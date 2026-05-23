<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include('../config/koneksi.php');

// ================= FILTER UNIVERSAL ================= //
$search   = $_GET['search'] ?? '';
$status   = $_GET['status'] ?? '';
$kategori = $_GET['kategori'] ?? '';
$dari     = $_GET['dari'] ?? '';
$sampai   = $_GET['sampai'] ?? '';

$where = [];
if ($search != '') {
    $where[] = "(peminjaman.nama_peminjam LIKE '%$search%' OR aset.nama_aset LIKE '%$search%' OR aset.lokasi LIKE '%$search%')";
}
if ($status != '') {
    $where[] = "peminjaman.status_pinjam = '$status'";
}
if ($kategori != '' && $kategori != 'semua') {
    if ($kategori == 'medis') $where[] = "aset.kategori_aset = 'Medis'";
    if ($kategori == 'non-medis') $where[] = "aset.kategori_aset = 'Non-Medis'";
}
if ($dari != '') $where[] = "peminjaman.tanggal_pinjam >= '$dari'";
if ($sampai != '') $where[] = "peminjaman.tanggal_pinjam <= '$sampai'";

$whereClause = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT 
            peminjaman.*,
            aset.nama_aset,
            aset.kategori_aset,
            aset.lokasi,
            aset.stok_tersedia
        FROM peminjaman
        JOIN aset ON peminjaman.id_aset = aset.id_aset
        $whereClause
        ORDER BY peminjaman.id_pinjam DESC";

$result = mysqli_query($koneksi, $sql);

// ================= SETUP PDF ================= //
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

$y = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) $pdf->Image($logoLeft, 15, $y, 22);
if (file_exists($logoRight)) $pdf->Image($logoRight, 260, $y, 22);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, $y + 3);
$pdf->Cell(0, 8, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'LAPORAN TRANSAKSI PEMINJAMAN ASET', 0, 1, 'C');

if ($dari != '' && $sampai != '') {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 5, 'Periode: ' . date('d/m/Y', strtotime($dari)) . ' s/d ' . date('d/m/Y', strtotime($sampai)), 0, 1, 'C');
}
$pdf->Ln(4);

// ================= HEADER TABEL ================= //
$w = [10, 40, 70, 28, 45, 25, 25, 30]; // Total 273mm
$header = ['No', 'Peminjam', 'Nama Alat & Stok', 'Kategori', 'Lokasi Asal', 'Tgl Pinjam', 'Tgl Kembali', 'Status'];

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

cetakHeaderPeminjaman($pdf, $w, $header);

// ================= ISI DATA (MULTICELL) ================= //
$no = 1;
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {

        $peminjam_nama = $row['nama_peminjam'] ?? '-';
        $sumber_label = (isset($row['sumber']) && $row['sumber'] == 'App User') ? 'App User' : 'Admin';
        $peminjam = $peminjam_nama . "\n[" . $sumber_label . "]";

        $nama_aset = $row['nama_aset'] ?? '-';
        $stok_tersedia = $row['stok_tersedia'] ?? '0';
        $alat_stok = $nama_aset . "\n[Sisa: " . $stok_tersedia . " Unit]";

        $kategori = $row['kategori_aset'] ?? '-';
        $lokasi = $row['lokasi'] ?? '-';

        $tgl_pinjam = (!empty($row['tanggal_pinjam']) && $row['tanggal_pinjam'] != '0000-00-00') ? date('d/m/Y', strtotime($row['tanggal_pinjam'])) : '-';
        $tgl_kembali = (!empty($row['tanggal_kembali']) && $row['tanggal_kembali'] != '0000-00-00') ? date('d/m/Y', strtotime($row['tanggal_kembali'])) : '-';
        $status = $row['status_pinjam'] ?? '-';

        $maxLine = max(
            2,
            ceil(strlen($nama_aset) / 40) + 1, // Ditambah 1 baris untuk teks sisa stok
            ceil(strlen($kategori) / 18),
            ceil(strlen($lokasi) / 25)
        );
        $tinggi = ($maxLine * 5) + 4;

        if ($pdf->GetY() + $tinggi > 185) {
            $pdf->AddPage();
            cetakHeaderPeminjaman($pdf, $w, $header);
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
        $pdf->MultiCell($w[1] - 2, 5, $peminjam, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + 1, $y + 2);
        $pdf->MultiCell($w[2] - 2, 5, $alat_stok, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + 1, $y + 2);
        $pdf->MultiCell($w[3] - 2, 5, $kategori, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + 1, $y + 2);
        $pdf->MultiCell($w[4] - 2, 5, $lokasi, 0, 'L');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4], $y + 2);
        $pdf->Cell($w[5], 5, $tgl_pinjam, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5], $y + 2);
        $pdf->Cell($w[6], 5, $tgl_kembali, 0, 0, 'C');

        $pdf->SetXY($x + $w[0] + $w[1] + $w[2] + $w[3] + $w[4] + $w[5] + $w[6], $y + 2);
        $pdf->Cell($w[7], 5, $status, 0, 0, 'C');

        $pdf->SetY($y + $tinggi);
    }
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(array_sum($w), 10, 'Tidak ada data peminjaman.', 1, 1, 'C');
}

// ================= FOOTER ================= //
if ($pdf->GetY() > 150) {
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

$pdf->Output();
exit;
