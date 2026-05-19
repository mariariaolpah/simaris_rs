<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

require('../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// ================= AMBIL DATA PENCARIAN (OPSIONAL) ================= //
// Kita abaikan parameter 'kategori' dari URL agar PDF SELALU mencetak SEMUA DATA
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

$whereClause = "";
if ($search != '') {
    $whereClause = "WHERE (kerusakan.nama_aset LIKE '%$search%' 
        OR kerusakan.status LIKE '%$search%' 
        OR kerusakan.keterangan LIKE '%$search%' 
        OR kerusakan.pelapor LIKE '%$search%' 
        OR aset.lokasi LIKE '%$search%')";
}

// ================= QUERY DATA (JOIN ASET & PENGURUTAN) ================= //
// ORDER BY aset.kategori_aset ASC memastikan "Medis" (M) muncul duluan sebelum "Non-Medis" (N)
$query = mysqli_query($koneksi, "
    SELECT 
        kerusakan.*,
        aset.kategori_aset,
        aset.lokasi 
    FROM kerusakan 
    LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset
    $whereClause
    ORDER BY aset.kategori_aset ASC, kerusakan.id DESC
");

// ================= PENGATURAN PDF ================= //
$pdf = new FPDF('L', 'mm', 'A4'); // L = Landscape
$pdf->AddPage();

/* ================= KOP SURAT ================= */
$y = 8;

$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) $pdf->Image($logoLeft, 15, $y, 25);
if (file_exists($logoRight)) $pdf->Image($logoRight, 260, $y, 25);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, $y + 5);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');

$pdf->Ln(15);

/* ================= JUDUL ================= */
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'DATA KERUSAKAN SELURUH ASET RS BHAYANGKARA', 0, 1, 'C');
$pdf->Ln(5);

/* ================= HEADER TABEL ================= */
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(72, 201, 176); // Warna Hijau Tosca
$pdf->SetTextColor(255);

// Total Widths = 277mm (A4 Landscape Printable Area)
$widths = [10, 45, 35, 20, 25, 22, 35, 85];

$header = ['No', 'Nama Aset', 'Lokasi Ruangan', 'Kategori', 'Pelapor', 'Tanggal', 'Status', 'Rincian Kerusakan'];

for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 9, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

/* ================= ISI TABEL ================= */
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0);

$i = 1;
if (mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        // Cek jika kosong
        $lokasi = !empty($row['lokasi']) ? $row['lokasi'] : '-';
        $kategori = !empty($row['kategori_aset']) ? $row['kategori_aset'] : '-';
        $pelapor = !empty($row['pelapor']) ? $row['pelapor'] : '-';

        // Membatasi panjang teks agar rapi
        $nama_aset = strlen($row['nama_aset']) > 30 ? substr($row['nama_aset'], 0, 28) . '...' : $row['nama_aset'];
        $keterangan = strlen($row['keterangan']) > 60 ? substr($row['keterangan'], 0, 58) . '...' : $row['keterangan'];

        $pdf->Cell($widths[0], 8, $i++, 1, 0, 'C');
        $pdf->Cell($widths[1], 8, $nama_aset, 1, 0, 'L');
        $pdf->Cell($widths[2], 8, $lokasi, 1, 0, 'L');
        $pdf->Cell($widths[3], 8, $kategori, 1, 0, 'C');
        $pdf->Cell($widths[4], 8, $pelapor, 1, 0, 'L');
        $pdf->Cell($widths[5], 8, date('d-m-Y', strtotime($row['tanggal'])), 1, 0, 'C');
        $pdf->Cell($widths[6], 8, $row['status'], 1, 0, 'C');
        $pdf->Cell($widths[7], 8, $keterangan, 1, 1, 'L');
    }
} else {
    // Jika data kosong
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(array_sum($widths), 10, 'Tidak ada data kerusakan yang ditemukan.', 1, 1, 'C');
}

/* ================= FOOTER ================= */
$pdf->Ln(10);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 5, 'Mengetahui,', 0, 1, 'R');
$pdf->Cell(0, 5, 'Administrator', 0, 1, 'R');

$pdf->Ln(15);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, $_SESSION['nama_pengguna'], 0, 1, 'R');

$pdf->Ln(5);

$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(
    0,
    5,
    'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . $_SESSION['nama_pengguna'],
    0,
    1,
    'R'
);

$pdf->Output();
exit;
