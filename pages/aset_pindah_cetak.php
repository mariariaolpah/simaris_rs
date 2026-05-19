<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// Ambil parameter search dan filter dari URL (jika ada)
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';

// Bangun query pencarian senada dengan halaman utama
$sql = "SELECT r.*, a.nama_aset, a.kategori_aset 
        FROM riwayat_lokasi r 
        JOIN aset a ON r.id_aset = a.id_aset";

$conditions = [];
if ($search != '') {
    $conditions[] = "(a.nama_aset LIKE '%$search%' OR r.lokasi_sebelumnya LIKE '%$search%' OR r.lokasi_baru LIKE '%$search%' OR r.keterangan LIKE '%$search%')";
}
if ($kategori != '') {
    $conditions[] = "a.kategori_aset = '$kategori'";
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY r.id_riwayat DESC"; // Sesuai dengan urutan data terbaru

$query = mysqli_query($koneksi, $sql);

// Set cetak posisi Landscape A4
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

/* ================= KOP SURAT (SENADA DENGAN PERAWATAN) ================= */
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

/* ================= JUDUL LAPORAN ================= */
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'LAPORAN MUTASI & PELACAKAN LOKASI ASET', 0, 1, 'C');
$pdf->Ln(5);

/* ================= HEADER TABEL (WARNA HIJAU TOSCA IDENTIK) ================= */
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(72, 201, 176); // Warna Tosca yang sama persis
$pdf->SetTextColor(255);

$header = ['No', 'Tgl Pindah', 'Nama Aset', 'Kategori', 'Lokasi Awal', 'Lokasi Baru', 'Keterangan'];
$widths = [12, 28, 65, 35, 42, 42, 53]; // Total lebar pas dengan A4 Landscape (277mm)

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 10, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

/* ================= ISI DATA TABEL ================= */
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

$i = 1;
if (mysqli_num_rows($query) == 0) {
    $pdf->Cell(array_sum($widths), 10, 'Tidak ada data riwayat perpindahan lokasi', 1, 1, 'C');
} else {
    while ($row = mysqli_fetch_assoc($query)) {
        $tgl_pindah = ($row['tanggal_pindah'] && $row['tanggal_pindah'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_pindah'])) : '-';
        $kat_aset = ($row['kategori_aset'] == 'Medis') ? 'Medis (Alkes)' : 'Non-Medis';

        $pdf->Cell($widths[0], 9, $i++, 1, 0, 'C');
        $pdf->Cell($widths[1], 9, $tgl_pindah, 1, 0, 'C');
        $pdf->Cell($widths[2], 9, ' ' . $row['nama_aset'], 1, 0, 'L');
        $pdf->Cell($widths[3], 9, ' ' . $kat_aset, 1, 0, 'C');
        $pdf->Cell($widths[4], 9, ' ' . $row['lokasi_sebelumnya'], 1, 0, 'L');
        $pdf->Cell($widths[5], 9, ' ' . $row['lokasi_baru'], 1, 0, 'L');
        $pdf->Cell($widths[6], 9, ' ' . $row['keterangan'], 1, 1, 'L');
    }
}

/* ================= FOOTER TANDA TANGAN (IDENTIK) ================= */
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 5, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 5, 'Administrator', 0, 1, 'R');

$pdf->Ln(15); // Ruang tanda tangan

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Output();
exit;
