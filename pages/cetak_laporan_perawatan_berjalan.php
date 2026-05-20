<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

ob_start();
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA SINKRON (MENGGUNAKAN ALIAS) ==================== //
$search         = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$teknisi        = isset($_GET['teknisi']) ? mysqli_real_escape_string($koneksi, $_GET['teknisi']) : '';
$kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$dari           = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
$sampai         = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

// Menggunakan alias p untuk perawatan, dan a untuk aset agar query stabil
$where = ["p.status IN ('Belum Dimulai','Sedang Proses')"];
if ($search != '')  $where[] = "(p.nama_aset LIKE '%$search%' OR a.lokasi LIKE '%$search%')";
if ($teknisi != '') $where[] = "p.teknisi LIKE '%$teknisi%'";
if ($kategoriFilter != '') $where[] = "a.kategori_aset = '$kategoriFilter'";
if ($dari != '')    $where[] = "p.tanggal >= '$dari'";
if ($sampai != '')  $where[] = "p.tanggal <= '$sampai'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ==================== QUERY DATA (SINKRON COLLATION DENGAN perawatan.php) ==================== //
$sql = "
    SELECT p.*, a.lokasi, a.kategori_aset 
    FROM perawatan p
    LEFT JOIN aset a ON p.nama_aset COLLATE utf8mb4_general_ci = a.nama_aset COLLATE utf8mb4_general_ci
    $whereSQL 
    ORDER BY p.tanggal DESC
";
$res = mysqli_query($koneksi, $sql);

// ==================== SETUP PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

// ==================== KOP SURAT ==================== //
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');
if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 15, 8, 22);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 260, 8, 22);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(8);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN REKAPITULASI JADWAL PERAWATAN ASET', 0, 1, 'C');

$subHeader = [];
if ($kategoriFilter != '') $subHeader[] = "Kategori: " . $kategoriFilter;
if ($dari != '' && $sampai != '') $subHeader[] = "Periode: " . date('d/m/Y', strtotime($dari)) . " s/d " . date('d/m/Y', strtotime($sampai));

if (count($subHeader) > 0) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 5, implode(' | ', $subHeader), 0, 1, 'C');
}
$pdf->Ln(4);

// ==================== TABEL HEADER ==================== //
$w = [10, 60, 30, 45, 60, 35, 37];
$header = ['No', 'Nama Aset', 'Kategori', 'Lokasi Ruang', 'Teknisi Bertugas', 'Tgl Perawatan', 'Status'];

function cetakHeaderPerawatan($pdf, $w, $header)
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

cetakHeaderPerawatan($pdf, $w, $header);

// ==================== TABEL ISI ==================== //
$no = 1;
if ($res && mysqli_num_rows($res) > 0) {
    while ($r = mysqli_fetch_assoc($res)) {

        $nama_aset = $r['nama_aset'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $lokasi    = $r['lokasi'] ?? '-';
        $teknisi   = $r['teknisi'] ?? '-';
        $tanggal   = !empty($r['tanggal']) ? date('d/m/Y', strtotime($r['tanggal'])) : '-';

        // Logika teks status disesuaikan agar sama ringkasnya dengan perawatan.php
        $status = $r['status'] ?? '-';
        if ($status == 'Sedang Proses') {
            $status = 'Proses';
        }

        $maxLine = max(
            1,
            ceil(strlen($nama_aset) / 32),
            ceil(strlen($lokasi) / 24),
            ceil(strlen($teknisi) / 32)
        );
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderPerawatan($pdf, $w, $header);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $currentX = $x;
        for ($i = 0; $i < count($w); $i++) {
            $pdf->Rect($currentX, $y, $w[$i], $tinggi);
            $currentX += $w[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w[0], 5, $no++, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + 1, $y + 2);
        $pdf->MultiCell($w[1] - 2, 5, $nama_aset, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1], $y + 2);
        $pdf->Cell($w[2], 5, $kategori, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + 1, $y + 2);
        $pdf->MultiCell($w[3] - 2, 5, $lokasi, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + 1, $y + 2);
        $pdf->MultiCell($w[4] - 2, 5, $teknisi, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y + 2);
        $pdf->Cell($w[5], 5, $tanggal, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y + 2);
        $pdf->Cell($w[6], 5, $status, 0, 0, 'C');

        $pdf->SetY($y + $tinggi);
    }
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(array_sum($w), 10, 'Tidak ada data perawatan yang ditemukan.', 1, 1, 'C');
}

// ==================== TANDA TANGAN ==================== //
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
$pdf->Output('I', 'Laporan_Perawatan_' . date('Ymd_His') . '.pdf');
exit;
