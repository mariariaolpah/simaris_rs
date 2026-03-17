<?php
session_start();
// Proteksi halaman: Hanya admin yang bisa akses laporan keuangan
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

// Query mengambil data aset yang memiliki harga
$sql = "SELECT * FROM aset WHERE harga > 0 ORDER BY tanggal_masuk DESC";
$result = mysqli_query($koneksi, $sql);

// Query hitung total seluruh nilai aset
$total_query = mysqli_query($koneksi, "SELECT SUM(harga) as total_duit FROM aset");
$total_res = mysqli_fetch_assoc($total_query);
$total_seluruh = $total_res['total_duit'] ? $total_res['total_duit'] : 0;

// Inisialisasi PDF Landscape
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ---------- KOP SURAT (Dua Logo) ----------
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
$pdf->Line(10, 35, 287, 35); // Garis pembatas kop
$pdf->Ln(15);

// ---------- JUDUL LAPORAN ----------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN REKAPITULASI NILAI PEROLEHAN ASET', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Dicetak pada: ' . date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Ln(5);

// ---------- HEADER TABEL (Warna Biru SIMARIS) ----------
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(52, 152, 219); // Biru sesuai aset_cetak
$pdf->SetTextColor(255);

// Lebar kolom
$w = [10, 80, 45, 40, 50, 50];

$pdf->SetX(11);
$pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Nama Aset / Infrastruktur', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, 'Asal-Usul', 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'Tgl Masuk', 1, 0, 'C', true);
$pdf->Cell($w[4], 10, 'Harga Perolehan', 1, 0, 'C', true);
$pdf->Cell($w[5], 10, 'Kondisi', 1, 1, 'C', true);

// ---------- ISI TABEL ----------
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);
$no = 1;

while ($row = mysqli_fetch_assoc($result)) {
    $pdf->SetX(11);
    $pdf->Cell($w[0], 8, $no++, 1, 0, 'C');
    $pdf->Cell($w[1], 8, $row['nama_aset'], 1);
    $pdf->Cell($w[2], 8, $row['asal_usul'], 1, 0, 'C');
    $pdf->Cell($w[3], 8, date('d/m/Y', strtotime($row['tanggal_masuk'])), 1, 0, 'C');
    $pdf->Cell($w[4], 8, 'Rp ' . number_format($row['harga'], 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell($w[5], 8, $row['kondisi'], 1, 1, 'C');
}

// ---------- TOTAL DI BAGIAN BAWAH ----------
$pdf->SetX(11);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell($w[0] + $w[1] + $w[2] + $w[3], 10, 'TOTAL KESELURUHAN NILAI INVESTASI ASET', 1, 0, 'R', true);
$pdf->Cell($w[4], 10, 'Rp ' . number_format($total_seluruh, 0, ',', '.'), 1, 0, 'R', true);
$pdf->Cell($w[5], 10, '', 1, 1, 'C', true);

// ---------- TANDA TANGAN ----------
$pdf->Ln(15);
$pdf->Cell(220);
$pdf->Cell(0, 5, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'C');
$pdf->Cell(220);
$pdf->Cell(0, 5, 'Kepala Urusan Logistik,', 0, 1, 'C');
$pdf->Ln(20);
$pdf->Cell(220);
$pdf->SetFont('Arial', 'BU', 10);
$pdf->Cell(0, 5, '( ' . $_SESSION['nama_pengguna'] . ' )', 0, 1, 'C');

$pdf->Output('I', 'Laporan_Nilai_Aset.pdf');
