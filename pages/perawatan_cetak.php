<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$sql = "SELECT * FROM perawatan";
if ($search != '') {
    $sql .= " WHERE nama_aset LIKE '%$search%' OR teknisi LIKE '%$search%' OR status LIKE '%$search%'";
}
$sql .= " ORDER BY id DESC";
$query = mysqli_query($koneksi, $sql);

$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// Kop surat
$pdf->Image($logoLeft, 15, 8, 25);
$pdf->Image($logoRight, 297 - 25 - 15, 8, 25);
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 13);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->SetX(0);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(15);

// Judul
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 12, 'Data Perawatan RS Bhayangkara', 0, 1, 'C');
$pdf->Ln(5);

// Header tabel
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetFillColor(52, 152, 219);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(.3);
$header = ['No', 'Nama Aset', 'Teknisi', 'Tanggal', 'Status'];
// Set margin agar tabel benar-benar center
$pdf->SetLeftMargin(11);
$pdf->SetRightMargin(11);

// Lebar kolom total 275mm (pas untuk landscape dengan margin)
$widths = [12, 95, 55, 75, 38];

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 12, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Isi tabel
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor(0);
$i = 1;
while ($row = mysqli_fetch_assoc($query)) {
    $pdf->Cell($widths[0], 10, $i++, 1, 0, 'C');
    $pdf->Cell($widths[1], 10, $row['nama_aset'], 1);
    $pdf->Cell($widths[2], 10, $row['teknisi'], 1);
    $pdf->Cell($widths[3], 10, $row['tanggal'], 1);
    $pdf->Cell($widths[4], 10, $row['status'], 1, 1);
}

$pdf->Output();
