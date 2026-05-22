<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

ob_start();
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

$search         = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$kondisi        = isset($_GET['kondisi']) ? mysqli_real_escape_string($koneksi, $_GET['kondisi']) : '';
$kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$dari           = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
$sampai         = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

$where = [];
if ($search !== '') $where[] = "(a.nama_aset LIKE '%$search%' OR a.lokasi LIKE '%$search%' OR ad.auditor LIKE '%$search%' OR ad.keterangan LIKE '%$search%')";
if ($kondisi !== '') $where[] = "ad.kondisi_fisik = '$kondisi'";
if ($kategoriFilter !== '') $where[] = "a.kategori_aset = '$kategoriFilter'";
if ($dari !== '') $where[] = "ad.tanggal_audit >= '$dari'";
if ($sampai !== '') $where[] = "ad.tanggal_audit <= '$sampai'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// MENGGUNAKAN JOIN AGAR CETAKAN PDF SINKRON DENGAN WEB DAN MASTER DATA
$sql = "
    SELECT ad.*, a.nama_aset, a.lokasi, a.kategori_aset 
    FROM audit_fisik ad 
    JOIN aset a ON ad.id_aset = a.id_aset 
    $whereSQL 
    ORDER BY ad.tanggal_audit DESC
";
$res = mysqli_query($koneksi, $sql);

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

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
$pdf->Cell(0, 10, 'LAPORAN REKAPITULASI AUDIT FISIK ASET', 0, 1, 'C');

$subHeader = [];
if ($kategoriFilter != '') $subHeader[] = "Kategori: " . $kategoriFilter;
if ($kondisi != '') $subHeader[] = "Kondisi: " . $kondisi;
if ($dari != '' && $sampai != '') $subHeader[] = "Periode: " . date('d/m/Y', strtotime($dari)) . " s/d " . date('d/m/Y', strtotime($sampai));

if (count($subHeader) > 0) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 5, implode(' | ', $subHeader), 0, 1, 'C');
}
$pdf->Ln(4);

// Kolom "Bukti Fisik" ditambahkan. Total Array Widths harus 277mm untuk kertas landscape.
$w = [10, 38, 22, 25, 25, 20, 20, 25, 92];
$header = ['No', 'Nama Aset', 'Kategori', 'Lokasi', 'Auditor', 'Tgl Audit', 'Kondisi', 'Bukti Fisik', 'Keterangan Tambahan'];

function cetakHeaderAudit($pdf, $w, $header)
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

cetakHeaderAudit($pdf, $w, $header);

$no = 1;
if ($res && mysqli_num_rows($res) > 0) {
    while ($r = mysqli_fetch_assoc($res)) {

        $nama_aset  = $r['nama_aset'] ?? '-';
        $kategori   = $r['kategori_aset'] ?? '-';
        $lokasi     = $r['lokasi'] ?? '-';
        $auditor    = $r['auditor'] ?? '-';
        $tanggal    = !empty($r['tanggal_audit']) ? date('d/m/Y', strtotime($r['tanggal_audit'])) : '-';
        $kondisi    = $r['kondisi_fisik'] ?? '-';
        $keterangan = $r['keterangan'] ?? '-';

        // Hitung Tinggi Baris (Penyediaan ruang untuk gambar jika ada)
        $maxLine = max(
            3, // Pastikan tinggi baris cukup lebar untuk image (15mm)
            ceil(strlen($nama_aset) / 22),
            ceil(strlen($keterangan) / 60)
        );
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderAudit($pdf, $w, $header);
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
        $pdf->MultiCell($w[4] - 2, 5, $auditor, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y + 2);
        $pdf->Cell($w[5], 5, $tanggal, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y + 2);
        $pdf->Cell($w[6], 5, $kondisi, 0, 0, 'C');

        // Render Gambar ke dalam sel Bukti Fisik
        $imgPath = __DIR__ . '/../assets/img/' . ($r['gambar_rusak'] ?? '');
        $imgColStart = $x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6];

        if (!empty($r['gambar_rusak']) && file_exists($imgPath)) {
            // Posisi tengah untuk render gambar
            $imgWidth = 18;
            $imgHeight = 14;
            $imgX = $imgColStart + (($w[7] - $imgWidth) / 2);
            $imgY = $y + (($tinggi - $imgHeight) / 2);
            $pdf->Image($imgPath, $imgX, $imgY, $imgWidth, $imgHeight);
        } else {
            $pdf->SetXY($imgColStart, $y + 2);
            $pdf->Cell($w[7], $tinggi - 4, '-', 0, 0, 'C');
        }

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + $w[7] + 1, $y + 2);
        $pdf->MultiCell($w[8] - 2, 5, $keterangan, 0, 'L');

        $pdf->SetY($y + $tinggi);
    }
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(array_sum($w), 10, 'Tidak ada riwayat audit yang ditemukan.', 1, 1, 'C');
}

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
$pdf->Output('I', 'Laporan_Audit_Fisik_' . date('Ymd_His') . '.pdf');
exit;
