<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA ==================== //
$search  = $_GET['search'] ?? '';
$kondisi = $_GET['kondisi'] ?? '';
$dari    = $_GET['dari'] ?? '';
$sampai  = $_GET['sampai'] ?? '';

$where = [];
if ($search != '')  $where[] = "(nama_aset LIKE '%$search%' OR jenis LIKE '%$search%' OR tipe_aset LIKE '%$search%' OR lokasi LIKE '%$search%')";
if ($kondisi != '') $where[] = "kondisi = '$kondisi'";
if ($dari != '')    $where[] = "tanggal_masuk >= '$dari'";
if ($sampai != '')  $where[] = "tanggal_masuk <= '$sampai'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ==================== QUERY DATA ==================== //
$sql = "SELECT * FROM aset $whereSQL ORDER BY id_aset DESC";
$res = mysqli_query($koneksi, $sql);

// ==================== PENANDATANGAN ==================== //
$signName = $_SESSION['nama_pengguna'] ?? 'Kepala Rumah Sakit';
$signNip  = '';

$qSig = @mysqli_query($koneksi, "SELECT * FROM pejabat_ttd WHERE jabatan LIKE '%Kepala%' LIMIT 1");
if ($qSig && mysqli_num_rows($qSig)) {
    $rSig = mysqli_fetch_assoc($qSig);
    if (!empty($rSig['nama_pejabat'])) $signName = $rSig['nama_pejabat'];
    if (!empty($rSig['nip'])) $signNip = $rSig['nip'];
}

// ==================== PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ==================== HEADER ==================== //
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft))  $pdf->Image($logoLeft, 15, 8, 25);
if (file_exists($logoRight)) $pdf->Image($logoRight, 252, 8, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->SetY(32);
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'LAPORAN DATA ASET', 0, 1, 'C');
$pdf->Ln(4);

// ==================== TABLE HEADER ==================== //
$widths = [10, 55, 30, 35, 45, 25, 30, 25];
$header = ['No', 'Nama Aset', 'Jenis', 'Tipe', 'Lokasi', 'Kondisi', 'Tanggal', 'Gambar'];

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

// biar posisi CENTER tabel
$tableWidth = array_sum($widths);
$pdf->SetX((297 - $tableWidth) / 2);

foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 10, $col, 1, 0, 'C', true);
}
$pdf->Ln();

// ==================== ISI ==================== //
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

$no = 1;

while ($r = mysqli_fetch_assoc($res)) {

    $pdf->SetX((297 - $tableWidth) / 2);

    $pdf->Cell($widths[0], 10, $no++, 1, 0, 'C');
    $pdf->Cell($widths[1], 10, substr($r['nama_aset'], 0, 25), 1);
    $pdf->Cell($widths[2], 10, substr($r['jenis'], 0, 15), 1);
    $pdf->Cell($widths[3], 10, substr($r['tipe_aset'], 0, 15), 1);
    $pdf->Cell($widths[4], 10, substr($r['lokasi'], 0, 20), 1);
    $pdf->Cell($widths[5], 10, $r['kondisi'], 1, 0, 'C');
    $pdf->Cell($widths[6], 10, date('d/m/Y', strtotime($r['tanggal_masuk'])), 1, 0, 'C');

    // ================= GAMBAR =================
    $imgPath = __DIR__ . '/../assets/dokumen/' . $r['dokumen'];

    if (!empty($r['dokumen']) && file_exists($imgPath)) {

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Cell($widths[7], 10, '', 1, 1);

        // gambar di tengah cell
        $pdf->Image($imgPath, $x + 3, $y + 1, 18, 8);
    } else {
        $pdf->Cell($widths[7], 10, '-', 1, 1, 'C');
    }
}

// ==================== TTD ==================== //
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);

$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, $signName, 0, 1, 'R');

if ($signNip != '') {
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, 'NIP. ' . $signNip, 0, 1, 'R');
}

$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'R');

$pdf->Output();
exit;
