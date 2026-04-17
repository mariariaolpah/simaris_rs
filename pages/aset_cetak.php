<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

$sql = "SELECT * FROM aset ORDER BY id_aset DESC";
$result = mysqli_query($koneksi, $sql);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

/* ================= HEADER ================= */
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
$pdf->Cell(0, 10, 'LAPORAN DATA ASET & INFRASTRUKTUR', 0, 1, 'C');
$pdf->Ln(3);

/* ================= TABLE ================= */
$w = [10, 45, 25, 30, 40, 25, 35, 30, 25];

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

$pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Nama Aset', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, 'Jenis', 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'Tipe', 1, 0, 'C', true);
$pdf->Cell($w[4], 10, 'Lokasi', 1, 0, 'C', true);
$pdf->Cell($w[5], 10, 'Kondisi', 1, 0, 'C', true);
$pdf->Cell($w[6], 10, 'Harga', 1, 0, 'C', true);
$pdf->Cell($w[7], 10, 'Tgl Masuk', 1, 0, 'C', true);
$pdf->Cell($w[8], 10, 'Dokumen', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

$no = 1;

while ($row = mysqli_fetch_assoc($result)) {

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    $pdf->Cell($w[0], 12, $no++, 1, 0, 'C');
    $pdf->Cell($w[1], 12, substr($row['nama_aset'], 0, 25), 1);
    $pdf->Cell($w[2], 12, substr($row['jenis'], 0, 15), 1);
    $pdf->Cell($w[3], 12, substr($row['tipe_aset'], 0, 15), 1);
    $pdf->Cell($w[4], 12, substr($row['lokasi'], 0, 20), 1);
    $pdf->Cell($w[5], 12, $row['kondisi'], 1, 0, 'C');
    $pdf->Cell($w[6], 12, number_format($row['harga'], 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell($w[7], 12, date('d/m/Y', strtotime($row['tanggal_masuk'])), 1, 0, 'C');

    $file = __DIR__ . '/../assets/dokumen/' . $row['dokumen'];

    if (!empty($row['dokumen']) && file_exists($file)) {
        $pdf->Cell($w[8], 12, '', 1, 1);

        $imgW = 10;
        $imgH = 8;

        $centerX = $x + array_sum(array_slice($w, 0, 8)) + ($w[8] / 2) - ($imgW / 2);
        $centerY = $y + 2;

        $pdf->Image($file, $centerX, $centerY, $imgW, $imgH);
    } else {
        $pdf->Cell($w[8], 12, '-', 1, 1, 'C');
    }
}

/* ================= FOOTER (DI BAWAH TABEL - KANAN) ================= */

$pdf->Ln(5); // kasih jarak sedikit dari tabel

$pdf->SetFont('Arial', '', 11);

// ambil posisi terakhir (biar tidak turun ke bawah halaman)
$y = $pdf->GetY();

$pdf->SetY($y);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Ln(5);

$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'], 0, 1, 'R');
$pdf->Output();
exit;
