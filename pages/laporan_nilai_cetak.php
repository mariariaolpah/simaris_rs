<?php
session_start();

if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

ob_start();
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

// =======================
// FILTER PENCARIAN SINKRON
// =======================
$search         = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
$dari           = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
$sampai         = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

$where = ["harga > 0"];
if ($search != '') $where[] = "(nama_aset LIKE '%$search%' OR asal_usul LIKE '%$search%')";
if ($kategoriFilter != '') $where[] = "kategori_aset = '$kategoriFilter'";
if ($dari != '') $where[] = "tanggal_masuk >= '$dari'";
if ($sampai != '') $where[] = "tanggal_masuk <= '$sampai'";

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// =======================
// QUERY DATA
// =======================
$sql = "SELECT * FROM aset $whereSQL ORDER BY tanggal_masuk DESC";
$result = mysqli_query($koneksi, $sql);

// =======================
// INISIALISASI PDF
// =======================
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false);

// =======================
// KOP SURAT
// =======================
$y = 8;
$logoLeft  = realpath(__DIR__ . '/../assets/img/logo_dokpol.png');
$logoRight = realpath(__DIR__ . '/../assets/img/logo_rs.jpg');

if (file_exists($logoLeft)) $pdf->Image($logoLeft, 15, $y, 22);
if (file_exists($logoRight)) $pdf->Image($logoRight, 260, $y, 22);

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetXY(0, 12);
$pdf->Cell(0, 6, 'RUMKIT BHAYANGKARA TK. III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Jl. A. Yani Km. 3,5 Banjarmasin 70235', 0, 1, 'C');
$pdf->Ln(8);

// =======================
// JUDUL LAPORAN
// =======================
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN REKAPITULASI NILAI DEPRESIASI ASET', 0, 1, 'C');

$subHeader = [];
if ($kategoriFilter != '') $subHeader[] = "Kategori: " . $kategoriFilter;
if ($dari != '' && $sampai != '') $subHeader[] = "Periode: " . date('d/m/Y', strtotime($dari)) . " s/d " . date('d/m/Y', strtotime($sampai));

if (count($subHeader) > 0) {
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 5, implode(' | ', $subHeader), 0, 1, 'C');
}
$pdf->Ln(4);

// =======================
// HEADER TABEL (8 Kolom, Sesuai Web)
// =======================
// Total Lebar: 10+60+25+22+20+45+40+55 = 277 mm
$w = [10, 60, 25, 22, 20, 45, 40, 55];
$header = ['No', 'Nama Aset', 'Kategori', 'Thn Masuk', 'Umur Eko.', 'Harga Beli Awal', 'Susut / Tahun', 'Nilai Saat Ini'];

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

cetakHeaderNilai($pdf, $w, $header);

// =======================
// ISI TABEL & PERHITUNGAN LOGIKA PDF
// =======================
$no = 1;
$total_harga_awal = 0;
$total_nilai_saat_ini = 0;
$tahun_sekarang = date('Y');

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {

        $nama_aset = $row['nama_aset'];
        $kategori  = $row['kategori_aset'] ?? '-';
        $harga     = $row['harga'];
        $umur      = isset($row['umur_ekonomis']) ? (int)$row['umur_ekonomis'] : 0;
        $tgl_masuk = $row['tanggal_masuk'];
        $thn_masuk = date('Y', strtotime($tgl_masuk));

        // LOGIKA PENYUSUTAN
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

        // Akumulasi Total Keseluruhan
        $total_harga_awal += $harga;
        $total_nilai_saat_ini += $nilai_sekarang;

        // Membatasi teks agar rapi
        if (strlen($nama_aset) > 30) $nama_aset = substr($nama_aset, 0, 27) . '...';

        // Pindah halaman jika tabel mentok bawah
        if ($pdf->GetY() > 180) {
            $pdf->AddPage();
            cetakHeaderNilai($pdf, $w, $header);
        }

        $pdf->Cell($w[0], 8, $no++, 1, 0, 'C');
        $pdf->Cell($w[1], 8, $nama_aset, 1, 0, 'L');
        $pdf->Cell($w[2], 8, $kategori, 1, 0, 'C');
        $pdf->Cell($w[3], 8, $thn_masuk, 1, 0, 'C');
        $pdf->Cell($w[4], 8, ($umur > 0 ? $umur . " Thn" : "-"), 1, 0, 'C');
        $pdf->Cell($w[5], 8, 'Rp ' . number_format($harga, 0, ',', '.'), 1, 0, 'R');
        $pdf->SetTextColor(220, 53, 69); // Warna merah untuk susut
        $pdf->Cell($w[6], 8, '- Rp ' . number_format($susut_per_tahun, 0, ',', '.'), 1, 0, 'R');
        $pdf->SetTextColor(0); // Kembali ke hitam
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($w[7], 8, 'Rp ' . number_format($nilai_sekarang, 0, ',', '.'), 1, 1, 'R');
        $pdf->SetFont('Arial', '', 8);
    }
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(array_sum($w), 10, 'Tidak ada data aset ternilai.', 1, 1, 'C');
}

// =======================
// BARIS TOTAL AKUMULASI (FOOTER TABEL)
// =======================
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(240, 240, 240);

$pdf->Cell($w[0] + $w[1] + $w[2] + $w[3] + $w[4], 10, 'TOTAL KESELURUHAN NILAI INVESTASI ASET', 1, 0, 'R', true);
$pdf->Cell($w[5], 10, 'Rp ' . number_format($total_harga_awal, 0, ',', '.'), 1, 0, 'R', true);
$pdf->Cell($w[6], 10, '', 1, 0, 'C', true); // Kosongkan kolom susut total agar tidak rancu
$pdf->SetTextColor(25, 135, 84); // Hijau untuk total akhir
$pdf->Cell($w[7], 10, 'Rp ' . number_format($total_nilai_saat_ini, 0, ',', '.'), 1, 1, 'R', true);
$pdf->SetTextColor(0);

// =======================
// AREA TANDA TANGAN
// =======================
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
$pdf->Output('I', 'Laporan_Nilai_Depresiasi_Aset.pdf');
exit;
