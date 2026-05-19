<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

// ==================== QUERY DATA ==================== //
$sql = "SELECT 
            audit_fisik.*, 
            aset.nama_aset, 
            aset.lokasi,
            aset.kategori_aset
        FROM audit_fisik 
        INNER JOIN aset ON audit_fisik.id_aset = aset.id_aset 
        ORDER BY audit_fisik.id_audit DESC";

$result = mysqli_query($koneksi, $sql);

// ==================== PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ==================== HEADER ==================== //
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) {
    $pdf->Image($logoLeft, 15, 8, 25);
}

if (file_exists($logoRight)) {
    $pdf->Image($logoRight, 252, 8, 25);
}

// Nama RS
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

// Judul
$pdf->SetY(34);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'LAPORAN AUDIT FISIK ASET', 0, 1, 'C');

$pdf->Ln(3);

// ==================== TABLE HEADER ==================== //
$w = [10, 55, 45, 30, 25, 35, 30, 55];

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

$pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Nama Aset', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, 'Lokasi', 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'Kategori', 1, 0, 'C', true);
$pdf->Cell($w[4], 10, 'Tanggal', 1, 0, 'C', true);
$pdf->Cell($w[5], 10, 'Auditor', 1, 0, 'C', true);
$pdf->Cell($w[6], 10, 'Kondisi', 1, 0, 'C', true);
$pdf->Cell($w[7], 10, 'Keterangan', 1, 1, 'C', true);

// ==================== ISI DATA ==================== //
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

$no = 1;

while ($row = mysqli_fetch_assoc($result)) {

    $nama_aset = !empty($row['nama_aset']) ? $row['nama_aset'] : '-';
    $lokasi = !empty($row['lokasi']) ? $row['lokasi'] : '-';
    $kategori = !empty($row['kategori_aset']) ? $row['kategori_aset'] : '-';
    $tanggal = !empty($row['tanggal_audit'])
        ? date('d/m/Y', strtotime($row['tanggal_audit']))
        : '-';

    $auditor = !empty($row['auditor']) ? $row['auditor'] : '-';
    $kondisi = !empty($row['kondisi_fisik']) ? $row['kondisi_fisik'] : '-';
    $keterangan = !empty($row['keterangan']) ? $row['keterangan'] : '-';

    $pdf->Cell($w[0], 9, $no++, 1, 0, 'C');
    $pdf->Cell($w[1], 9, substr($nama_aset, 0, 28), 1);
    $pdf->Cell($w[2], 9, substr($lokasi, 0, 23), 1);
    $pdf->Cell($w[3], 9, substr($kategori, 0, 15), 1, 0, 'C');
    $pdf->Cell($w[4], 9, $tanggal, 1, 0, 'C');
    $pdf->Cell($w[5], 9, substr($auditor, 0, 18), 1);
    $pdf->Cell($w[6], 9, substr($kondisi, 0, 18), 1, 0, 'C');
    $pdf->Cell($w[7], 9, substr($keterangan, 0, 35), 1, 1);
}

// ==================== FOOTER ==================== //
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');

$pdf->Ln(15);

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

// ==================== OUTPUT ==================== //
$pdf->Output('I', 'laporan_audit_fisik.pdf');
exit;
