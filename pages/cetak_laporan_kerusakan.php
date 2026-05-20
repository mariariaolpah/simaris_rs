<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA SINKRON ==================== //
$search         = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$status         = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$dari           = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
$sampai         = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

$where = [];
// Pencarian disinkronkan dengan kolom teknisi
if ($search != '')  $where[] = "(kerusakan.nama_aset LIKE '%$search%' OR kerusakan.keterangan LIKE '%$search%' OR kerusakan.pelapor LIKE '%$search%' OR kerusakan.teknisi LIKE '%$search%' OR aset.lokasi LIKE '%$search%')";
if ($status != '')  $where[] = "kerusakan.status = '$status'";
if ($kategoriFilter != '') $where[] = "aset.kategori_aset = '$kategoriFilter'";
if ($dari != '')    $where[] = "kerusakan.tanggal >= '$dari'";
if ($sampai != '')  $where[] = "kerusakan.tanggal <= '$sampai'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ==================== QUERY DATA ==================== //
$sql = "
    SELECT kerusakan.*, aset.lokasi, aset.kategori_aset 
    FROM kerusakan 
    LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
    $whereSQL 
    ORDER BY kerusakan.tanggal DESC, kerusakan.id DESC
";
$res = mysqli_query($koneksi, $sql);

if (mysqli_num_rows($res) == 0) {
    echo "<script>alert('Tidak ada data yang ditemukan untuk dicetak!'); window.close();</script>";
    exit;
}

// ==================== SETUP PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

// ==================== KOP SURAT INSTANSI ==================== //
$y = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');
if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 15, $y, 22);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 260, $y, 22);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(8);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'LAPORAN REKAPITULASI KERUSAKAN ASET', 0, 1, 'C');

$subHeader = [];
if ($kategoriFilter != '') $subHeader[] = "Kategori: " . $kategoriFilter;
if ($dari != '' && $sampai != '') $subHeader[] = "Periode: " . date('d/m/Y', strtotime($dari)) . " s/d " . date('d/m/Y', strtotime($sampai));

if (count($subHeader) > 0) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 5, implode(' | ', $subHeader), 0, 1, 'C');
}
$pdf->Ln(4);

// ==================== HEADER TABEL ==================== //
// Total Lebar: 10+40+30+20+35+25+22+25+70 = 277 mm
$w = [10, 40, 30, 20, 35, 25, 22, 25, 70];
$header = ['No', 'Nama Aset', 'Lokasi Ruang', 'Kategori', 'Pelapor', 'Teknisi', 'Tanggal', 'Status', 'Rincian'];

function cetakHeaderLaporanKerusakan($pdf, $w, $header)
{
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(72, 201, 176);
    $pdf->SetTextColor(255);
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 9, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0);
}

cetakHeaderLaporanKerusakan($pdf, $w, $header);

// ==================== LOOPING DATA TABEL ==================== //
$no = 1;
while ($r = mysqli_fetch_assoc($res)) {

    $nama_aset  = $r['nama_aset'] ?? '-';
    $lokasi     = $r['lokasi'] ?? '-';
    $kategori   = $r['kategori_aset'] ?? '-';
    $tanggal    = date('d-m-Y', strtotime($r['tanggal']));
    $status     = $r['status'] ?? '-';
    $keterangan = $r['keterangan'] ?? '-';
    $teknisi    = $r['teknisi'] ?? '-'; // Data Teknisi

    $pelapor_asli  = $r['pelapor'] ?? '-';
    $sumber_label  = (isset($r['sumber']) && $r['sumber'] == 'App User') ? 'App User' : 'Admin';
    $pelapor_cetak = $pelapor_asli . "\n[" . $sumber_label . "]";

    // Hitung baris tertinggi untuk sel MultiCell
    $maxLine = max(
        2,
        ceil(strlen($nama_aset) / 20),
        ceil(strlen($lokasi) / 15),
        ceil(strlen($keterangan) / 45)
    );
    $tinggi = ($maxLine * 5) + 4;

    if ($pdf->GetY() + $tinggi > 185) {
        $pdf->AddPage();
        cetakHeaderLaporanKerusakan($pdf, $w, $header);
    }

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Gambar bingkai kotak untuk 9 kolom
    $currentX = $x;
    for ($i = 0; $i < count($w); $i++) {
        $pdf->Rect($currentX, $y, $w[$i], $tinggi);
        $currentX += $w[$i];
    }

    // Isi Data ke dalam Tabel PDF
    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($w[0], 5, $no++, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + 1, $y + 2);
    $pdf->MultiCell($w[1] - 2, 5, $nama_aset, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + 1, $y + 2);
    $pdf->MultiCell($w[2] - 2, 5, $lokasi, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2], $y + 2);
    $pdf->Cell($w[3], 5, $kategori, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + 1, $y + 2);
    $pdf->MultiCell($w[4] - 2, 5, $pelapor_cetak, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + 1, $y + 2);
    $pdf->MultiCell($w[5] - 2, 5, $teknisi, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y + 2);
    $pdf->Cell($w[6], 5, $tanggal, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6], $y + 2);
    $pdf->Cell($w[7], 5, $status, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + $w[7] + 1, $y + 2);
    $pdf->MultiCell($w[8] - 2, 5, $keterangan, 0, 'L');

    $pdf->SetY($y + $tinggi);
}

// ==================== AREA TANDA TANGAN ==================== //
if ($pdf->GetY() > 155) {
    $pdf->AddPage();
}

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');
$pdf->Ln(4);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'], 0, 1, 'R');

ob_end_clean();
$pdf->Output('I', 'Laporan_Kerusakan_' . date('Ymd_His') . '.pdf');
exit;
