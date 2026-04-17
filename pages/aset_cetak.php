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

/* ================= KOP SURAT ================= */
$y = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) $pdf->Image($logoLeft, 15, $y, 22);
if (file_exists($logoRight)) $pdf->Image($logoRight, 260, $y, 22);

$pdf->SetFont('Arial', 'B', 15);
$pdf->SetXY(0, $y + 2);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 5, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(15);

/* ================= JUDUL ================= */
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN DATA ASET & INFRASTRUKTUR', 0, 1, 'C');
$pdf->Ln(5);

/* ================= HEADER TABEL ================= */
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

$w = [10, 45, 30, 35, 40, 25, 30, 35, 25];

$pdf->SetX(11);
$pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Nama Aset', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, 'Jenis', 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'Tipe', 1, 0, 'C', true);
$pdf->Cell($w[4], 10, 'Lokasi', 1, 0, 'C', true);
$pdf->Cell($w[5], 10, 'Kondisi', 1, 0, 'C', true);
$pdf->Cell($w[6], 10, 'Asal-Usul', 1, 0, 'C', true);
$pdf->Cell($w[7], 10, 'Harga (Rp)', 1, 0, 'C', true);
$pdf->Cell($w[8], 10, 'Tgl Masuk', 1, 1, 'C', true);

/* ================= ISI ================= */
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

$i = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $pdf->SetX(11);
    $pdf->Cell($w[0], 8, $i++, 1, 0, 'C');
    $pdf->Cell($w[1], 8, $row['nama_aset'], 1);
    $pdf->Cell($w[2], 8, $row['jenis'], 1);
    $pdf->Cell($w[3], 8, $row['tipe_aset'], 1);
    $pdf->Cell($w[4], 8, $row['lokasi'], 1);
    $pdf->Cell($w[5], 8, $row['kondisi'], 1, 0, 'C');
    $pdf->Cell($w[6], 8, $row['asal_usul'], 1, 0, 'C');
    $pdf->Cell($w[7], 8, number_format($row['harga'], 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell($w[8], 8, date('d/m/Y', strtotime($row['tanggal_masuk'])), 1, 1, 'C');
}

/* ================= FOOTER TTD (BARU) ================= */
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 5, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 5, 'Administrator', 0, 1, 'R');

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Ln(5);

$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(
    0,
    5,
    'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'],
    0,
    1,
    'R'
);

$pdf->Output();
exit;
