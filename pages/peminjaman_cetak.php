<?php
session_start();

if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

/* ================= QUERY ================= */

$sql = "SELECT 
            peminjaman.*,
            aset.nama_aset,
            aset.kategori_aset,
            aset.lokasi
        FROM peminjaman
        JOIN aset ON peminjaman.id_aset = aset.id_aset
        ORDER BY peminjaman.id_pinjam DESC";

$result = mysqli_query($koneksi, $sql);

/* ================= PDF ================= */

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 20);

/* ================= HEADER ================= */

$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) {
    $pdf->Image($logoLeft, 12, 8, 22);
}

if (file_exists($logoRight)) {
    $pdf->Image($logoRight, 262, 8, 22);
}

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 8, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 8, 'LAPORAN TRANSAKSI PEMINJAMAN ASET', 0, 1, 'C');

$pdf->Ln(6);

/* ================= UKURAN KOLOM ================= */

$w = [
    10, // no
    40, // peminjam
    70, // nama alat
    28, // kategori
    45, // lokasi
    25, // tgl pinjam
    25, // tgl kembali
    30  // status
];

/* ================= HEADER TABEL ================= */

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

$pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Peminjam', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, 'Nama Alat', 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'Kategori', 1, 0, 'C', true);
$pdf->Cell($w[4], 10, 'Lokasi Asal', 1, 0, 'C', true);
$pdf->Cell($w[5], 10, 'Tgl Pinjam', 1, 0, 'C', true);
$pdf->Cell($w[6], 10, 'Tgl Kembali', 1, 0, 'C', true);
$pdf->Cell($w[7], 10, 'Status', 1, 1, 'C', true);

/* ================= BODY ================= */

$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0);

$no = 1;

while ($row = mysqli_fetch_assoc($result)) {

    $peminjam = $row['nama_peminjam'] ?? '-';
    $nama_aset = $row['nama_aset'] ?? '-';
    $kategori = $row['kategori_aset'] ?? '-';
    $lokasi = $row['lokasi'] ?? '-';

    $tgl_pinjam = '-';
    if (!empty($row['tanggal_pinjam']) && $row['tanggal_pinjam'] != '0000-00-00') {
        $tgl_pinjam = date('d/m/Y', strtotime($row['tanggal_pinjam']));
    }

    $tgl_kembali = '-';
    if (!empty($row['tanggal_kembali']) && $row['tanggal_kembali'] != '0000-00-00') {
        $tgl_kembali = date('d/m/Y', strtotime($row['tanggal_kembali']));
    }

    $status = $row['status_pinjam'] ?? '-';

    /* ================= TINGGI BARIS ================= */

    $maxLine = max(
        ceil(strlen($peminjam) / 25),
        ceil(strlen($nama_aset) / 40),
        ceil(strlen($kategori) / 18),
        ceil(strlen($lokasi) / 25)
    );

    if ($maxLine < 1) {
        $maxLine = 1;
    }

    $tinggi = $maxLine * 6;

    /* ================= POSISI ================= */

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    /* ================= BORDER RAPI ================= */

    $pdf->Rect($x, $y, $w[0], $tinggi);
    $pdf->Rect($x + $w[0], $y, $w[1], $tinggi);
    $pdf->Rect($x + $w[0] + $w[1], $y, $w[2], $tinggi);
    $pdf->Rect($x + $w[0] + $w[1] + $w[2], $y, $w[3], $tinggi);
    $pdf->Rect($x + $w[0] + $w[1] + $w[2] + $w[3], $y, $w[4], $tinggi);
    $pdf->Rect($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y, $w[5], $tinggi);
    $pdf->Rect($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y, $w[6], $tinggi);
    $pdf->Rect($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6], $y, $w[7], $tinggi);

    /* ================= ISI ================= */

    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($w[0], 5, $no++, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + 1, $y + 2);
    $pdf->MultiCell($w[1] - 2, 5, $peminjam, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + 1, $y + 2);
    $pdf->MultiCell($w[2] - 2, 5, $nama_aset, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + 1, $y + 2);
    $pdf->MultiCell($w[3] - 2, 5, $kategori, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + 1, $y + 2);
    $pdf->MultiCell($w[4] - 2, 5, $lokasi, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y);
    $pdf->Cell($w[5], $tinggi, $tgl_pinjam, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y);
    $pdf->Cell($w[6], $tinggi, $tgl_kembali, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6], $y);
    $pdf->Cell($w[7], $tinggi, $status, 0, 0, 'C');

    /* ================= BARIS BARU ================= */

    $pdf->SetY($y + $tinggi);
}

/* ================= FOOTER ================= */

$pdf->Ln(10);

$pdf->SetFont('Arial', '', 11);

$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');

$pdf->Ln(12);

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

/* ================= OUTPUT ================= */

$pdf->Output();

exit;
