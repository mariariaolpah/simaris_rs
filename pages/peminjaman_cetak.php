<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

// Ambil data peminjaman dengan JOIN ke aset
$sql = "SELECT peminjaman.*, aset.nama_aset, aset.jenis 
        FROM peminjaman 
        JOIN aset ON peminjaman.id_aset = aset.id_aset 
        ORDER BY peminjaman.id_pinjam DESC";
$result = mysqli_query($koneksi, $sql);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ---------- Kop Surat ----------
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

// ---------- Judul Laporan ----------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN TRANSAKSI PEMINJAMAN ASET', 0, 1, 'C');
$pdf->Ln(5);

// ---------- Header Tabel ----------
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(52, 152, 219);
$pdf->SetTextColor(255);

// Lebar kolom (Total 265mm)
$w = [10, 55, 60, 40, 35, 35, 30];

$pdf->SetX(16);
$pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Nama Peminjam', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, 'Nama Alat / Aset', 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'Jenis', 1, 0, 'C', true);
$pdf->Cell($w[4], 10, 'Tgl Pinjam', 1, 0, 'C', true);
$pdf->Cell($w[5], 10, 'Tgl Kembali', 1, 0, 'C', true);
$pdf->Cell($w[6], 10, 'Status', 1, 1, 'C', true);

// ---------- Isi Tabel ----------
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);
$i = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $pdf->SetX(16);
    $pdf->Cell($w[0], 8, $i++, 1, 0, 'C');
    $pdf->Cell($w[1], 8, $row['nama_peminjam'], 1);
    $pdf->Cell($w[2], 8, $row['nama_aset'], 1);
    $pdf->Cell($w[3], 8, $row['jenis'], 1);
    $pdf->Cell($w[4], 8, date('d/m/Y', strtotime($row['tanggal_pinjam'])), 1, 0, 'C');
    $pdf->Cell($w[5], 8, ($row['tanggal_kembali'] ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '-'), 1, 0, 'C');
    $pdf->Cell($w[6], 8, $row['status_pinjam'], 1, 1, 'C');
}

$pdf->Output();
