<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

ob_clean(); // Mencegah error biner PDF corrupt
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : '';

// ==================== SETUP LAYOUT PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

// ==================== KOP SURAT (Hanya Tampil Sekali di Halaman 1) ==================== //
$y_kop = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');
if ($logoLeft && file_exists($logoLeft))  $pdf->Image($logoLeft, 15, $y_kop, 22);
if ($logoRight && file_exists($logoRight)) $pdf->Image($logoRight, 260, $y_kop, 22);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, $y_kop + 3);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetLineWidth(0.5);
$pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'LAPORAN REKAPITULASI SEMUA DATA', 0, 1, 'C');
if ($tahun_filter != '') {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 5, 'Periode Registrasi Tahun: ' . $tahun_filter, 0, 1, 'C');
}
$pdf->Ln(6);


// =========================================================================
// SECTION 1: LAPORAN ASET
// =========================================================================
$w_aset = [8, 37, 20, 24, 24, 30, 18, 26, 30, 15, 22, 23];
$header_aset = ['No', 'Nama Aset', 'Kategori', 'Jenis', 'Tipe', 'Lokasi', 'Kondisi', 'Asal Usul', 'Harga (Rp)', 'Umur', 'Tgl Masuk', 'Foto'];

function cetakHeaderTabelAsetLengkap($pdf, $w, $header)
{
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(72, 201, 176);
    $pdf->SetTextColor(255);
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 9, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetTextColor(0);
}

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '1. LAPORAN ASET', 0, 1, 'L');
cetakHeaderTabelAsetLengkap($pdf, $w_aset, $header_aset);

$where_aset = [];
if (!empty($tahun_filter)) $where_aset[] = "YEAR(tanggal_masuk) = '$tahun_filter'";
$whereSQL_aset = count($where_aset) ? 'WHERE ' . implode(' AND ', $where_aset) : '';
$sql_aset = "SELECT * FROM aset $whereSQL_aset ORDER BY id_aset DESC";
$res_aset = mysqli_query($koneksi, $sql_aset);

if (mysqli_num_rows($res_aset) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data', 1, 1, 'C');
} else {
    $no_aset = 1;
    while ($r = mysqli_fetch_assoc($res_aset)) {
        $nama_aset = $r['nama_aset'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $jenis     = $r['jenis'] ?? '-';
        $tipe      = $r['tipe_aset'] ?? '-';
        $lokasi    = $r['lokasi'] ?? '-';
        $kondisi   = $r['kondisi'] ?? '-';
        $asal      = $r['asal_usul'] ?? '-';
        $harga     = number_format($r['harga'], 0, ',', '.');
        $umur      = ($r['umur_ekonomis'] > 0) ? $r['umur_ekonomis'] . ' Th' : '-';
        $tgl       = date('d/m/Y', strtotime($r['tanggal_masuk']));

        $maxLine = max(2, ceil(strlen($nama_aset) / 25), ceil(strlen($jenis) / 15), ceil(strlen($tipe) / 15), ceil(strlen($lokasi) / 20), ceil(strlen($asal) / 18));
        $tinggi = ($maxLine * 4) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderTabelAsetLengkap($pdf, $w_aset, $header_aset);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $sum_w = 0;
        for ($i = 0; $i < 12; $i++) {
            $pdf->Rect($x + $sum_w, $y, $w_aset[$i], $tinggi);
            $sum_w += $w_aset[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_aset[0], 4, $no_aset++, 0, 0, 'C');
        $pdf->SetXY($x + $w_aset[0] + 1, $y + 2);
        $pdf->MultiCell($w_aset[1] - 2, 4, $nama_aset, 0, 'L');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1], $y + 2);
        $pdf->Cell($w_aset[2], 4, $kategori, 0, 0, 'C');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1] + $w_aset[2] + 1, $y + 2);
        $pdf->MultiCell($w_aset[3] - 2, 4, $jenis, 0, 'L');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1] + $w_aset[2] + $w_aset[3] + 1, $y + 2);
        $pdf->MultiCell($w_aset[4] - 2, 4, $tipe, 0, 'L');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1] + $w_aset[2] + $w_aset[3] + $w_aset[4] + 1, $y + 2);
        $pdf->MultiCell($w_aset[5] - 2, 4, $lokasi, 0, 'L');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1] + $w_aset[2] + $w_aset[3] + $w_aset[4] + $w_aset[5], $y + 2);
        $pdf->Cell($w_aset[6], 4, $kondisi, 0, 0, 'C');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1] + $w_aset[2] + $w_aset[3] + $w_aset[4] + $w_aset[5] + $w_aset[6] + 1, $y + 2);
        $pdf->MultiCell($w_aset[7] - 2, 4, $asal, 0, 'L');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1] + $w_aset[2] + $w_aset[3] + $w_aset[4] + $w_aset[5] + $w_aset[6] + $w_aset[7] + 1, $y + 2);
        $pdf->MultiCell($w_aset[8] - 2, 4, $harga, 0, 'R');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1] + $w_aset[2] + $w_aset[3] + $w_aset[4] + $w_aset[5] + $w_aset[6] + $w_aset[7] + $w_aset[8], $y + 2);
        $pdf->Cell($w_aset[9], 4, $umur, 0, 0, 'C');
        $pdf->SetXY($x + $w_aset[0] + $w_aset[1] + $w_aset[2] + $w_aset[3] + $w_aset[4] + $w_aset[5] + $w_aset[6] + $w_aset[7] + $w_aset[8] + $w_aset[9], $y + 2);
        $pdf->Cell($w_aset[10], 4, $tgl, 0, 0, 'C');

        $imgPath = __DIR__ . '/../assets/dokumen/' . $r['dokumen'];
        $imgColStart = $x + $w_aset[0] + $w_aset[1] + $w_aset[2] + $w_aset[3] + $w_aset[4] + $w_aset[5] + $w_aset[6] + $w_aset[7] + $w_aset[8] + $w_aset[9] + $w_aset[10];

        if (!empty($r['dokumen']) && file_exists($imgPath)) {
            $imgX = $imgColStart + (($w_aset[11] - 14) / 2);
            $imgY = $y + (($tinggi - 10) / 2);
            $pdf->Image($imgPath, $imgX, $imgY, 14, 10);
        } else {
            $pdf->SetXY($imgColStart, $y + 2);
            $pdf->Cell($w_aset[11], $tinggi - 4, 'Tidak Ada', 0, 0, 'C');
        }
        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// =========================================================================
// SECTION 2: LAPORAN KERUSAKAN
// =========================================================================
$w_rusak = [10, 40, 30, 20, 35, 25, 22, 25, 70];
$header_rusak = ['No', 'Nama Aset', 'Lokasi Ruang', 'Kategori', 'Pelapor', 'Teknisi', 'Tanggal', 'Status', 'Rincian'];

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

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '2. LAPORAN KERUSAKAN', 0, 1, 'L');
cetakHeaderLaporanKerusakan($pdf, $w_rusak, $header_rusak);

$where_rusak = [];
if (!empty($tahun_filter)) $where_rusak[] = "YEAR(kerusakan.tanggal) = '$tahun_filter'";
$whereSQL_rusak = count($where_rusak) ? 'WHERE ' . implode(' AND ', $where_rusak) : '';

$sql_rusak = "SELECT kerusakan.*, aset.lokasi, aset.kategori_aset FROM kerusakan 
              LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
              $whereSQL_rusak ORDER BY kerusakan.tanggal DESC, kerusakan.id DESC";
$res_rusak = mysqli_query($koneksi, $sql_rusak);

if (mysqli_num_rows($res_rusak) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data', 1, 1, 'C');
} else {
    $no_rusak = 1;
    while ($r = mysqli_fetch_assoc($res_rusak)) {
        $nama_aset = $r['nama_aset'] ?? '-';
        $lokasi    = $r['lokasi'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $tanggal   = date('d-m-Y', strtotime($r['tanggal']));
        $status    = $r['status'] ?? '-';
        $ket       = $r['keterangan'] ?? '-';
        $teknisi   = $r['teknisi'] ?? '-';
        $pelapor   = ($r['pelapor'] ?? '-') . "\n[" . ((isset($r['sumber']) && $r['sumber'] == 'App User') ? 'App User' : 'Admin') . "]";

        $maxLine = max(2, ceil(strlen($nama_aset) / 20), ceil(strlen($lokasi) / 15), ceil(strlen($ket) / 45));
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderLaporanKerusakan($pdf, $w_rusak, $header_rusak);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;
        for ($i = 0; $i < count($w_rusak); $i++) {
            $pdf->Rect($currX, $y, $w_rusak[$i], $tinggi);
            $currX += $w_rusak[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_rusak[0], 5, $no_rusak++, 0, 0, 'C');
        $pdf->SetXY($x + $w_rusak[0] + 1, $y + 2);
        $pdf->MultiCell($w_rusak[1] - 2, 5, $nama_aset, 0, 'L');
        $pdf->SetXY($x + $w_rusak[0] + $w_rusak[1] + 1, $y + 2);
        $pdf->MultiCell($w_rusak[2] - 2, 5, $lokasi, 0, 'L');
        $pdf->SetXY($x + $w_rusak[0] + $w_rusak[1] + $w_rusak[2], $y + 2);
        $pdf->Cell($w_rusak[3], 5, $kategori, 0, 0, 'C');
        $pdf->SetXY($x + $w_rusak[0] + $w_rusak[1] + $w_rusak[2] + $w_rusak[3] + 1, $y + 2);
        $pdf->MultiCell($w_rusak[4] - 2, 5, $pelapor, 0, 'L');
        $pdf->SetXY($x + $w_rusak[0] + $w_rusak[1] + $w_rusak[2] + $w_rusak[3] + $w_rusak[4] + 1, $y + 2);
        $pdf->MultiCell($w_rusak[5] - 2, 5, $teknisi, 0, 'L');
        $pdf->SetXY($x + $w_rusak[0] + $w_rusak[1] + $w_rusak[2] + $w_rusak[3] + $w_rusak[4] + $w_rusak[5], $y + 2);
        $pdf->Cell($w_rusak[6], 5, $tanggal, 0, 0, 'C');
        $pdf->SetXY($x + $w_rusak[0] + $w_rusak[1] + $w_rusak[2] + $w_rusak[3] + $w_rusak[4] + $w_rusak[5] + $w_rusak[6], $y + 2);
        $pdf->Cell($w_rusak[7], 5, $status, 0, 0, 'C');
        $pdf->SetXY($x + $w_rusak[0] + $w_rusak[1] + $w_rusak[2] + $w_rusak[3] + $w_rusak[4] + $w_rusak[5] + $w_rusak[6] + $w_rusak[7] + 1, $y + 2);
        $pdf->MultiCell($w_rusak[8] - 2, 5, $ket, 0, 'L');
        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// =========================================================================
// SECTION 3: LAPORAN PERAWATAN
// =========================================================================
$w_rawat = [10, 55, 30, 40, 50, 27, 35, 30];
$header_rawat = ['No', 'Nama Aset', 'Kategori', 'Lokasi Ruang', 'Teknisi Bertugas', 'Tgl Rawat', 'Jadwal Kalibrasi', 'Status'];

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

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '3. LAPORAN PERAWATAN', 0, 1, 'L');
cetakHeaderPerawatan($pdf, $w_rawat, $header_rawat);

$where_rawat = [];
if (!empty($tahun_filter)) $where_rawat[] = "YEAR(perawatan.tanggal) = '$tahun_filter'";
$whereSQL_rawat = count($where_rawat) ? 'WHERE ' . implode(' AND ', $where_rawat) : '';

$sql_rawat = "SELECT perawatan.*, aset.lokasi, aset.kategori_aset FROM perawatan 
              LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
              $whereSQL_rawat ORDER BY perawatan.tanggal DESC";
$res_rawat = mysqli_query($koneksi, $sql_rawat);

if (mysqli_num_rows($res_rawat) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data', 1, 1, 'C');
} else {
    $no_rawat = 1;
    while ($r = mysqli_fetch_assoc($res_rawat)) {
        $nama_aset = $r['nama_aset'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $lokasi    = $r['lokasi'] ?? '-';
        $teknisi   = $r['teknisi'] ?? '-';
        $tanggal   = date('d/m/Y', strtotime($r['tanggal']));
        $status    = $r['status'] ?? '-';

        $tgl_berikutnya = $r['tanggal_kalibrasi_berikutnya'] ?? '';
        if ($tgl_berikutnya && $tgl_berikutnya != '0000-00-00') {
            $kalibrasi_tgl = date('d/m/Y', strtotime($tgl_berikutnya));
            $selisih_detik = strtotime($tgl_berikutnya) - strtotime('today');
            $selisih_hari = floor($selisih_detik / (60 * 60 * 24));
            if ($selisih_hari <= 7 && $selisih_hari >= 0) $kalibrasi_status = "H-$selisih_hari";
            elseif ($selisih_hari < 0) $kalibrasi_status = "Lewat " . abs($selisih_hari) . " Hr";
            else $kalibrasi_status = "Aman";
            $kalibrasi_cetak = $kalibrasi_tgl . "\n[" . $kalibrasi_status . "]";
        } else {
            $kalibrasi_cetak = "-";
        }

        $maxLine = max(2, ceil(strlen($nama_aset) / 32), ceil(strlen($lokasi) / 24), ceil(strlen($teknisi) / 30));
        $tinggi = ($maxLine * 5) + 4;
        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderPerawatan($pdf, $w_rawat, $header_rawat);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;
        for ($i = 0; $i < count($w_rawat); $i++) {
            $pdf->Rect($currX, $y, $w_rawat[$i], $tinggi);
            $currX += $w_rawat[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_rawat[0], 5, $no_rawat++, 0, 0, 'C');
        $pdf->SetXY($x + $w_rawat[0] + 1, $y + 2);
        $pdf->MultiCell($w_rawat[1] - 2, 5, $nama_aset, 0, 'L');
        $pdf->SetXY($x + $w_rawat[0] + $w_rawat[1] + 1, $y + 2);
        $pdf->MultiCell($w_rawat[2] - 2, 5, $kategori, 0, 'C');
        $pdf->SetXY($x + $w_rawat[0] + $w_rawat[1] + $w_rawat[2] + 1, $y + 2);
        $pdf->MultiCell($w_rawat[3] - 2, 5, $lokasi, 0, 'L');
        $pdf->SetXY($x + $w_rawat[0] + $w_rawat[1] + $w_rawat[2] + $w_rawat[3] + 1, $y + 2);
        $pdf->MultiCell($w_rawat[4] - 2, 5, $teknisi, 0, 'L');
        $pdf->SetXY($x + $w_rawat[0] + $w_rawat[1] + $w_rawat[2] + $w_rawat[3] + $w_rawat[4], $y + 2);
        $pdf->Cell($w_rawat[5], 5, $tanggal, 0, 0, 'C');
        $pdf->SetXY($x + $w_rawat[0] + $w_rawat[1] + $w_rawat[2] + $w_rawat[3] + $w_rawat[4] + $w_rawat[5] + 1, $y + 2);
        $pdf->MultiCell($w_rawat[6] - 2, 5, $kalibrasi_cetak, 0, 'C');
        $pdf->SetXY($x + $w_rawat[0] + $w_rawat[1] + $w_rawat[2] + $w_rawat[3] + $w_rawat[4] + $w_rawat[5] + $w_rawat[6], $y + 2);
        $pdf->Cell($w_rawat[7], 5, $status, 0, 0, 'C');
        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// =========================================================================
// SECTION 4: LAPORAN PERBAIKAN
// =========================================================================
$w_baik = [10, 35, 25, 20, 25, 25, 20, 25, 92];
$header_baik = ['No', 'Nama Aset', 'Lokasi', 'Kategori', 'Pelapor', 'Teknisi', 'Tanggal', 'Status', 'Keterangan'];

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '4. LAPORAN PERBAIKAN', 0, 1, 'L');
cetakHeaderLaporanKerusakan($pdf, $w_baik, $header_baik);

$where_baik = ["(kerusakan.status = 'Dalam Perbaikan' OR kerusakan.status = 'Selesai Diperbaiki')"];
if (!empty($tahun_filter)) $where_baik[] = "YEAR(kerusakan.tanggal) = '$tahun_filter'";
$whereSQL_baik = "WHERE " . implode(' AND ', $where_baik);

$sql_baik = "SELECT kerusakan.*, aset.kategori_aset, aset.lokasi FROM kerusakan 
             LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
             $whereSQL_baik ORDER BY kerusakan.tanggal DESC";
$res_baik = mysqli_query($koneksi, $sql_baik);

if (mysqli_num_rows($res_baik) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data', 1, 1, 'C');
} else {
    $no_baik = 1;
    while ($r = mysqli_fetch_assoc($res_baik)) {
        $nama_aset  = $r['nama_aset'] ?? '-';
        $lokasi     = $r['lokasi'] ?? '-';
        $kategori   = $r['kategori_aset'] ?? '-';
        $pelapor    = $r['pelapor'] ?? '-';
        $teknisi    = $r['teknisi'] ?? '-';
        $tanggal    = date('d-m-Y', strtotime($r['tanggal']));
        $status     = $r['status'] ?? '-';
        $keterangan = $r['keterangan'] ?? '-';

        if (strlen($nama_aset) > 20) $nama_aset = substr($nama_aset, 0, 18) . '...';
        $maxLine = max(1, ceil(strlen($nama_aset) / 20), ceil(strlen($keterangan) / 60));
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderLaporanKerusakan($pdf, $w_baik, $header_baik);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;
        foreach ($w_baik as $width) {
            $pdf->Rect($currX, $y, $width, $tinggi);
            $currX += $width;
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_baik[0], 5, $no_baik++, 0, 0, 'C');
        $pdf->SetXY($x + $w_baik[0] + 1, $y + 2);
        $pdf->MultiCell($w_baik[1] - 2, 5, $nama_aset, 0, 'L');
        $pdf->SetXY($x + $w_baik[0] + $w_baik[1] + 1, $y + 2);
        $pdf->MultiCell($w_baik[2] - 2, 5, $lokasi, 0, 'L');
        $pdf->SetXY($x + $w_baik[0] + $w_baik[1] + $w_baik[2], $y + 2);
        $pdf->Cell($w_baik[3], 5, $kategori, 0, 0, 'C');
        $pdf->SetXY($x + $w_baik[0] + $w_baik[1] + $w_baik[2] + $w_baik[3] + 1, $y + 2);
        $pdf->MultiCell($w_baik[4] - 2, 5, $pelapor, 0, 'L');
        $pdf->SetXY($x + $w_baik[0] + $w_baik[1] + $w_baik[2] + $w_baik[3] + $w_baik[4] + 1, $y + 2);
        $pdf->MultiCell($w_baik[5] - 2, 5, $teknisi, 0, 'L');
        $pdf->SetXY($x + $w_baik[0] + $w_baik[1] + $w_baik[2] + $w_baik[3] + $w_baik[4] + $w_baik[5], $y + 2);
        $pdf->Cell($w_baik[6], 5, $tanggal, 0, 0, 'C');
        $pdf->SetXY($x + $w_baik[0] + $w_baik[1] + $w_baik[2] + $w_baik[3] + $w_baik[4] + $w_baik[5] + $w_baik[6], $y + 2);
        $pdf->Cell($w_baik[7], 5, $status, 0, 0, 'C');
        $pdf->SetXY($x + $w_baik[0] + $w_baik[1] + $w_baik[2] + $w_baik[3] + $w_baik[4] + $w_baik[5] + $w_baik[6] + $w_baik[7] + 1, $y + 2);
        $pdf->MultiCell($w_baik[8] - 2, 5, $keterangan, 0, 'L');
        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// =========================================================================
// SECTION 5: LAPORAN PERAWATAN BERJALAN
// =========================================================================
$w_pb = [10, 50, 30, 45, 50, 40, 52];
$header_pb = ['No', 'Nama Aset', 'Kategori', 'Lokasi Ruang', 'Teknisi Bertugas', 'Tanggal Mulai', 'Status Perawatan'];

function cetakHeaderPerawatanBerjalan($pdf, $w, $header)
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

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '5. LAPORAN PERAWATAN BERJALAN', 0, 1, 'L');
cetakHeaderPerawatanBerjalan($pdf, $w_pb, $header_pb);

$where_pb = ["p.status IN ('Belum Dimulai','Sedang Proses')"];
if (!empty($tahun_filter)) $where_pb[] = "YEAR(p.tanggal) = '$tahun_filter'";
$whereSQL_pb = "WHERE " . implode(' AND ', $where_pb);

$sql_pb = "SELECT p.*, a.kategori_aset, a.lokasi FROM perawatan p 
           LEFT JOIN aset a ON p.nama_aset = a.nama_aset COLLATE utf8mb4_general_ci
           $whereSQL_pb ORDER BY p.tanggal DESC";
$res_pb = mysqli_query($koneksi, $sql_pb);

if (mysqli_num_rows($res_pb) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data', 1, 1, 'C');
} else {
    $no_pb = 1;
    while ($r = mysqli_fetch_assoc($res_pb)) {
        $nama_aset = $r['nama_aset'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $lokasi    = $r['lokasi'] ?? '-';
        $teknisi   = $r['teknisi'] ?? '-';
        $tanggal   = date('d/m/Y', strtotime($r['tanggal']));
        $status    = $r['status'] ?? '-';

        $maxLine = max(2, ceil(strlen($nama_aset) / 25), ceil(strlen($lokasi) / 20), ceil(strlen($teknisi) / 25));
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderPerawatanBerjalan($pdf, $w_pb, $header_pb);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;
        for ($i = 0; $i < count($w_pb); $i++) {
            $pdf->Rect($currX, $y, $w_pb[$i], $tinggi);
            $currX += $w_pb[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_pb[0], 5, $no_pb++, 0, 0, 'C');
        $pdf->SetXY($x + $w_pb[0] + 1, $y + 2);
        $pdf->MultiCell($w_pb[1] - 2, 5, $nama_aset, 0, 'L');
        $pdf->SetXY($x + $w_pb[0] + $w_pb[1], $y + 2);
        $pdf->Cell($w_pb[2], 5, $kategori, 0, 0, 'C');
        $pdf->SetXY($x + $w_pb[0] + $w_pb[1] + $w_pb[2] + 1, $y + 2);
        $pdf->MultiCell($w_pb[3] - 2, 5, $lokasi, 0, 'L');
        $pdf->SetXY($x + $w_pb[0] + $w_pb[1] + $w_pb[2] + $w_pb[3] + 1, $y + 2);
        $pdf->MultiCell($w_pb[4] - 2, 5, $teknisi, 0, 'L');
        $pdf->SetXY($x + $w_pb[0] + $w_pb[1] + $w_pb[2] + $w_pb[3] + $w_pb[4], $y + 2);
        $pdf->Cell($w_pb[5], 5, $tanggal, 0, 0, 'C');
        $pdf->SetXY($x + $w_pb[0] + $w_pb[1] + $w_pb[2] + $w_pb[3] + $w_pb[4] + $w_pb[5], $y + 2);
        $pdf->Cell($w_pb[6], 5, $status, 0, 0, 'C');

        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// =========================================================================
// SECTION 6: LAPORAN PEMINJAMAN ASET
// =========================================================================
$w_pinjam = [10, 40, 74, 28, 45, 25, 25, 30];
$header_pinjam = ['No', 'Peminjam', 'Nama Alat', 'Kategori', 'Lokasi Asal', 'Tgl Pinjam', 'Tgl Kembali', 'Status'];

function cetakHeaderPeminjaman($pdf, $w, $header)
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

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '6. LAPORAN PEMINJAMAN ASET', 0, 1, 'L');
cetakHeaderPeminjaman($pdf, $w_pinjam, $header_pinjam);

$where_pinjam = [];
if (!empty($tahun_filter)) $where_pinjam[] = "YEAR(peminjaman.tanggal_pinjam) = '$tahun_filter'";
$whereSQL_pinjam = count($where_pinjam) ? 'WHERE ' . implode(' AND ', $where_pinjam) : '';

$sql_pinjam = "SELECT peminjaman.*, aset.nama_aset, aset.kategori_aset, aset.lokasi 
               FROM peminjaman 
               JOIN aset ON peminjaman.id_aset = aset.id_aset 
               $whereSQL_pinjam ORDER BY peminjaman.id_pinjam DESC";
$res_pinjam = mysqli_query($koneksi, $sql_pinjam);

if (mysqli_num_rows($res_pinjam) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data', 1, 1, 'C');
} else {
    $no_pinjam = 1;
    while ($r = mysqli_fetch_assoc($res_pinjam)) {
        $peminjam_nama = $r['nama_peminjam'] ?? '-';
        $sumber_label = (isset($r['sumber']) && $r['sumber'] == 'App User') ? 'App User' : 'Admin';
        $peminjam = $peminjam_nama . "\n[" . $sumber_label . "]";

        $nama_aset = $r['nama_aset'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $lokasi    = $r['lokasi'] ?? '-';
        $tgl_pinjam = (!empty($r['tanggal_pinjam']) && $r['tanggal_pinjam'] != '0000-00-00') ? date('d/m/Y', strtotime($r['tanggal_pinjam'])) : '-';
        $tgl_kembali = (!empty($r['tanggal_kembali']) && $r['tanggal_kembali'] != '0000-00-00') ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '-';
        $status = $r['status_pinjam'] ?? '-';

        $maxLine = max(2, ceil(strlen($nama_aset) / 40), ceil(strlen($kategori) / 18), ceil(strlen($lokasi) / 25));
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderPeminjaman($pdf, $w_pinjam, $header_pinjam);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;
        for ($i = 0; $i < count($w_pinjam); $i++) {
            $pdf->Rect($currX, $y, $w_pinjam[$i], $tinggi);
            $currX += $w_pinjam[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_pinjam[0], 5, $no_pinjam++, 0, 0, 'C');
        $pdf->SetXY($x + $w_pinjam[0] + 1, $y + 2);
        $pdf->MultiCell($w_pinjam[1] - 2, 5, $peminjam, 0, 'L');
        $pdf->SetXY($x + $w_pinjam[0] + $w_pinjam[1] + 1, $y + 2);
        $pdf->MultiCell($w_pinjam[2] - 2, 5, $nama_aset, 0, 'L');
        $pdf->SetXY($x + $w_pinjam[0] + $w_pinjam[1] + $w_pinjam[2] + 1, $y + 2);
        $pdf->MultiCell($w_pinjam[3] - 2, 5, $kategori, 0, 'C');
        $pdf->SetXY($x + $w_pinjam[0] + $w_pinjam[1] + $w_pinjam[2] + $w_pinjam[3] + 1, $y + 2);
        $pdf->MultiCell($w_pinjam[4] - 2, 5, $lokasi, 0, 'L');
        $pdf->SetXY($x + $w_pinjam[0] + $w_pinjam[1] + $w_pinjam[2] + $w_pinjam[3] + $w_pinjam[4], $y + 2);
        $pdf->Cell($w_pinjam[5], 5, $tgl_pinjam, 0, 0, 'C');
        $pdf->SetXY($x + $w_pinjam[0] + $w_pinjam[1] + $w_pinjam[2] + $w_pinjam[3] + $w_pinjam[4] + $w_pinjam[5], $y + 2);
        $pdf->Cell($w_pinjam[6], 5, $tgl_kembali, 0, 0, 'C');
        $pdf->SetXY($x + $w_pinjam[0] + $w_pinjam[1] + $w_pinjam[2] + $w_pinjam[3] + $w_pinjam[4] + $w_pinjam[5] + $w_pinjam[6], $y + 2);
        $pdf->Cell($w_pinjam[7], 5, $status, 0, 0, 'C');

        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// =========================================================================
// SECTION 7: LAPORAN HASIL AUDIT FISIK (DIPERBARUI)
// =========================================================================
$w_audit = [10, 38, 22, 25, 25, 20, 20, 25, 92];
$header_audit = ['No', 'Nama Aset', 'Kategori', 'Lokasi', 'Auditor', 'Tgl Audit', 'Kondisi', 'Bukti Fisik', 'Keterangan Tambahan'];

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

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '7. LAPORAN HASIL AUDIT FISIK', 0, 1, 'L');
cetakHeaderAudit($pdf, $w_audit, $header_audit);

$where_audit = [];
if (!empty($tahun_filter)) $where_audit[] = "YEAR(audit_fisik.tanggal_audit) = '$tahun_filter'";
$whereSQL_audit = count($where_audit) ? 'WHERE ' . implode(' AND ', $where_audit) : '';

$sql_audit = "SELECT audit_fisik.*, aset.nama_aset, aset.lokasi, aset.kategori_aset 
              FROM audit_fisik 
              INNER JOIN aset ON audit_fisik.id_aset = aset.id_aset 
              $whereSQL_audit ORDER BY audit_fisik.id_audit DESC";
$res_audit = mysqli_query($koneksi, $sql_audit);

if (mysqli_num_rows($res_audit) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data', 1, 1, 'C');
} else {
    $no_audit = 1;
    while ($r = mysqli_fetch_assoc($res_audit)) {
        $nama_aset = $r['nama_aset'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $lokasi    = $r['lokasi'] ?? '-';
        $auditor   = $r['auditor'] ?? '-';
        $tanggal   = (!empty($r['tanggal_audit'])) ? date('d/m/Y', strtotime($r['tanggal_audit'])) : '-';
        $kondisi   = $r['kondisi_fisik'] ?? '-';
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
            cetakHeaderAudit($pdf, $w_audit, $header_audit);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;
        for ($i = 0; $i < count($w_audit); $i++) {
            $pdf->Rect($currX, $y, $w_audit[$i], $tinggi);
            $currX += $w_audit[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_audit[0], 5, $no_audit++, 0, 0, 'C');

        $pdf->SetXY($x + $w_audit[0] + 1, $y + 2);
        $pdf->MultiCell($w_audit[1] - 2, 5, $nama_aset, 0, 'L');

        $pdf->SetXY($x + $w_audit[0] + $w_audit[1], $y + 2);
        $pdf->Cell($w_audit[2], 5, $kategori, 0, 0, 'C');

        $pdf->SetXY($x + $w_audit[0] + $w_audit[1] + $w_audit[2] + 1, $y + 2);
        $pdf->MultiCell($w_audit[3] - 2, 5, $lokasi, 0, 'L');

        $pdf->SetXY($x + $w_audit[0] + $w_audit[1] + $w_audit[2] + $w_audit[3] + 1, $y + 2);
        $pdf->MultiCell($w_audit[4] - 2, 5, $auditor, 0, 'L');

        $pdf->SetXY($x + $w_audit[0] + $w_audit[1] + $w_audit[2] + $w_audit[3] + $w_audit[4], $y + 2);
        $pdf->Cell($w_audit[5], 5, $tanggal, 0, 0, 'C');

        $pdf->SetXY($x + $w_audit[0] + $w_audit[1] + $w_audit[2] + $w_audit[3] + $w_audit[4] + $w_audit[5], $y + 2);
        $pdf->Cell($w_audit[6], 5, $kondisi, 0, 0, 'C');

        // Render Gambar ke dalam sel Bukti Fisik
        $imgPath = __DIR__ . '/../assets/img/' . ($r['gambar_rusak'] ?? '');
        $imgColStart = $x + $w_audit[0] + $w_audit[1] + $w_audit[2] + $w_audit[3] + $w_audit[4] + $w_audit[5] + $w_audit[6];

        if (!empty($r['gambar_rusak']) && file_exists($imgPath)) {
            $imgWidth = 18;
            $imgHeight = 14;
            $imgX = $imgColStart + (($w_audit[7] - $imgWidth) / 2);
            $imgY = $y + (($tinggi - $imgHeight) / 2);
            $pdf->Image($imgPath, $imgX, $imgY, $imgWidth, $imgHeight);
        } else {
            $pdf->SetXY($imgColStart, $y + 2);
            $pdf->Cell($w_audit[7], $tinggi - 4, '-', 0, 0, 'C');
        }

        $pdf->SetXY($x + $w_audit[0] + $w_audit[1] + $w_audit[2] + $w_audit[3] + $w_audit[4] + $w_audit[5] + $w_audit[6] + $w_audit[7] + 1, $y + 2);
        $pdf->MultiCell($w_audit[8] - 2, 5, $keterangan, 0, 'L');

        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// =========================================================================
// SECTION 8: LAPORAN REKAPITULASI NILAI ASET
// =========================================================================
$w_nilai = [10, 60, 25, 22, 20, 45, 40, 55];
$header_nilai = ['No', 'Nama Aset', 'Kategori', 'Thn Masuk', 'Umur Eko.', 'Harga Beli Awal', 'Susut / Tahun', 'Nilai Saat Ini'];

function cetakHeaderNilai($pdf, $w, $header)
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

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '8. LAPORAN REKAPITULASI NILAI ASET', 0, 1, 'L');
cetakHeaderNilai($pdf, $w_nilai, $header_nilai);

$where_nilai = ["harga > 0"];
if (!empty($tahun_filter)) $where_nilai[] = "YEAR(tanggal_masuk) = '$tahun_filter'";
$whereSQL_nilai = "WHERE " . implode(' AND ', $where_nilai);

$sql_nilai = "SELECT * FROM aset $whereSQL_nilai ORDER BY tanggal_masuk DESC";
$res_nilai = mysqli_query($koneksi, $sql_nilai);

$total_harga_awal = 0;
$total_nilai_saat_ini = 0;
$tahun_sekarang = date('Y');

if (mysqli_num_rows($res_nilai) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data', 1, 1, 'C');
} else {
    $no_nilai = 1;
    while ($r = mysqli_fetch_assoc($res_nilai)) {
        $nama_aset = $r['nama_aset'];
        $kategori  = $r['kategori_aset'] ?? '-';
        $harga     = $r['harga'];
        $umur      = isset($r['umur_ekonomis']) ? (int)$r['umur_ekonomis'] : 0;
        $tgl_masuk = $r['tanggal_masuk'];
        $thn_masuk = date('Y', strtotime($tgl_masuk));

        $susut_per_tahun = 0;
        $nilai_sekarang = $harga;

        if ($umur > 0) {
            $susut_per_tahun = $harga / $umur;
            $pakai = $tahun_sekarang - $thn_masuk;
            if ($pakai < 0) $pakai = 0;
            if ($pakai > $umur) $pakai = $umur;
            $akumulasi = $pakai * $susut_per_tahun;
            $nilai_sekarang = $harga - $akumulasi;
        }

        $total_harga_awal += $harga;
        $total_nilai_saat_ini += $nilai_sekarang;

        if (strlen($nama_aset) > 30) $nama_aset = substr($nama_aset, 0, 27) . '...';

        if ($pdf->GetY() > 180) {
            $pdf->AddPage();
            cetakHeaderNilai($pdf, $w_nilai, $header_nilai);
        }

        $pdf->Cell($w_nilai[0], 8, $no_nilai++, 1, 0, 'C');
        $pdf->Cell($w_nilai[1], 8, $nama_aset, 1, 0, 'L');
        $pdf->Cell($w_nilai[2], 8, $kategori, 1, 0, 'C');
        $pdf->Cell($w_nilai[3], 8, $thn_masuk, 1, 0, 'C');
        $pdf->Cell($w_nilai[4], 8, ($umur > 0 ? $umur . " Thn" : "-"), 1, 0, 'C');
        $pdf->Cell($w_nilai[5], 8, 'Rp ' . number_format($harga, 0, ',', '.'), 1, 0, 'R');

        $pdf->SetTextColor(220, 53, 69);
        $pdf->Cell($w_nilai[6], 8, '- Rp ' . number_format($susut_per_tahun, 0, ',', '.'), 1, 0, 'R');
        $pdf->SetTextColor(0);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($w_nilai[7], 8, 'Rp ' . number_format($nilai_sekarang, 0, ',', '.'), 1, 1, 'R');
        $pdf->SetFont('Arial', '', 8);
    }
}

// Baris Total Akumulasi Nilai Aset
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell($w_nilai[0] + $w_nilai[1] + $w_nilai[2] + $w_nilai[3] + $w_nilai[4], 10, 'TOTAL KESELURUHAN NILAI INVESTASI ASET', 1, 0, 'R', true);
$pdf->Cell($w_nilai[5], 10, 'Rp ' . number_format($total_harga_awal, 0, ',', '.'), 1, 0, 'R', true);
$pdf->Cell($w_nilai[6], 10, '', 1, 0, 'C', true);
$pdf->SetTextColor(25, 135, 84);
$pdf->Cell($w_nilai[7], 10, 'Rp ' . number_format($total_nilai_saat_ini, 0, ',', '.'), 1, 1, 'R', true);
$pdf->SetTextColor(0);
$pdf->SetFont('Arial', '', 8);
$pdf->Ln(10);


// =========================================================================
// SECTION 9: LAPORAN JADWAL KALIBRASI ASET & ALAT MEDIS
// =========================================================================
$w_kalib = [10, 50, 45, 35, 25, 27, 27, 58];
$header_kalib = ['No', 'Nama Aset', 'Merk / Tipe', 'Lokasi Ruang', 'Kategori', 'Tgl Rawat', 'Tgl Kalibrasi', 'Status / Sisa Waktu'];

function cetakHeaderKalib($pdf, $w, $header)
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

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '9. LAPORAN JADWAL KALIBRASI ASET & ALAT MEDIS', 0, 1, 'L');
cetakHeaderKalib($pdf, $w_kalib, $header_kalib);

$where_kalib = ["perawatan.tanggal_kalibrasi_berikutnya IS NOT NULL AND perawatan.tanggal_kalibrasi_berikutnya >= '2000-01-01'"];
if (!empty($tahun_filter)) $where_kalib[] = "YEAR(perawatan.tanggal_kalibrasi_berikutnya) = '$tahun_filter'";
$whereSQL_kalib = "WHERE " . implode(' AND ', $where_kalib);

$sql_kalib = "SELECT perawatan.*, aset.lokasi, aset.kategori_aset, aset.jenis, aset.tipe_aset 
              FROM perawatan 
              LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
              $whereSQL_kalib 
              ORDER BY perawatan.tanggal_kalibrasi_berikutnya ASC";
$res_kalib = mysqli_query($koneksi, $sql_kalib);

if (mysqli_num_rows($res_kalib) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data jadwal kalibrasi.', 1, 1, 'C');
} else {
    $no_kalib = 1;
    while ($r = mysqli_fetch_assoc($res_kalib)) {
        $nama_aset = $r['nama_aset'] ?? '-';
        $merk_tipe = ($r['jenis'] ?? '-') . " / " . ($r['tipe_aset'] ?? '-');
        $lokasi    = $r['lokasi'] ?? '-';
        $kategori  = $r['kategori_aset'] ?? '-';
        $t_rawat   = (!empty($r['tanggal']) && $r['tanggal'] != '0000-00-00') ? date('d/m/Y', strtotime($r['tanggal'])) : '-';

        $tgl_berikutnya = $r['tanggal_kalibrasi_berikutnya'];
        $t_kalib = date('d/m/Y', strtotime($tgl_berikutnya));

        $selisih_hari = floor((strtotime($tgl_berikutnya) - strtotime('today')) / 86400);
        if ($selisih_hari <= 7 && $selisih_hari >= 0) {
            $status = "Mendekati H-" . $selisih_hari;
        } elseif ($selisih_hari < 0) {
            $status = "Terlewat " . abs($selisih_hari) . " Hari";
        } else {
            $status = "Aman (> 7 Hari)";
        }

        $maxLine = max(2, ceil(strlen($nama_aset) / 28), ceil(strlen($merk_tipe) / 22), ceil(strlen($lokasi) / 18));
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderKalib($pdf, $w_kalib, $header_kalib);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;
        for ($i = 0; $i < count($w_kalib); $i++) {
            $pdf->Rect($currX, $y, $w_kalib[$i], $tinggi);
            $currX += $w_kalib[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_kalib[0], 5, $no_kalib++, 0, 0, 'C');
        $pdf->SetXY($x + $w_kalib[0] + 1, $y + 2);
        $pdf->MultiCell($w_kalib[1] - 2, 5, $nama_aset, 0, 'L');
        $pdf->SetXY($x + $w_kalib[0] + $w_kalib[1] + 1, $y + 2);
        $pdf->MultiCell($w_kalib[2] - 2, 5, $merk_tipe, 0, 'L');
        $pdf->SetXY($x + $w_kalib[0] + $w_kalib[1] + $w_kalib[2] + 1, $y + 2);
        $pdf->MultiCell($w_kalib[3] - 2, 5, $lokasi, 0, 'L');
        $pdf->SetXY($x + $w_kalib[0] + $w_kalib[1] + $w_kalib[2] + $w_kalib[3], $y + 2);
        $pdf->Cell($w_kalib[4], 5, $kategori, 0, 0, 'C');
        $pdf->SetXY($x + $w_kalib[0] + $w_kalib[1] + $w_kalib[2] + $w_kalib[3] + $w_kalib[4], $y + 2);
        $pdf->Cell($w_kalib[5], 5, $t_rawat, 0, 0, 'C');
        $pdf->SetXY($x + $w_kalib[0] + $w_kalib[1] + $w_kalib[2] + $w_kalib[3] + $w_kalib[4] + $w_kalib[5], $y + 2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($w_kalib[6], 5, $t_kalib, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY($x + $w_kalib[0] + $w_kalib[1] + $w_kalib[2] + $w_kalib[3] + $w_kalib[4] + $w_kalib[5] + $w_kalib[6] + 1, $y + 2);
        $pdf->MultiCell($w_kalib[7] - 2, 5, $status, 0, 'C');

        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// =========================================================================
// SECTION 10: LAPORAN MUTASI & PELACAKAN LOKASI ASET
// =========================================================================
$w_pindah = [10, 22, 50, 25, 35, 35, 35, 65];
$header_pindah = ['No', 'Tanggal', 'Nama Aset', 'Kategori', 'P. Jawab', 'Lok. Awal', 'Lok. Baru', 'Keterangan'];

function cetakHeaderPelacakan($pdf, $w, $header)
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

if ($pdf->GetY() + 25 > 185) $pdf->AddPage();
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, '10. LAPORAN MUTASI & PELACAKAN LOKASI ASET', 0, 1, 'L');
cetakHeaderPelacakan($pdf, $w_pindah, $header_pindah);

$where_pindah = [];
if (!empty($tahun_filter)) $where_pindah[] = "YEAR(r.tanggal_pindah) = '$tahun_filter'";
$whereSQL_pindah = count($where_pindah) > 0 ? "WHERE " . implode(" AND ", $where_pindah) : "";

$sql_pindah = "SELECT r.*, a.nama_aset, a.kategori_aset 
               FROM riwayat_lokasi r 
               JOIN aset a ON r.id_aset = a.id_aset 
               $whereSQL_pindah 
               ORDER BY r.tanggal_pindah DESC, r.id_riwayat DESC";
$res_pindah = mysqli_query($koneksi, $sql_pindah);

if (mysqli_num_rows($res_pindah) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data pelacakan aset.', 1, 1, 'C');
} else {
    $no_pindah = 1;
    while ($row = mysqli_fetch_assoc($res_pindah)) {
        $tgl_pindah = ($row['tanggal_pindah'] && $row['tanggal_pindah'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_pindah'])) : '-';
        $kat_aset = ($row['kategori_aset'] == 'Medis') ? 'Medis' : 'Non-Medis';
        $p_jawab = isset($row['penanggung_jawab']) ? $row['penanggung_jawab'] : '-';

        $maxLine = max(2, ceil(strlen($row['nama_aset']) / 25), ceil(strlen($p_jawab) / 20), ceil(strlen($row['keterangan']) / 32));
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderPelacakan($pdf, $w_pindah, $header_pindah);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;
        for ($i = 0; $i < count($w_pindah); $i++) {
            $pdf->Rect($currX, $y, $w_pindah[$i], $tinggi);
            $currX += $w_pindah[$i];
        }

        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w_pindah[0], 5, $no_pindah++, 0, 0, 'C');
        $pdf->SetXY($x + $w_pindah[0] + 1, $y + 2);
        $pdf->Cell($w_pindah[1] - 2, 5, $tgl_pindah, 0, 0, 'C');
        $pdf->SetXY($x + $w_pindah[0] + $w_pindah[1] + 1, $y + 2);
        $pdf->MultiCell($w_pindah[2] - 2, 5, $row['nama_aset'], 0, 'L');
        $pdf->SetXY($x + $w_pindah[0] + $w_pindah[1] + $w_pindah[2] + 1, $y + 2);
        $pdf->Cell($w_pindah[3] - 2, 5, $kat_aset, 0, 0, 'C');
        $pdf->SetXY($x + $w_pindah[0] + $w_pindah[1] + $w_pindah[2] + $w_pindah[3] + 1, $y + 2);
        $pdf->MultiCell($w_pindah[4] - 2, 5, $p_jawab, 0, 'L');
        $pdf->SetXY($x + $w_pindah[0] + $w_pindah[1] + $w_pindah[2] + $w_pindah[3] + $w_pindah[4] + 1, $y + 2);
        $pdf->MultiCell($w_pindah[5] - 2, 5, $row['lokasi_sebelumnya'], 0, 'L');
        $pdf->SetXY($x + $w_pindah[0] + $w_pindah[1] + $w_pindah[2] + $w_pindah[3] + $w_pindah[4] + $w_pindah[5] + 1, $y + 2);
        $pdf->MultiCell($w_pindah[6] - 2, 5, $row['lokasi_baru'], 0, 'L');
        $pdf->SetXY($x + $w_pindah[0] + $w_pindah[1] + $w_pindah[2] + $w_pindah[3] + $w_pindah[4] + $w_pindah[5] + $w_pindah[6] + 1, $y + 2);
        $pdf->MultiCell($w_pindah[7] - 2, 5, $row['keterangan'], 0, 'L');

        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);


// ==================== AREA TANDA TANGAN PIMPINAN ==================== //
if ($pdf->GetY() > 155) $pdf->AddPage();

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->SetX(200);
$pdf->Cell(70, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'C');
$pdf->SetX(200);
$pdf->Cell(70, 6, 'Kepala Rumah Sakit,', 0, 1, 'C');
$pdf->Ln(22);

$pdf->SetX(200);
$pdf->SetFont('Arial', 'BU', 11);
$pdf->Cell(70, 6, 'AKBP dr. Muhammad Ihsan Wahyudi', 0, 1, 'C');

$pdf->SetY(-15);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Dicetak oleh: ' . $_SESSION['nama_pengguna'] . ' | SIMARIS RS Bhayangkara', 0, 0, 'C');

$pdf->Output('I', 'Rekap_Semua_Laporan_Pimpinan_' . date('Ymd_His') . '.pdf');
exit;
