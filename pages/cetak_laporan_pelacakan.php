<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}
ob_start();
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// Tangkap parameter filter dari laporan pelacakan
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$bulan = isset($_GET['bulan']) ? mysqli_real_escape_string($koneksi, $_GET['bulan']) : '';

// Kondisi pencarian
$where = [];
if ($search !== '') {
    $where[] = "(a.nama_aset LIKE '%$search%' OR r.penanggung_jawab LIKE '%$search%' OR r.lokasi_sebelumnya LIKE '%$search%' OR r.lokasi_baru LIKE '%$search%')";
}
if ($kategori !== '') {
    $where[] = "a.kategori_aset = '$kategori'";
}
if ($bulan !== '') {
    $where[] = "DATE_FORMAT(r.tanggal_pindah, '%Y-%m') = '$bulan'";
}
$whereSQL = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT r.*, a.nama_aset, a.kategori_aset 
        FROM riwayat_lokasi r 
        JOIN aset a ON r.id_aset = a.id_aset 
        $whereSQL 
        ORDER BY r.tanggal_pindah DESC, r.id_riwayat DESC";
$res = mysqli_query($koneksi, $sql);

// ================= SETUP PDF LANDSCAPE =================
// Menggunakan orientasi 'L' (Landscape) agar 8 kolom muat dan tidak tertinggal
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);

/* ================= KOP SURAT ================= */
$y = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) $pdf->Image($logoLeft, 15, $y, 25);
if (file_exists($logoRight)) $pdf->Image($logoRight, 260, $y, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, $y + 5);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(15);

/* ================= JUDUL LAPORAN ================= */
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'LAPORAN MUTASI & PELACAKAN LOKASI ASET', 0, 1, 'C');

// Tampilkan teks filter bulan jika ada
if ($bulan != '') {
    $pdf->SetFont('Arial', 'I', 11);
    $pdf->Cell(0, 6, "Periode Bulan: " . date('F Y', strtotime($bulan . '-01')), 0, 1, 'C');
}
$pdf->Ln(5);

/* ================= HEADER TABEL (8 KOLOM MUAT SEMUA) ================= */
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

// Total Lebar: 277mm (Maksimal Landscape A4)
$header = ['No', 'Tanggal', 'Nama Aset', 'Kategori', 'P. Jawab', 'Lok. Awal', 'Lok. Baru', 'Keterangan'];
$widths = [10, 22, 50, 25, 35, 35, 35, 65];

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 10, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

/* ================= ISI DATA TABEL ================= */
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

$no = 1;
if (mysqli_num_rows($res) == 0) {
    $pdf->Cell(array_sum($widths), 10, 'Tidak ada data pelacakan aset yang sesuai filter.', 1, 1, 'C');
} else {
    while ($row = mysqli_fetch_assoc($res)) {
        $tgl_pindah = ($row['tanggal_pindah'] && $row['tanggal_pindah'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_pindah'])) : '-';
        $kat_aset = ($row['kategori_aset'] == 'Medis') ? 'Medis' : 'Non-Medis';
        $p_jawab = isset($row['penanggung_jawab']) ? $row['penanggung_jawab'] : '-';

        // Memotong teks sedikit jika terlalu panjang agar tidak keluar garis
        $pdf->Cell($widths[0], 9, $no++, 1, 0, 'C');
        $pdf->Cell($widths[1], 9, $tgl_pindah, 1, 0, 'C');
        $pdf->Cell($widths[2], 9, ' ' . substr($row['nama_aset'], 0, 25), 1, 0, 'L');
        $pdf->Cell($widths[3], 9, ' ' . $kat_aset, 1, 0, 'C');
        $pdf->Cell($widths[4], 9, ' ' . substr($p_jawab, 0, 20), 1, 0, 'L');
        $pdf->Cell($widths[5], 9, ' ' . substr($row['lokasi_sebelumnya'], 0, 18), 1, 0, 'L');
        $pdf->Cell($widths[6], 9, ' ' . substr($row['lokasi_baru'], 0, 18), 1, 0, 'L');
        $pdf->Cell($widths[7], 9, ' ' . substr($row['keterangan'], 0, 32), 1, 1, 'L');
    }
}

/* ================= FOOTER TANDA TANGAN ================= */
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 5, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 5, 'Administrator', 0, 1, 'R');

$pdf->Ln(15);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Output('I', 'Laporan_Pelacakan_Aset.pdf');
exit;
