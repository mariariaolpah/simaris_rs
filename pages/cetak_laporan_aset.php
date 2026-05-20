<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA ==================== //
$search  = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$kondisi = isset($_GET['kondisi']) ? mysqli_real_escape_string($koneksi, $_GET['kondisi']) : '';
$dari    = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
$sampai  = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

$where = [];
if ($search != '')  $where[] = "(nama_aset LIKE '%$search%' OR jenis LIKE '%$search%' OR tipe_aset LIKE '%$search%' OR lokasi LIKE '%$search%')";
if ($kondisi != '') $where[] = "kondisi = '$kondisi'";
if ($dari != '')    $where[] = "tanggal_masuk >= '$dari'";
if ($sampai != '')  $where[] = "tanggal_masuk <= '$sampai'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT * FROM aset $whereSQL ORDER BY id_aset DESC";
$res = mysqli_query($koneksi, $sql);

if (mysqli_num_rows($res) == 0) {
    echo "<script>alert('Tidak ada data aset yang cocok untuk dicetak!'); window.close();</script>";
    exit;
}

// ==================== SETUP LAYOUT PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

$y = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');
if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 15, $y, 22);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 260, $y, 22);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, $y + 3);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'LAPORAN REKAPITULASI INVENTARIS ASET', 0, 1, 'C');

if ($dari != '' && $sampai != '') {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 5, 'Periode Registrasi: ' . date('d/m/Y', strtotime($dari)) . ' s/d ' . date('d/m/Y', strtotime($sampai)), 0, 1, 'C');
}
$pdf->Ln(4);

// ==================== DAFTAR 12 KOLOM (Total 277mm) ==================== //
$w = [8, 37, 20, 24, 24, 30, 18, 26, 30, 15, 22, 23];
$header = ['No', 'Nama Aset', 'Kategori', 'Jenis', 'Tipe', 'Lokasi', 'Kondisi', 'Asal Usul', 'Harga (Rp)', 'Umur', 'Tgl Masuk', 'Foto'];

function cetakHeaderTabelAsetLengkap($pdf, $w, $header)
{
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(72, 201, 176);
    $pdf->SetTextColor(255);
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 9, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 7); // Huruf dikecilkan agar teks muat
    $pdf->SetTextColor(0);
}

cetakHeaderTabelAsetLengkap($pdf, $w, $header);

// ==================== LOOPING DATA ==================== //
$no = 1;
while ($r = mysqli_fetch_assoc($res)) {

    $nama_aset  = $r['nama_aset'] ?? '-';
    $kategori   = $r['kategori_aset'] ?? '-';
    $jenis      = $r['jenis'] ?? '-';
    $tipe       = $r['tipe_aset'] ?? '-';
    $lokasi     = $r['lokasi'] ?? '-';
    $kondisi    = $r['kondisi'] ?? '-';
    $asal       = $r['asal_usul'] ?? '-';
    $harga      = number_format($r['harga'], 0, ',', '.');
    $umur       = ($r['umur_ekonomis'] > 0) ? $r['umur_ekonomis'] . ' Th' : '-';
    $tgl        = date('d/m/Y', strtotime($r['tanggal_masuk']));

    $maxLine = max(
        2,
        ceil(strlen($nama_aset) / 25),
        ceil(strlen($jenis) / 15),
        ceil(strlen($tipe) / 15),
        ceil(strlen($lokasi) / 20),
        ceil(strlen($asal) / 18)
    );

    $tinggi = ($maxLine * 4) + 4; // 4mm per baris teks

    if ($pdf->GetY() + $tinggi > 185) {
        $pdf->AddPage();
        cetakHeaderTabelAsetLengkap($pdf, $w, $header);
    }

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Gambar Kotak
    $sum_w = 0;
    for ($i = 0; $i < 12; $i++) {
        $pdf->Rect($x + $sum_w, $y, $w[$i], $tinggi);
        $sum_w += $w[$i];
    }

    // Isi Text
    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($w[0], 4, $no++, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + 1, $y + 2);
    $pdf->MultiCell($w[1] - 2, 4, $nama_aset, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1], $y + 2);
    $pdf->Cell($w[2], 4, $kategori, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + 1, $y + 2);
    $pdf->MultiCell($w[3] - 2, 4, $jenis, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + 1, $y + 2);
    $pdf->MultiCell($w[4] - 2, 4, $tipe, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + 1, $y + 2);
    $pdf->MultiCell($w[5] - 2, 4, $lokasi, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y + 2);
    $pdf->Cell($w[6], 4, $kondisi, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + 1, $y + 2);
    $pdf->MultiCell($w[7] - 2, 4, $asal, 0, 'L');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + $w[7] + 1, $y + 2);
    $pdf->MultiCell($w[8] - 2, 4, $harga, 0, 'R');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + $w[7] + $w[8], $y + 2);
    $pdf->Cell($w[9], 4, $umur, 0, 0, 'C');

    $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + $w[7] + $w[8] + $w[9], $y + 2);
    $pdf->Cell($w[10], 4, $tgl, 0, 0, 'C');

    // Cek Gambar Dokumen
    $imgPath = __DIR__ . '/../assets/dokumen/' . $r['dokumen'];
    $imgColStart = $x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + $w[7] + $w[8] + $w[9] + $w[10];

    if (!empty($r['dokumen']) && file_exists($imgPath)) {
        $imgX = $imgColStart + (($w[11] - 14) / 2); // 14mm width image
        $imgY = $y + (($tinggi - 10) / 2);          // 10mm height image
        $pdf->Image($imgPath, $imgX, $imgY, 14, 10);
    } else {
        $pdf->SetXY($imgColStart, $y + 2);
        $pdf->Cell($w[11], $tinggi - 4, 'Tidak Ada', 0, 0, 'C');
    }

    $pdf->SetY($y + $tinggi);
}

// Tanda Tangan
if ($pdf->GetY() > 155) {
    $pdf->AddPage();
}

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'Administrator Aset', 0, 1, 'R');
$pdf->Ln(4);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'], 0, 1, 'R');

ob_end_clean();
$pdf->Output('I', 'Laporan_Master_Aset_' . date('Ymd_His') . '.pdf');
exit;
