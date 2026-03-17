<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// Hindari error output sebelum PDF
ob_start();

require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA ==================== //
$search  = $_GET['search'] ?? '';
$status  = $_GET['status'] ?? '';
$role    = $_GET['role'] ?? '';

$where = [];
if ($search != '')  $where[] = "(nama_pengguna LIKE '%" . mysqli_real_escape_string($koneksi, $search) . "%' OR username LIKE '%" . mysqli_real_escape_string($koneksi, $search) . "%')";
if ($status != '')  $where[] = "status = '" . mysqli_real_escape_string($koneksi, $status) . "'";
if ($role != '')    $where[] = "role = '" . mysqli_real_escape_string($koneksi, $role) . "'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ==================== QUERY DATA ==================== //
$sql = "SELECT * FROM pengguna $whereSQL ORDER BY nama_pengguna ASC";
$res = mysqli_query($koneksi, $sql);

// ==================== SETUP PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// ==================== HEADER (DENGAN LOGO) ==================== //
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');
if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 15, 8, 25);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 252, 8, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->SetY(32);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'LAPORAN MANAJEMEN USER', 0, 1, 'C');
$pdf->Ln(4);

// ==================== TABEL HEADER ==================== //
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);
$header = ['No', 'Nama Pengguna', 'Username', 'Level', 'Role', 'Status'];
$widths = [12, 60, 60, 40, 40, 40];
foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 11, $col, 1, 0, 'C', true);
}
$pdf->Ln();

// ==================== TABEL ISI ==================== //
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);
$no = 1;

while ($r = mysqli_fetch_assoc($res)) {
    $pdf->Cell($widths[0], 9, $no++, 1, 0, 'C');
    $pdf->Cell($widths[1], 9, $r['nama_pengguna'], 1);
    $pdf->Cell($widths[2], 9, $r['username'], 1);
    $pdf->Cell($widths[3], 9, $r['level'], 1);
    $pdf->Cell($widths[4], 9, $r['role'], 1);
    $pdf->Cell($widths[5], 9, $r['status'], 1, 1);
}

// ==================== TANDA TANGAN ==================== //
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(8);

// --- Hapus atau ganti gambar tanda tangan kepala RS jika tidak ada
// $ttdPath = realpath(__DIR__ . '/../assets/img/ttd_kepala.png');
// if ($ttdPath && file_exists($ttdPath)) {
//     $yPos = $pdf->GetY();
//     $pdf->Image($ttdPath, 230, $yPos, 35);
//     $pdf->Ln(25);
// }

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R'); // ganti Kepala Rumah Sakit jadi Administrator
$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'], 0, 1, 'R');

// ==================== OUTPUT PDF ==================== //
ob_end_clean();
$pdf->Output('I', 'Laporan_Perawatan_' . date('Ymd_His') . '.pdf');
exit;
