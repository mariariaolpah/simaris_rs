<?php
ob_clean();
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// ==== LOGO ==== //
$pdf->Image(__DIR__ . '/../assets/img/logo_dokpol.png', 20, 10, 18);
$pdf->Image(__DIR__ . '/../assets/img/logo_rs.jpg', 255, 10, 18);

// ==== HEADER ==== //
$pdf->SetFont('Arial', 'B', 16);
$pdf->Ln(5);
$pdf->Cell(0, 10, 'RUMKIT BHAYANGKARA TK.III BANJARMASIN', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Ln(3);
$pdf->Cell(0, 7, 'LAPORAN SEMUA DATA', 0, 1, 'C');
$pdf->Ln(8);

// ==== KOLOM SESUAI LAPORAN WEB ==== //
$customOrder = [
    "aset"       => ['nama_aset', 'jenis', 'tipe_aset', 'lokasi', 'kondisi', 'tanggal_masuk'],
    "kerusakan"  => ['nama_aset', 'status', 'tanggal', 'keterangan'],
    "perawatan"  => ['nama_aset', 'teknisi', 'tanggal', 'status'],
    "perbaikan"  => ['nama_aset', 'status', 'tanggal', 'keterangan'],
    "pengguna"   => ['nama_pengguna', 'level']
];

// ==== SORTING SUPAYA TERBARU DI ATAS ==== //
$sortKey = [
    "aset"       => "tanggal_masuk",
    "kerusakan"  => "tanggal",
    "perawatan"  => "tanggal",
    "perbaikan"  => "tanggal",
    "pengguna"   => "nama_pengguna"
];

// ==== LAPORAN ==== //
$laporan_semua = [
    ['Laporan Aset', "aset"],
    ['Laporan Kerusakan', "kerusakan"],
    ['Laporan Perawatan', "perawatan"],
    ['Laporan Perbaikan', "perbaikan"],
    ['Laporan Perawatan Berjalan', "perawatan", "WHERE status IN ('Belum Dimulai','Sedang Proses')"],
    ['Laporan Manajemen User', "pengguna"]
];

// Ambil filter GET
$search       = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$dari         = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
$sampai       = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

$whereKerusakan = [];
if ($search !== '') $whereKerusakan[] = "(nama_aset LIKE '%$search%' OR keterangan LIKE '%$search%')";
if ($statusFilter !== '') $whereKerusakan[] = "status = '$statusFilter'";
if ($dari !== '') $whereKerusakan[] = "tanggal >= '$dari'";
if ($sampai !== '') $whereKerusakan[] = "tanggal <= '$sampai'";
$whereKerusakanSQL = count($whereKerusakan) ? 'WHERE ' . implode(' AND ', $whereKerusakan) : '';

// ==== LOOP LAPORAN ==== //
foreach ($laporan_semua as $lap) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 7, strtoupper($lap[0]), 0, 1);

    $tableName = $lap[1];
    $filter = $lap[2] ?? "";

    // Khusus kerusakan, pakai filter GET
    if ($tableName == 'kerusakan' && empty($lap[2])) {
        $filter = $whereKerusakanSQL;
    }

    // ==== LAPORAN MANAJEMEN USER KHUSUS ==== //
    if ($tableName == 'pengguna') {

        // Query urut berdasarkan id_pengguna (kolom yang benar)
        $query = "SELECT * FROM pengguna ORDER BY id_pengguna DESC";
        $result = mysqli_query($koneksi, $query);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Ln(2);
        if ($pdf->GetY() > 170) $pdf->AddPage();

        // Header tabel
        $columns = ['No', 'Nama Pengguna', 'Username', 'Level', 'Role', 'Status'];
        $colWidth = 260 / count($columns);

        $pdf->SetFillColor(72, 201, 176); // Header warna hijau
        $pdf->SetTextColor(255);          // Teks putih
        $pdf->SetFont('Arial', 'B', 9);

        foreach ($columns as $col) {
            $pdf->Cell($colWidth, 8, $col, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Data tabel
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0);

        if (!$result || mysqli_num_rows($result) == 0) {
            $pdf->Cell(260, 8, 'Tidak ada data tersedia.', 1, 1, 'C');
        } else {
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {

                // 1 baris per user → tabel rapi
                $pdf->Cell($colWidth, 7, $no, 1, 0, 'C');
                $pdf->Cell($colWidth, 7, $row['nama_pengguna'], 1, 0, 'C');
                $pdf->Cell($colWidth, 7, $row['username'], 1, 0, 'C');
                $pdf->Cell($colWidth, 7, $row['level'], 1, 0, 'C');
                $pdf->Cell($colWidth, 7, $row['role'], 1, 0, 'C');
                $pdf->Cell($colWidth, 7, $row['status'], 1, 0, 'C');

                $pdf->Ln();
                $no++;
            }
        }

        $pdf->Ln(10);
        continue;
    }

    // ==== LAPORAN LAINNYA ==== //
    $queryColumns = mysqli_query($koneksi, "SHOW COLUMNS FROM $tableName");
    $actualCols   = array_column(mysqli_fetch_all($queryColumns, MYSQLI_ASSOC), 'Field');
    $columns      = isset($customOrder[$tableName]) ? array_values(array_intersect($customOrder[$tableName], $actualCols)) : $actualCols;
    array_unshift($columns, 'no');

    // Header tabel
    $colWidth = 260 / count($columns);
    $pdf->SetFillColor(72, 201, 176); // Header warna hijau untuk semua laporan
    $pdf->SetTextColor(255);
    $pdf->SetFont('Arial', 'B', 9);
    foreach ($columns as $col) {
        $label = ($col == 'no') ? 'No' : ucfirst(str_replace('_', ' ', $col));
        $pdf->Cell($colWidth, 8, $label, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Sorting query untuk masing-masing tabel
    if ($tableName == 'aset') {
        // Ambil data aset persis urutan di database
        $query = "SELECT * FROM aset ORDER BY tanggal_masuk DESC, id_aset DESC";
    } elseif ($tableName == 'perbaikan') {
        $query = "SELECT * FROM kerusakan
              WHERE status IN ('Dalam Perbaikan', 'Selesai Diperbaiki')
              $filter
              ORDER BY tanggal DESC, id DESC";
    } elseif ($tableName == 'kerusakan') {
        $query = "SELECT * FROM kerusakan 
              $filter 
              ORDER BY tanggal DESC, id DESC";
    } else {
        $orderColumn = $sortKey[$tableName] ?? $actualCols[0];
        $query = "SELECT * FROM $tableName 
              $filter 
              ORDER BY $orderColumn DESC";
    }


    $result = mysqli_query($koneksi, $query);

    // Data tabel
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0);
    if (!$result || mysqli_num_rows($result) == 0) {
        $pdf->Cell(260, 8, 'Tidak ada data tersedia.', 1, 1, 'C');
    } else {
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            foreach ($columns as $col) {
                $value = ($col == 'no') ? $no : ($row[$col] ?? '-');
                $pdf->Cell($colWidth, 7, mb_substr($value, 0, 40), 1, 0, 'C');
            }
            $pdf->Ln();
            $no++;
        }
    }
    $pdf->Ln(10);
}

// ==================== TANDA TANGAN ==================== //
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Banjarmasin, ' . date('d F Y'), 0, 1, 'R');
$pdf->Cell(0, 6, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(8);

// Jika ada gambar tanda tangan kepala RS bisa ditambahkan
// $ttdPath = realpath(__DIR__ . '/../assets/img/ttd_kepala.png');
// if ($ttdPath && file_exists($ttdPath)) {
//     $yPos = $pdf->GetY();
//     $pdf->Image($ttdPath, 230, $yPos, 35);
//     $pdf->Ln(25);
// }

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, 'Administrator', 0, 1, 'R'); // ganti Kepala RS jadi Administrator
$pdf->Ln(5);

// Footer tambahan
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 6, 'Dicetak pada: ' . date('d-m-Y H:i:s') . ' oleh ' . ($_SESSION['nama_pengguna'] ?? 'User'), 0, 1, 'R');


// Output PDF
$pdf->Output('I', 'Semua_Laporan_' . date('Ymd_His') . '.pdf');
