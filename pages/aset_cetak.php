<?php
session_start();

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

/* ================= QUERY ================= */

// Modifikasi: mengambil data stok yang sudah dipecah
$sql = "SELECT 
            nama_aset,
            kategori_aset,
            jenis,
            tipe_aset,
            lokasi,
            total_stok, 
            stok_tersedia,
            stok_rusak,
            stok_perawatan,
            asal_usul,
            harga,
            umur_ekonomis,
            tanggal_masuk,
            dokumen
        FROM aset
        ORDER BY id_aset ASC";

$result = mysqli_query($koneksi, $sql);

/* ================= PDF ================= */

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

$pdf->SetMargins(8, 8, 8);

/* ================= HEADER ================= */

$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) {
    $pdf->Image($logoLeft, 10, 8, 20);
}

if (file_exists($logoRight)) {
    $pdf->Image($logoRight, 267, 8, 18);
}

$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 8, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin', 0, 1, 'C');

$pdf->Ln(3);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'LAPORAN DATA ASET & INFRASTRUKTUR', 0, 1, 'C');

$pdf->Ln(3);

/* ================= UKURAN TABEL ================= */
// Disesuaikan agar muat dengan tambahan kolom stok
$w = [8, 38, 20, 20, 30, 15, 12, 12, 12, 22, 25, 15, 20, 22];

/* ================= HEADER TABEL ================= */

$pdf->SetFillColor(72, 201, 176); // HIJAU MUDA
$pdf->SetTextColor(255);
$pdf->SetFont('Arial', 'B', 7);

$header = [
    'No',
    'Nama Aset',
    'Kategori',
    'Jenis',
    'Lokasi',
    'Total',
    'Baik',
    'Rusak',
    'Prwtn',
    'Asal Usul',
    'Harga',
    'Umur',
    'Tgl Masuk',
    'Dokumen'
];

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 10, $header[$i], 1, 0, 'C', true);
}

$pdf->Ln();

/* ================= BODY ================= */

$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 6.5);

$no = 1;

while ($row = mysqli_fetch_assoc($result)) {

    $nama      = $row['nama_aset'] ?? '-';
    $kategori  = $row['kategori_aset'] ?? '-';
    $jenis     = $row['jenis'] ?? '-';
    $lokasi    = $row['lokasi'] ?? '-';

    // Ambil data stok baru
    $total     = $row['total_stok'] ?? '0';
    $baik      = $row['stok_tersedia'] ?? '0';
    $rusak     = $row['stok_rusak'] ?? '0';
    $prwtn     = $row['stok_perawatan'] ?? '0';

    $asal      = $row['asal_usul'] ?? '-';
    $harga     = 'Rp ' . number_format($row['harga'] ?? 0, 0, ',', '.');
    $umur      = ($row['umur_ekonomis'] ?? '-') . ' Th';

    $tanggal = '-';

    if (!empty($row['tanggal_masuk']) && $row['tanggal_masuk'] != '0000-00-00') {
        $tanggal = date('d/m/Y', strtotime($row['tanggal_masuk']));
    }

    $tinggi = 12;

    /* ================= PAGE BREAK ================= */

    if ($pdf->GetY() + $tinggi > 185) {
        $pdf->AddPage();
        $pdf->SetFillColor(72, 201, 176);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Arial', 'B', 7);

        for ($i = 0; $i < count($header); $i++) {
            $pdf->Cell($w[$i], 10, $header[$i], 1, 0, 'C', true);
        }
        $pdf->Ln();
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '', 6.5);
    }

    /* ================= CETAK DATA ================= */

    $pdf->Cell($w[0], $tinggi, $no++, 1, 0, 'C');
    $pdf->Cell($w[1], $tinggi, utf8_decode(substr($nama, 0, 28)), 1, 0, 'L');
    $pdf->Cell($w[2], $tinggi, utf8_decode(substr($kategori, 0, 15)), 1, 0, 'C');
    $pdf->Cell($w[3], $tinggi, utf8_decode(substr($jenis, 0, 15)), 1, 0, 'C');
    $pdf->Cell($w[4], $tinggi, utf8_decode(substr($lokasi, 0, 20)), 1, 0, 'L');

    // Tampilan Kolom Stok
    $pdf->Cell($w[5], $tinggi, $total, 1, 0, 'C');
    $pdf->Cell($w[6], $tinggi, $baik, 1, 0, 'C');
    $pdf->Cell($w[7], $tinggi, $rusak, 1, 0, 'C');
    $pdf->Cell($w[8], $tinggi, $prwtn, 1, 0, 'C');

    $pdf->Cell($w[9], $tinggi, utf8_decode(substr($asal, 0, 18)), 1, 0, 'C');
    $pdf->Cell($w[10], $tinggi, $harga, 1, 0, 'R');
    $pdf->Cell($w[11], $tinggi, $umur, 1, 0, 'C');
    $pdf->Cell($w[12], $tinggi, $tanggal, 1, 0, 'C');

    /* ================= DOKUMEN ================= */

    $dokumen = $row['dokumen'] ?? '';
    $file = __DIR__ . '/../assets/dokumen/' . $dokumen;

    if (!empty($dokumen) && file_exists($file)) {
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Cell($w[13], $tinggi, '', 1, 1);
        $pdf->Image($file, $x + 7, $y + 2, 10, 8);
    } else {
        $pdf->Cell($w[13], $tinggi, 'Tidak Ada', 1, 1, 'C');
    }
}

/* ================= FOOTER ================= */

$pdf->Ln(5);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(12);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, $_SESSION['nama_pengguna'], 0, 1, 'R');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'], 0, 1, 'R');

/* ================= OUTPUT ================= */

$pdf->Output();
exit;
