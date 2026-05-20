<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

ob_start();
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA ==================== //
$search         = $_GET['search'] ?? '';
$status         = $_GET['status'] ?? '';
$kategoriFilter = $_GET['kategori'] ?? '';
$dari           = $_GET['dari'] ?? '';
$sampai         = $_GET['sampai'] ?? '';

$where = [];
if ($search != '') {
    $where[] = "(perawatan.nama_aset LIKE '%$search%' OR perawatan.teknisi LIKE '%$search%' OR aset.lokasi LIKE '%$search%')";
}
if ($status != '') $where[] = "perawatan.status = '$status'";
if ($kategoriFilter != '') $where[] = "aset.kategori_aset = '$kategoriFilter'";
if ($dari != '')   $where[] = "perawatan.tanggal >= '$dari'";
if ($sampai != '') $where[] = "perawatan.tanggal <= '$sampai'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ==================== QUERY DATA ==================== //
$sql = "
    SELECT perawatan.*, aset.lokasi, aset.kategori_aset 
    FROM perawatan 
    LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
    $whereSQL 
    ORDER BY perawatan.tanggal DESC
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

// ==================== TABEL HEADER (TOTAL 277mm, 8 Kolom) ==================== //
$w = [10, 55, 30, 40, 50, 27, 35, 30];
$header = ['No', 'Nama Aset', 'Kategori', 'Lokasi Ruang', 'Teknisi Bertugas', 'Tgl Rawat', 'Jadwal Kalibrasi', 'Status'];

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

// ==================== TABEL ISI (MULTICELL) ==================== //
$no = 1;
if (mysqli_num_rows($res) > 0) {
    while ($r = mysqli_fetch_assoc($res)) {

        $nama_aset = $r['nama_aset'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $lokasi    = $r['lokasi'] ?? '-';
        $teknisi   = $r['teknisi'] ?? '-';
        $tanggal   = date('d/m/Y', strtotime($r['tanggal']));
        $status    = $r['status'] ?? '-';

        // Konversi Logika Kalibrasi PDF
        $tgl_berikutnya = $r['tanggal_kalibrasi_berikutnya'] ?? '';
        if ($tgl_berikutnya && $tgl_berikutnya != '0000-00-00') {
            $kalibrasi_tgl = date('d/m/Y', strtotime($tgl_berikutnya));
            $selisih_detik = strtotime($tgl_berikutnya) - strtotime('today');
            $selisih_hari = floor($selisih_detik / (60 * 60 * 24));

            if ($selisih_hari <= 7 && $selisih_hari >= 0) {
                $kalibrasi_status = "H-$selisih_hari";
            } elseif ($selisih_hari < 0) {
                $lewat = abs($selisih_hari);
                $kalibrasi_status = "Lewat $lewat Hr";
            } else {
                $kalibrasi_status = "Aman";
            }
            $kalibrasi_cetak = $kalibrasi_tgl . "\n[" . $kalibrasi_status . "]";
        } else {
            $kalibrasi_cetak = "-";
        }

        $maxLine = max(
            2,
            ceil(strlen($nama_aset) / 32),
            ceil(strlen($lokasi) / 24),
            ceil(strlen($teknisi) / 30)
        );
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderPerawatan($pdf, $w, $header);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Rect($x, $y, $w[0], $tinggi);
        $pdf->Rect($x + $w[0], $y, $w[1], $tinggi);
        $pdf->Rect($x + $w[0] + $w[1], $y, $w[2], $tinggi);
        $pdf->Rect($x + $w[0] + $w[1] + $w[2], $y, $w[3], $tinggi);
        $pdf->Rect($x + $w[0] + $w[1] + $w[2] + $w[3], $y, $w[4], $tinggi);
        $pdf->Rect($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y, $w[5], $tinggi);
        $pdf->Rect($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y, $w[6], $tinggi);
        $pdf->Rect($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6], $y, $w[7], $tinggi);

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w[0], 5, $no++, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + 1, $y + 2);
        $pdf->MultiCell($w[1] - 2, 5, $nama_aset, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + 1, $y + 2);
        $pdf->MultiCell($w[2] - 2, 5, $kategori, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + 1, $y + 2);
        $pdf->MultiCell($w[3] - 2, 5, $lokasi, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + 1, $y + 2);
        $pdf->MultiCell($w[4] - 2, 5, $teknisi, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y + 2);
        $pdf->Cell($w[5], 5, $tanggal, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + 1, $y + 2);
        $pdf->MultiCell($w[6] - 2, 5, $kalibrasi_cetak, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6], $y + 2);
        $pdf->Cell($w[7], 5, $status, 0, 0, 'C');

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
