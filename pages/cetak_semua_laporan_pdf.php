<?php
ob_clean();
require(__DIR__ . '/../config/fpdf.php');
include(__DIR__ . '/../config/koneksi.php');

$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : '';

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
$pdf->Cell(0, 7, 'REKAPITULASI SEMUA DATA LAPORAN ' . ($tahun_filter ? 'TAHUN ' . $tahun_filter : ''), 0, 1, 'C');
$pdf->Ln(8);

// ==== DAFTAR TABEL & KONFIGURASI PENGAMBILAN DATA ==== //
// Sesuaikan nama kolom ('columns') dengan yang ada di database kamu
$laporan_data = [
    'Laporan Aset' => [
        'table' => 'aset',
        'columns' => ['nama_aset', 'kategori_aset', 'kondisi', 'tanggal_masuk'],
        'col_date' => 'tanggal_masuk',
        'extra_where' => ''
    ],
    'Laporan Kerusakan' => [
        'table' => 'kerusakan',
        'columns' => ['id_aset', 'status', 'tanggal'],
        'col_date' => 'tanggal',
        'extra_where' => ''
    ],
    'Laporan Perawatan' => [
        'table' => 'perawatan',
        'columns' => ['nama_aset', 'status', 'tanggal'],
        'col_date' => 'tanggal',
        'extra_where' => ''
    ],
    'Laporan Perbaikan' => [
        'table' => 'kerusakan',
        'columns' => ['id_aset', 'status', 'tanggal'],
        'col_date' => 'tanggal',
        'extra_where' => "status IN ('Dalam Perbaikan','Selesai Diperbaiki')"
    ],
    'Laporan Perawatan Berjalan' => [
        'table' => 'perawatan',
        'columns' => ['nama_aset', 'status', 'tanggal'],
        'col_date' => 'tanggal',
        'extra_where' => "status IN ('Belum Dimulai','Sedang Proses')"
    ],
    'Laporan Peminjaman Aset' => [
        'table' => 'peminjaman',
        // Pastikan kolom ini ada di tabel peminjaman
        'columns' => ['id_aset', 'tanggal', 'status'],
        'col_date' => 'tanggal',
        'extra_where' => ''
    ],
    'Laporan Hasil Audit Fisik' => [
        'table' => 'audit_fisik',
        'columns' => ['id_aset', 'auditor', 'kondisi_fisik', 'tanggal_audit'],
        'col_date' => 'tanggal_audit',
        'extra_where' => ''
    ],
    'Laporan Rekapitulasi Nilai Aset' => [
        'table' => 'aset',
        // Pastikan ada kolom harga di tabel aset
        'columns' => ['nama_aset', 'kategori_aset', 'harga', 'tanggal_masuk'],
        'col_date' => 'tanggal_masuk',
        'extra_where' => "harga > 0"
    ],
    'Laporan Kalibrasi' => [
        'table' => 'perawatan',
        'columns' => ['nama_aset', 'status', 'tanggal'],
        'col_date' => 'tanggal',
        'extra_where' => ''
    ],
    'Laporan Pelacakan Lokasi Aset' => [
        'table' => 'aset',
        // Pastikan ada kolom lokasi di tabel aset
        'columns' => ['nama_aset', 'lokasi', 'kategori_aset', 'tanggal_masuk'],
        'col_date' => 'tanggal_masuk',
        'extra_where' => ''
    ]
];

foreach ($laporan_data as $title => $info) {
    // Judul tiap bagian laporan
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, strtoupper($title), 0, 1, 'L');

    // Susun Query beserta kondisi filternya
    $conditions = [];
    if (!empty($tahun_filter)) {
        $conditions[] = "YEAR(" . $info['col_date'] . ") = '$tahun_filter'";
    }
    if (!empty($info['extra_where'])) {
        $conditions[] = $info['extra_where'];
    }

    $whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";
    $query = mysqli_query($koneksi, "SELECT * FROM " . $info['table'] . " $whereClause");

    if (!$query) {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->Cell(260, 7, 'Gagal memuat data / Tabel belum sesuai', 1, 1, 'C');
        $pdf->Ln(5);
        continue;
    }

    // ==== HEADER TABEL BERWARNA ==== //
    $pdf->SetFont('Arial', 'B', 9);
    // Warna background (Teal / Hijau Gelap) menyesuaikan dashboard
    $pdf->SetFillColor(44, 122, 123);
    // Warna teks putih
    $pdf->SetTextColor(255, 255, 255);

    $colWidth = 260 / count($info['columns']);
    foreach ($info['columns'] as $col) {
        // Hapus underscore jadi spasi biar rapi, misal 'nama_aset' jadi 'NAMA ASET'
        $label = strtoupper(str_replace('_', ' ', $col));
        // Tambahkan "true" di akhir agar warna background (Fill) aktif
        $pdf->Cell($colWidth, 8, $label, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // ==== ISI TABEL ==== //
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(0, 0, 0); // Kembalikan warna teks ke hitam

    if (mysqli_num_rows($query) == 0) {
        $pdf->Cell(260, 8, 'Tidak ada data pada periode ini', 1, 1, 'C');
    } else {
        while ($row = mysqli_fetch_assoc($query)) {
            foreach ($info['columns'] as $col) {
                // Cek jika datanya kosong, beri tanda strip (-)
                $isi = !empty($row[$col]) ? $row[$col] : '-';

                // Format rupiah khusus kalau nama kolomnya 'harga'
                if ($col == 'harga' && is_numeric($isi)) {
                    $isi = 'Rp ' . number_format($isi, 0, ',', '.');
                }

                $pdf->Cell($colWidth, 7, substr($isi, 0, 40), 1, 0, 'C');
            }
            $pdf->Ln();
        }
    }
    $pdf->Ln(8); // Jarak antar tabel
}

$pdf->Output();
