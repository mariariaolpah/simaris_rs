<?php
session_start();

// pastikan login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// izinkan admin & user
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'user'])) {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Akses ditolak!</h2>";
    exit;
}

ob_start();

// koneksi & fpdf
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/fpdf.php';

// ================= QUERY DATA USER ================= //
$sql = "SELECT * FROM pengguna ORDER BY id_pengguna DESC";
$res = mysqli_query($koneksi, $sql);

// ================= SETUP PDF ===================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// LOGO
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 10, 8, 25);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 270, 8, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->SetY(34);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'LAPORAN DATA USER', 0, 1, 'C');
$pdf->Ln(4);

// ================= TABEL HEADER =================== //
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

// header kolom user
$header = ['No', 'Nama User', 'Username', 'Level', 'Role', 'Status'];
$widths = [12, 65, 55, 30, 35, 35];

foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 10, $col, 1, 0, 'C', true);
}
$pdf->Ln();

// ================= ISI TABEL ===================== //
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

$no = 1;
while ($r = mysqli_fetch_assoc($res)) {

    $pdf->Cell($widths[0], 8, $no++, 1, 0, 'C');
    $pdf->Cell($widths[1], 8, $r['nama_pengguna'], 1);
    $pdf->Cell($widths[2], 8, $r['username'], 1);
    $pdf->Cell($widths[3], 8, $r['level'], 1, 0, 'C');
    $pdf->Cell($widths[4], 8, ucfirst($r['role']), 1, 0, 'C');
    $pdf->Cell($widths[5], 8, $r['status'] ?? 'aktif', 1, 1, 'C');
}

// ================= TTD & FOOTER ========================== //
$pdf->Ln(8);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(18);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');

$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . ($_SESSION['nama_pengguna'] ?? '-'), 0, 1, 'R');

ob_end_clean();
$pdf->Output('I', 'Laporan_Data_User_' . date('Ymd_His') . '.pdf');
exit;
