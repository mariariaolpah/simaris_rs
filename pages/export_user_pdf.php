<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

// Filter search & level jika ada
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$filter_level = isset($_GET['level']) ? mysqli_real_escape_string($koneksi, $_GET['level']) : '';

$sql = "SELECT * FROM pengguna";
$where = [];
if ($search != '') {
    $where[] = "(nama_pengguna LIKE '%$search%' OR username LIKE '%$search%' OR level LIKE '%$search%' OR role LIKE '%$search%')";
}
if ($filter_level != '') {
    $where[] = "level='$filter_level'";
}
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY id_pengguna DESC";

$result = mysqli_query($koneksi, $sql);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ---------- Kop surat ----------
$y = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (!$logoLeft || !file_exists($logoLeft)) die('Logo kiri tidak ditemukan');
if (!$logoRight || !file_exists($logoRight)) die('Logo kanan tidak ditemukan');

$margin = 15; // margin kiri/kanan
$logoWidth = 25;

// Logo kiri
$pdf->Image($logoLeft, $margin, $y, $logoWidth);

// Logo kanan
$pdf->Image($logoRight, 297 - $margin - $logoWidth, $y, $logoWidth);

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
$pdf->Cell(0, 12, 'Data User SIMARIS RS Bhayangkara', 0, 1, 'C');
$pdf->Ln(5);

// ---------- Header tabel ----------
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(52, 152, 219); // biru
$pdf->SetTextColor(255);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(.3);

$header = ['No', 'Nama', 'Username', 'Level', 'Role', 'Status'];

// margin tabel
$pdf->SetLeftMargin($margin);
$pdf->SetRightMargin($margin);

// lebar kolom disesuaikan agar total = 297 - 2*margin = 267
$widths = [12, 80, 60, 35, 40, 40];

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
    $pdf->Cell($widths[1], 10, $row['nama_pengguna'], 1);
    $pdf->Cell($widths[2], 10, $row['username'], 1);
    $pdf->Cell($widths[3], 10, $row['level'], 1, 0, 'C');
    $pdf->Cell($widths[4], 10, ucfirst($row['role']), 1, 0, 'C');
    $pdf->Cell($widths[5], 10, $row['status'] ?? 'aktif', 1, 1, 'C');
}

$pdf->Output();
