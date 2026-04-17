<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

// ==================== QUERY DATA ==================== //
$sql = "SELECT audit_fisik.*, aset.nama_aset, aset.lokasi 
        FROM audit_fisik 
        LEFT JOIN aset ON audit_fisik.id_aset = aset.id_aset 
        ORDER BY audit_fisik.id_audit DESC";

$result = mysqli_query($koneksi, $sql);

// ==================== PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ==================== HEADER (SAMA SEMUA MODUL) ==================== //
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) $pdf->Image($logoLeft, 15, 8, 25);
if (file_exists($logoRight)) $pdf->Image($logoRight, 252, 8, 25);

// Nama RS
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

// Judul
$pdf->SetY(32);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'LAPORAN AUDIT FISIK ASET', 0, 1, 'C');
$pdf->Ln(4);

// ==================== TABLE HEADER (SAMA WARNA ASSET) ==================== //
$w = [10, 60, 45, 35, 40, 80];

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

$pdf->Cell($w[0], 11, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 11, 'Nama Aset', 1, 0, 'C', true);
$pdf->Cell($w[2], 11, 'Lokasi', 1, 0, 'C', true);
$pdf->Cell($w[3], 11, 'Tgl Audit', 1, 0, 'C', true);
$pdf->Cell($w[4], 11, 'Kondisi', 1, 0, 'C', true);
$pdf->Cell($w[5], 11, 'Keterangan', 1, 1, 'C', true);

// ==================== ISI ==================== //
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

$no = 1;

while ($row = mysqli_fetch_assoc($result)) {

    $ket = $row['keterangan'] . ' (Oleh: ' . $row['auditor'] . ')';

    $pdf->Cell($w[0], 9, $no++, 1, 0, 'C');
    $pdf->Cell($w[1], 9, substr($row['nama_aset'], 0, 30), 1);
    $pdf->Cell($w[2], 9, substr($row['lokasi'], 0, 25), 1);
    $pdf->Cell($w[3], 9, date('d/m/Y', strtotime($row['tanggal_audit'])), 1, 0, 'C');
    $pdf->Cell($w[4], 9, $row['kondisi_fisik'], 1, 0, 'C');
    $pdf->Cell($w[5], 9, substr($ket, 0, 50), 1, 1);
}

// ==================== FOOTER (KANAN RAPI SAMA SEMUA) ==================== //
$pdf->SetY(-45);
$pdf->SetFont('Arial', '', 11);

$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Ln(5);

$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(
    0,
    6,
    'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'],
    0,
    1,
    'R'
);

$pdf->Output();
exit;
