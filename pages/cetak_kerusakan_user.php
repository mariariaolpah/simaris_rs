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

// hindari output sebelum PDF
ob_start();

// load koneksi & fpdf
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/fpdf.php';

// ================= QUERY DATA KERUSAKAN ================= //
$sql = "SELECT * FROM kerusakan ORDER BY id DESC";
$res = mysqli_query($koneksi, $sql);

// ================= SETUP PDF ===================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();

// LOGO
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 10, 8, 25);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 270, 8, 25);

// HEADER
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->SetY(34);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'LAPORAN DATA KERUSAKAN ASET', 0, 1, 'C');
$pdf->Ln(4);

// ================= TABEL HEADER =================== //
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

$header = ['No', 'Nama Aset', 'Tanggal', 'Status', 'Keterangan'];
$widths = [12, 80, 35, 35, 110];

foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 10, $col, 1, 0, 'C', true);
}
$pdf->Ln();

// ================= TABEL ISI ===================== //
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0);

$no = 1;
while ($r = mysqli_fetch_assoc($res)) {

    $nama       = $r['nama_aset'] ?? '-';
    $tanggal    = $r['tanggal'] ?? '-';
    $status     = $r['status'] ?? '-';
    $keterangan = $r['keterangan'] ?? '-';

    $pdf->Cell($widths[0], 8, $no++, 1, 0, 'C');
    $pdf->Cell($widths[1], 8, $nama, 1);
    $pdf->Cell($widths[2], 8, $tanggal, 1);
    $pdf->Cell($widths[3], 8, $status, 1);
    $pdf->Cell($widths[4], 8, $keterangan, 1, 1);
}

// ================= TANDA TANGAN ================= //
$pdf->Ln(8);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(18);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');

// FOOTER
$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . ($_SESSION['nama_pengguna'] ?? '-'), 0, 1, 'R');

// ================= OUTPUT ========================== //
ob_end_clean();
$pdf->Output('I', 'Laporan_Kerusakan_' . date('Ymd_His') . '.pdf');
exit;
