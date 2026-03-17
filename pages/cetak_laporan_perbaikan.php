<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require_once(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA ==================== //
$search  = $_GET['search'] ?? '';
$dari    = $_GET['dari'] ?? '';
$sampai  = $_GET['sampai'] ?? '';

$where = [];
if ($search != '')  $where[] = "(nama_aset LIKE '%$search%' OR keterangan LIKE '%$search%' OR status LIKE '%$search%')";
if ($dari != '')    $where[] = "tanggal >= '$dari'";
if ($sampai != '')  $where[] = "tanggal <= '$sampai'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ==================== QUERY DATA ==================== //
$where = [];
if ($search != '')  $where[] = "(nama_aset LIKE '%$search%' OR keterangan LIKE '%$search%' OR status LIKE '%$search%')";
if ($dari != '')    $where[] = "tanggal >= '$dari'";
if ($sampai != '')  $where[] = "tanggal <= '$sampai'";

// selalu aman pakai '1' supaya AND berikutnya bisa dipakai
$whereSQL = count($where) ? implode(' AND ', $where) : '1';

$sql = "SELECT * FROM kerusakan WHERE $whereSQL AND (status LIKE '%Perbaikan%' OR status LIKE '%Selesai%') ORDER BY tanggal DESC";
$res = mysqli_query($koneksi, $sql);

if (!$res || mysqli_num_rows($res) == 0) {
    echo "<script>alert('Tidak ada data perbaikan ditemukan!'); window.close();</script>";
    exit;
}

// ==================== PENANDATANGAN ==================== //
$signName = $_SESSION['nama_pengguna'] ?? 'Kepala Rumah Sakit';
$signNip  = '';

$qSig = @mysqli_query($koneksi, "SELECT * FROM pejabat_ttd WHERE jabatan LIKE '%Kepala%' LIMIT 1");
if ($qSig && mysqli_num_rows($qSig)) {
    $rSig = mysqli_fetch_assoc($qSig);
    if (!empty($rSig['nama_pejabat'])) $signName = $rSig['nama_pejabat'];
    if (!empty($rSig['nip'])) $signNip = $rSig['nip'];
}

// ==================== SETUP PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ==================== HEADER DENGAN LOGO ==================== //
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');
if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 15, 8, 25);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 252, 8, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(8);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'LAPORAN DATA PERBAIKAN ASET', 0, 1, 'C');
$pdf->Ln(4);

// ==================== HEADER TABEL ==================== //
$widths = [12, 90, 50, 40, 90];
$header = ['No', 'Nama Aset', 'Status', 'Tanggal', 'Keterangan'];

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 11, $col, 1, 0, 'C', true);
}
$pdf->Ln();

// ==================== ISI DATA ==================== //
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);
$no = 1;

while ($r = mysqli_fetch_assoc($res)) {
    $pdf->Cell($widths[0], 9, $no++, 1, 0, 'C');
    $pdf->Cell($widths[1], 9, substr($r['nama_aset'], 0, 45), 1);
    $pdf->Cell($widths[2], 9, substr($r['status'], 0, 30), 1);
    $pdf->Cell($widths[3], 9, $r['tanggal'], 1);
    $pdf->Cell($widths[4], 9, substr($r['keterangan'], 0, 60), 1, 1);
}

// ==================== TANDA TANGAN ==================== //
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(8);

$ttdPath = realpath(__DIR__ . '/../assets/img/ttd_kepala.png');
if ($ttdPath && file_exists($ttdPath)) {
    $yPos = $pdf->GetY();
    $pdf->Image($ttdPath, 230, $yPos, 35);
    $pdf->Ln(25);
}

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, $signName, 0, 1, 'R');
if ($signNip != '') {
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 6, 'NIP. ' . $signNip, 0, 1, 'R');
}

$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['id_pengguna'], 0, 1, 'R');

// ==================== OUTPUT PDF ==================== //
$pdf->Output('I', 'Laporan_Perbaikan_Aset_' . date('Ymd_His') . '.pdf');
exit;
