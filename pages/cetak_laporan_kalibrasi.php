<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

ob_clean(); // Mencegah error biner PDF corrupt
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ==================== TANGKAP FILTER ==================== //
$search         = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$statusFilter   = isset($_GET['status_kalibrasi']) ? mysqli_real_escape_string($koneksi, $_GET['status_kalibrasi']) : '';

$where = ["perawatan.tanggal_kalibrasi_berikutnya IS NOT NULL AND perawatan.tanggal_kalibrasi_berikutnya >= '2000-01-01'"];

if ($search !== '') {
    $where[] = "(perawatan.nama_aset LIKE '%$search%' OR aset.lokasi LIKE '%$search%' OR aset.jenis LIKE '%$search%')";
}
if ($kategoriFilter !== '') {
    $where[] = "aset.kategori_aset = '$kategoriFilter'";
}
if ($statusFilter === 'aman') {
    $where[] = "DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) > 7";
} elseif ($statusFilter === 'mendekati') {
    $where[] = "DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) BETWEEN 0 AND 7";
} elseif ($statusFilter === 'terlewat') {
    $where[] = "DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) < 0";
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// ==================== QUERY DATA ==================== //
$sql = "SELECT perawatan.*, aset.lokasi, aset.kategori_aset, aset.jenis, aset.tipe_aset 
        FROM perawatan 
        LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        $whereSQL 
        ORDER BY perawatan.tanggal_kalibrasi_berikutnya ASC";
$res = mysqli_query($koneksi, $sql);


// ==================== SETUP PDF (LANDSCAPE A4) ==================== //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

// ==================== KOP SURAT ==================== //
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
$pdf->Cell(0, 8, 'LAPORAN JADWAL KALIBRASI ASET & ALAT MEDIS', 0, 1, 'C');
$pdf->Ln(4);

// ==================== HEADER TABEL (8 Kolom, Total = 277mm) ==================== //
$w = [10, 50, 45, 35, 25, 27, 27, 58];
$header = ['No', 'Nama Aset', 'Merk / Tipe', 'Lokasi Ruang', 'Kategori', 'Tgl Rawat', 'Tgl Kalibrasi', 'Status / Sisa Waktu'];

function cetakHeaderKalibrasi($pdf, $w, $header)
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

cetakHeaderKalibrasi($pdf, $w, $header);

// ==================== ISI TABEL ==================== //
if (mysqli_num_rows($res) == 0) {
    $pdf->Cell(277, 8, 'Tidak ada data jadwal kalibrasi.', 1, 1, 'C');
} else {
    $no = 1;
    while ($r = mysqli_fetch_assoc($res)) {

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

        // Kalkulasi tinggi baris otomatis (MultiCell)
        $maxLine = max(
            2,
            ceil(strlen($nama_aset) / 28),
            ceil(strlen($merk_tipe) / 22),
            ceil(strlen($lokasi) / 18)
        );
        $tinggi = ($maxLine * 5) + 4;

        // Pindah halaman jika mentok
        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderKalibrasi($pdf, $w, $header);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $currX = $x;

        // Buat kotak (Rect) pinggiran
        for ($i = 0; $i < count($w); $i++) {
            $pdf->Rect($currX, $y, $w[$i], $tinggi);
            $currX += $w[$i];
        }

        // Isi konten
        $pdf->SetXY($x, $y + 2);
        $pdf->Cell($w[0], 5, $no++, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + 1, $y + 2);
        $pdf->MultiCell($w[1] - 2, 5, $nama_aset, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + 1, $y + 2);
        $pdf->MultiCell($w[2] - 2, 5, $merk_tipe, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + 1, $y + 2);
        $pdf->MultiCell($w[3] - 2, 5, $lokasi, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3], $y + 2);
        $pdf->Cell($w[4], 5, $kategori, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y + 2);
        $pdf->Cell($w[5], 5, $t_rawat, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y + 2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($w[6], 5, $t_kalib, 0, 0, 'C');
        $pdf->SetFont('Arial', '', 8);

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6] + 1, $y + 2);
        $pdf->MultiCell($w[7] - 2, 5, $status, 0, 'C');

        $pdf->SetY($y + $tinggi);
    }
}
$pdf->Ln(10);

// ==================== AREA TANDA TANGAN (ADMIN SAJA) ==================== //
if ($pdf->GetY() > 155) {
    $pdf->AddPage();
}

$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);

// Posisi rata kanan (R)
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');

$pdf->Ln(15);

// Nama Admin tercetak tebal
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Ln(5);

// Keterangan waktu dicetak
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Output('I', 'Laporan_Kalibrasi_' . date('Ymd_His') . '.pdf');
exit;
