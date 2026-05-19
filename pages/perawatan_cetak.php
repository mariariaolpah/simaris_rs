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

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

/* ================= KOP SURAT ================= */
$y = 8;

$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) $pdf->Image($logoLeft, 15, $y, 25);
if (file_exists($logoRight)) $pdf->Image($logoRight, 260, $y, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, $y + 5);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->Ln(15);

/* ================= JUDUL ================= */
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'DATA PERAWATAN DAN KALIBRASI ASET', 0, 1, 'C');
$pdf->Ln(5);

/* ================= HEADER TABEL ================= */
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(72, 201, 176); // SAMAIN WARNA HIJAU TOSCA
$pdf->SetTextColor(255);

// Header diperbarui dengan Kalibrasi
$header = ['No', 'Nama Aset', 'Teknisi', 'Tgl Perawatan', 'Jadwal Kalibrasi', 'Status'];
// Lebar disesuaikan agar pas dengan A4 Landscape (~277mm)
$widths = [10, 85, 45, 45, 45, 45];

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 10, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

/* ================= ISI TABEL ================= */
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

$i = 1;
while ($row = mysqli_fetch_assoc($query)) {
    // Format Tanggal
    $tgl_rawat = ($row['tanggal'] && $row['tanggal'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal'])) : '-';
    $tgl_kalibrasi = ($row['tanggal_kalibrasi_berikutnya'] && $row['tanggal_kalibrasi_berikutnya'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_kalibrasi_berikutnya'])) : '-';

    $pdf->Cell($widths[0], 8, $i++, 1, 0, 'C');
    $pdf->Cell($widths[1], 8, ' ' . $row['nama_aset'], 1, 0, 'L');
    $pdf->Cell($widths[2], 8, ' ' . $row['teknisi'], 1, 0, 'L');
    $pdf->Cell($widths[3], 8, $tgl_rawat, 1, 0, 'C');
    $pdf->Cell($widths[4], 8, $tgl_kalibrasi, 1, 0, 'C');
    $pdf->Cell($widths[5], 8, $row['status'], 1, 1, 'C');
}

/* ================= FOOTER ================= */
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 5, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 5, 'Administrator', 0, 1, 'R');

$pdf->Ln(15);

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
