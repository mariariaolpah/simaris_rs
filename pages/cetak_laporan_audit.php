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

// ---------- Kop surat (Dua Logo) ----------
$y = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

// Cek file logo ada atau tidak agar tidak error
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
$pdf->Cell(0, 10, 'LAPORAN HASIL AUDIT FISIK ASET (STOCK OPNAME)', 0, 1, 'C');
$pdf->Ln(5);

// ---------- Header Tabel (Warna Biru seperti Aset) ----------
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(52, 152, 219); // Warna Biru
$pdf->SetTextColor(255);

// Pengaturan Lebar Kolom (Total 260mm agar center di A4 Landscape)
$w = [10, 65, 45, 35, 35, 70];

$pdf->SetX(11);
$pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Nama Aset', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, 'Lokasi', 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'Tgl Audit', 1, 0, 'C', true);
$pdf->Cell($w[4], 10, 'Kondisi Fisik', 1, 0, 'C', true);
$pdf->Cell($w[5], 10, 'Keterangan / Auditor', 1, 1, 'C', true);

// ---------- Isi Tabel ----------
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);
$i = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $pdf->SetX(11);
    $pdf->Cell($w[0], 8, $i++, 1, 0, 'C');
    $pdf->Cell($w[1], 8, $row['nama_aset'], 1);
    $pdf->Cell($w[2], 8, $row['lokasi'], 1);
    $pdf->Cell($w[3], 8, date('d/m/Y', strtotime($row['tanggal_audit'])), 1, 0, 'C');
    $pdf->Cell($w[4], 8, $row['kondisi_fisik'], 1, 0, 'C');
    $pdf->Cell($w[5], 8, $row['keterangan'] . ' (Oleh: ' . $row['auditor'] . ')', 1, 1);
}

$pdf->Output();
