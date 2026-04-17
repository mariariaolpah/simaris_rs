<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

// Ambil data audit dengan JOIN ke aset
$sql = "SELECT audit_fisik.*, aset.nama_aset, aset.lokasi 
        FROM audit_fisik 
        JOIN aset ON audit_fisik.id_aset = aset.id_aset 
        ORDER BY audit_fisik.id_audit DESC";
$result = mysqli_query($koneksi, $sql);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ==================== HEADER ==================== //
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) $pdf->Image($logoLeft, 15, 8, 25);
if (file_exists($logoRight)) $pdf->Image($logoRight, 252, 8, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->SetY(32);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'LAPORAN HASIL AUDIT FISIK ASET (STOCK OPNAME)', 0, 1, 'C');
$pdf->Ln(4);

// ==================== TABEL ==================== //
$widths = [12, 65, 45, 35, 40, 80];
$header = ['No', 'Nama Aset', 'Lokasi', 'Tgl Audit', 'Kondisi', 'Keterangan / Auditor'];

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 11, $col, 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

$i = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $pdf->Cell($widths[0], 9, $i++, 1, 0, 'C');
    $pdf->Cell($widths[1], 9, substr($row['nama_aset'], 0, 35), 1);
    $pdf->Cell($widths[2], 9, substr($row['lokasi'], 0, 30), 1);
    $pdf->Cell($widths[3], 9, date('d/m/Y', strtotime($row['tanggal_audit'])), 1, 0, 'C');
    $pdf->Cell($widths[4], 9, substr($row['kondisi_fisik'], 0, 20), 1, 0, 'C');
    $pdf->Cell($widths[5], 9, substr($row['keterangan'] . ' (Oleh: ' . $row['auditor'] . ')', 0, 40), 1, 1);
}

// ==================== FOOTER (RAPI + KANAN) ==================== //
$pdf->SetY(-50);
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

// ==================== OUTPUT (HARUS PALING BAWAH) ==================== //
$pdf->Output();
exit;
