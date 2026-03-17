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

// Statistik singkat
$statQ = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) AS total_all,
        SUM(kondisi='Baik') AS total_baik,
        SUM(kondisi='Rusak') AS total_rusak,
        SUM(kondisi='Perlu Perawatan') AS total_perawatan
    FROM aset $whereSQL
");
$stat = mysqli_fetch_assoc($statQ);

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

// Logo kiri & kanan
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');
if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 15, 8, 25);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 252, 8, 25);

// ==================== HEADER ==================== //
// Logo kiri & kanan
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');
if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 15, 8, 25);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 252, 8, 25);

// Nama Rumah Sakit
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

// Alamat
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

// Tambahkan jarak sebelum judul laporan
$pdf->SetY(32); // posisi vertikal dari atas halaman
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'LAPORAN DATA ASET ', 0, 1, 'C');
$pdf->Ln(4);

// ==================== TABEL HEADER ==================== //
$widths = [12, 60, 40, 50, 50, 40, 30]; // lebar kolom sedikit lebih besar
$header = ['No', 'Nama Aset', 'Jenis', 'Tipe Aset', 'Lokasi', 'Kondisi', 'Tanggal Masuk'];

$pdf->SetFont('Arial', 'B', 11); // font header lebih besar
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

foreach ($header as $i => $col) {
    $pdf->Cell($widths[$i], 11, $col, 1, 0, 'C', true); // tinggi header 11
}
$pdf->Ln();

// ==================== TABEL ISI ==================== //
$pdf->SetFont('Arial', '', 10); // font isi lebih besar
$pdf->SetTextColor(0);
$no = 1;

while ($r = mysqli_fetch_assoc($res)) {
    $pdf->Cell($widths[0], 9, $no++, 1, 0, 'C'); // tinggi baris 9
    $pdf->Cell($widths[1], 9, substr($r['nama_aset'], 0, 35), 1);
    $pdf->Cell($widths[2], 9, substr($r['jenis'], 0, 25), 1);
    $pdf->Cell($widths[3], 9, substr($r['tipe_aset'], 0, 30), 1);
    $pdf->Cell($widths[4], 9, substr($r['lokasi'], 0, 30), 1);
    $pdf->Cell($widths[5], 9, substr($r['kondisi'], 0, 20), 1);
    $pdf->Cell($widths[6], 9, $r['tanggal_masuk'], 1, 1);
}

// ==================== TANDA TANGAN ==================== //
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(8);

// --- Coba tampilkan tanda tangan di posisi yang aman (kanan bawah)
$ttdPath = realpath(__DIR__ . '/../assets/img/ttd_kepala.png');
if ($ttdPath && file_exists($ttdPath)) {
    $yPos = $pdf->GetY(); // ambil posisi terakhir
    $pdf->Image($ttdPath, 230, $yPos, 35); // 230 = kanan bawah
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
$pdf->Output('I', 'Laporan_Aset_RS_' . date('Ymd_His') . '.pdf');
exit;
