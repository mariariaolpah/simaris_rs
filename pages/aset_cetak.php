<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
if ($search != '') {
    $sql = "SELECT * FROM aset 
            WHERE nama_aset LIKE '%$search%' 
               OR jenis LIKE '%$search%' 
               OR lokasi LIKE '%$search%' 
               OR kondisi LIKE '%$search%'
            ORDER BY id_aset DESC";
} else {
    $sql = "SELECT * FROM aset ORDER BY id_aset DESC";
}
$result = mysqli_query($koneksi, $sql);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ---------- Kop surat dengan logo kiri, teks tengah, logo kanan ----------
$y = 8;

$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (!$logoLeft || !file_exists($logoLeft)) {
    die('Logo kiri tidak ditemukan: ' . $logoLeft);
}
if (!$logoRight || !file_exists($logoRight)) {
    die('Logo kanan tidak ditemukan: ' . $logoRight);
}

$margin = 20;
$logoWidth = 25;

// Logo kiri
$pdf->Image($logoLeft, $margin, $y, $logoWidth);

// Logo kanan
$pdf->Image($logoRight, 297 - $logoWidth - $margin, $y, $logoWidth);

// Teks tengah kop
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, $y + 5);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->SetX(0);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->Ln(15);

// ---------- Judul tabel ----------
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 12, 'Data Aset RS Bhayangkara', 0, 1, 'C');
$pdf->Ln(5);

// ---------- Header tabel ----------
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(52, 152, 219);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(.3);

$header = ['No', 'Nama Aset', 'Jenis', 'Lokasi', 'Kondisi', 'Tanggal Masuk'];
$pdf->SetLeftMargin(11);
$pdf->SetRightMargin(11);

$widths = [12, 70, 50, 70, 40, 35]; // total 277mm < 297 A4

foreach ($header as $key => $col) {
    $pdf->Cell($widths[$key], 12, $col, 1, 0, 'C', true);
}
$pdf->Ln();

// ---------- Isi tabel ----------
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor(0);
$i = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $pdf->Cell($widths[0], 10, $i++, 1, 0, 'C');
    $pdf->Cell($widths[1], 10, $row['nama_aset'], 1);
    $pdf->Cell($widths[2], 10, $row['jenis'], 1);
    $pdf->Cell($widths[3], 10, $row['lokasi'], 1);
    $pdf->Cell($widths[4], 10, $row['kondisi'], 1);
    $pdf->Cell($widths[5], 10, date('Y-m-d', strtotime($row['tanggal_masuk'])), 1, 1);
}

$pdf->Output();
