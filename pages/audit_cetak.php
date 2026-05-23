<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

/* ================= FILTER & PENCARIAN (SINKRON DENGAN HALAMAN UTAMA) ================= */
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';
$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : "";

$whereConditions = [];

if ($search != '') {
    $whereConditions[] = "(audit_fisik.auditor LIKE '%$search%' 
        OR aset.nama_aset LIKE '%$search%' 
        OR audit_fisik.keterangan LIKE '%$search%')";
}

if ($kategori_filter == 'medis') {
    $whereConditions[] = "aset.kategori_aset = 'Medis'";
} elseif ($kategori_filter == 'non-medis') {
    $whereConditions[] = "aset.kategori_aset = 'Non-Medis'";
}

if (!empty($tahun_filter)) {
    $whereConditions[] = "YEAR(audit_fisik.tanggal_audit) = $tahun_filter";
}

$whereClause = "";
if (count($whereConditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// ==================== QUERY DATA ==================== //
$sql = "SELECT 
            audit_fisik.*, 
            aset.nama_aset, 
            aset.lokasi,
            aset.kategori_aset
        FROM audit_fisik 
        INNER JOIN aset ON audit_fisik.id_aset = aset.id_aset 
        $whereClause
        ORDER BY audit_fisik.id_audit DESC";

$result = mysqli_query($koneksi, $sql);

// ==================== PDF ==================== //
$pdf = new FPDF('L', 'mm', 'A4'); // L = Landscape
$pdf->SetAutoPageBreak(false); // Dimatikan agar page break dikontrol manual untuk gambar
$pdf->AddPage();

// ==================== HEADER KOP SURAT ==================== //
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) {
    $pdf->Image($logoLeft, 15, 8, 25);
}

if (file_exists($logoRight)) {
    $pdf->Image($logoRight, 252, 8, 25);
}

// Nama RS
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

// Judul
$pdf->SetY(34);
$pdf->SetFont('Arial', 'B', 15);
$pdf->Cell(0, 10, 'LAPORAN AUDIT FISIK ASET', 0, 1, 'C');

$pdf->Ln(3);

// ==================== TABLE HEADER ==================== //
// Penyesuaian Lebar Kolom (Total: ~277mm untuk fit di kertas A4 Landscape)
$w = [10, 40, 35, 20, 22, 25, 20, 30, 75];

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(72, 201, 176);
$pdf->SetTextColor(255);

$pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Nama Aset', 1, 0, 'C', true);
$pdf->Cell($w[2], 10, 'Lokasi', 1, 0, 'C', true);
$pdf->Cell($w[3], 10, 'Kategori', 1, 0, 'C', true);
$pdf->Cell($w[4], 10, 'Tanggal', 1, 0, 'C', true);
$pdf->Cell($w[5], 10, 'Auditor', 1, 0, 'C', true);
$pdf->Cell($w[6], 10, 'Kondisi', 1, 0, 'C', true);
$pdf->Cell($w[7], 10, 'Bukti Fisik', 1, 0, 'C', true);
$pdf->Cell($w[8], 10, 'Keterangan', 1, 1, 'C', true);

// ==================== ISI DATA ==================== //
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0);

$no = 1;
$rowHeight = 20; // Tinggi baris ditingkatkan (20mm) untuk memuat gambar

while ($row = mysqli_fetch_assoc($result)) {

    // Cek batas bawah halaman (Batas aman: 175). Jika terlewat, tambah halaman baru + cetak header lagi.
    if ($pdf->GetY() > 175) {
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(72, 201, 176);
        $pdf->SetTextColor(255);
        $pdf->Cell($w[0], 10, 'No', 1, 0, 'C', true);
        $pdf->Cell($w[1], 10, 'Nama Aset', 1, 0, 'C', true);
        $pdf->Cell($w[2], 10, 'Lokasi', 1, 0, 'C', true);
        $pdf->Cell($w[3], 10, 'Kategori', 1, 0, 'C', true);
        $pdf->Cell($w[4], 10, 'Tanggal', 1, 0, 'C', true);
        $pdf->Cell($w[5], 10, 'Auditor', 1, 0, 'C', true);
        $pdf->Cell($w[6], 10, 'Kondisi', 1, 0, 'C', true);
        $pdf->Cell($w[7], 10, 'Bukti Fisik', 1, 0, 'C', true);
        $pdf->Cell($w[8], 10, 'Keterangan', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0);
    }

    $nama_aset = !empty($row['nama_aset']) ? $row['nama_aset'] : '-';
    $lokasi = !empty($row['lokasi']) ? $row['lokasi'] : '-';
    $kategori = !empty($row['kategori_aset']) ? $row['kategori_aset'] : '-';

    // Pengecekan field tanggal yang tersedia (audit atau tanggal biasa)
    $tanggal_db = isset($row['tanggal_audit']) ? $row['tanggal_audit'] : (isset($row['tanggal']) ? $row['tanggal'] : null);
    $tanggal = !empty($tanggal_db) ? date('d/m/Y', strtotime($tanggal_db)) : '-';

    $auditor = !empty($row['auditor']) ? $row['auditor'] : '-';

    // Pengecekan field kondisi fisik
    $kondisi_db = isset($row['kondisi_fisik']) ? $row['kondisi_fisik'] : (isset($row['kondisi']) ? $row['kondisi'] : '');
    $kondisi = !empty($kondisi_db) ? $kondisi_db : '-';

    $keterangan = !empty($row['keterangan']) ? $row['keterangan'] : '-';

    // Cetak Kolom 0 s/d 6
    $pdf->Cell($w[0], $rowHeight, $no++, 1, 0, 'C');
    $pdf->Cell($w[1], $rowHeight, substr($nama_aset, 0, 22), 1);
    $pdf->Cell($w[2], $rowHeight, substr($lokasi, 0, 18), 1);
    $pdf->Cell($w[3], $rowHeight, substr($kategori, 0, 11), 1, 0, 'C');
    $pdf->Cell($w[4], $rowHeight, $tanggal, 1, 0, 'C');
    $pdf->Cell($w[5], $rowHeight, substr($auditor, 0, 14), 1);
    $pdf->Cell($w[6], $rowHeight, substr($kondisi, 0, 11), 1, 0, 'C');

    // ================= GAMBAR / BUKTI FISIK ================= //
    $imgX = $pdf->GetX(); // Ambil koordinat X untuk sel Bukti Fisik
    $imgY = $pdf->GetY(); // Ambil koordinat Y

    $gambar = !empty($row['gambar_rusak']) ? $row['gambar_rusak'] : '';
    $imgPath = ($gambar != '') ? realpath(__DIR__ . '/../assets/img/' . $gambar) : false;

    if ($imgPath && file_exists($imgPath)) {
        // Cetak garis selnya (kosong tanpa teks)
        $pdf->Cell($w[7], $rowHeight, '', 1, 0, 'C');
        // Masukkan gambar (di-center secara kasar di dalam ukuran 30x20)
        // Posisi gambar: X + 7mm, Y + 2mm, Ukuran: 16x16mm
        $pdf->Image($imgPath, $imgX + 7, $imgY + 2, 16, 16);
    } else {
        // Jika tidak ada gambar, tampilkan tanda '-'
        $pdf->Cell($w[7], $rowHeight, '-', 1, 0, 'C');
    }

    // Cetak Keterangan & pindah ke baris baru (ln=1)
    $pdf->Cell($w[8], $rowHeight, substr($keterangan, 0, 42), 1, 1);
}

// ==================== FOOTER ==================== //
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);

// Cek jika butuh page break sebelum ttd agar tidak terpotong
if ($pdf->GetY() > 160) {
    $pdf->AddPage();
}

$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R');

$pdf->Ln(15);

$nama_pengguna = isset($_SESSION['nama_pengguna']) ? $_SESSION['nama_pengguna'] : 'Admin';
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, $nama_pengguna, 0, 1, 'R');

$pdf->Ln(5);

$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(
    0,
    6,
    'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $nama_pengguna,
    0,
    1,
    'R'
);

// ==================== OUTPUT ==================== //
$pdf->Output('I', 'laporan_audit_fisik.pdf');
exit;
