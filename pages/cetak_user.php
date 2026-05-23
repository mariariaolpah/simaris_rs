<?php
session_start();

// pastikan login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// izinkan admin, pegawai, teknisi, dll
$role = $_SESSION['role'] ?? $_SESSION['level'] ?? '';
if (!in_array(strtolower($role), ['admin', 'user', 'pegawai', 'teknisi'])) {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Akses ditolak!</h2>";
    exit;
}

ob_start();

// koneksi & fpdf
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/fpdf.php';

// ================= QUERY DATA ASET ================= //
// Diurutkan secara ASC (dari data pertama/terlama ke terbaru) sama seperti perbaikan webnya
$sql = "SELECT * FROM aset ORDER BY id_aset ASC";
$res = mysqli_query($koneksi, $sql);

// ================= SETUP PDF ===================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// LOGO
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 10, 8, 25);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 270, 8, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->SetY(34);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'LAPORAN RINCIAN KETERSEDIAAN ASET', 0, 1, 'C');
$pdf->Ln(4);

// ================= TABEL HEADER =================== //
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(44, 122, 123); // Warna header hijau tosca
$pdf->SetTextColor(255);

// Header kolom menyesuaikan Data Aset (Lebar total 277)
$header = ['No', 'Nama Aset', 'Kategori', 'Tipe / Spesifikasi', 'Lokasi Ruangan', 'Stok', 'Kondisi'];
$widths = [10, 60, 25, 45, 50, 25, 62];

foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 10, $col, 1, 0, 'C', true);
}
$pdf->Ln();

// ================= ISI TABEL ===================== //
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

$no = 1;
while ($r = mysqli_fetch_assoc($res)) {
    // Potong teks jika terlalu panjang agar tabel PDF tidak rusak
    $nama       = substr($r['nama_aset'] ?? '-', 0, 35);
    $kategori   = substr($r['kategori_aset'] ?? '-', 0, 15);
    $tipe       = substr($r['tipe_aset'] ?? '-', 0, 25);
    $lokasi     = substr($r['lokasi'] ?? '-', 0, 28);
    $stok       = $r['total_stok'] ?? '0';
    $kondisi    = substr($r['kondisi'] ?? '-', 0, 35);

    $pdf->Cell($widths[0], 8, $no++, 1, 0, 'C');
    $pdf->Cell($widths[1], 8, $nama, 1);
    $pdf->Cell($widths[2], 8, $kategori, 1, 0, 'C');
    $pdf->Cell($widths[3], 8, $tipe, 1);
    $pdf->Cell($widths[4], 8, $lokasi, 1);
    $pdf->Cell($widths[5], 8, $stok, 1, 0, 'C');
    $pdf->Cell($widths[6], 8, $kondisi, 1, 1);
}

// ================= TTD & FOOTER ========================== //
$pdf->Ln(8);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(18);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Kepala / Administrator', 0, 1, 'R');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . ($_SESSION['nama_pengguna'] ?? '-'), 0, 1, 'R');

ob_end_clean();
$pdf->Output('I', 'Laporan_Data_Aset_' . date('Ymd_His') . '.pdf');
exit;
