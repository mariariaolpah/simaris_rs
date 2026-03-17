<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA ==================== //
$search  = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$status  = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$dari    = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
$sampai  = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

$where = [];
if ($search != '')  $where[] = "(nama_aset LIKE '%$search%' OR keterangan LIKE '%$search%')";
if ($status != '')  $where[] = "status = '$status'";
if ($dari != '')    $where[] = "tanggal >= '$dari'";
if ($sampai != '')  $where[] = "tanggal <= '$sampai'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ==================== QUERY DATA ==================== //
$sql = "SELECT * FROM kerusakan $whereSQL ORDER BY tanggal DESC";
$res = mysqli_query($koneksi, $sql);

// ==================== HEADER EXPORT ==================== //
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=laporan_kerusakan_aset_' . date('Ymd_His') . '.csv');

// ==================== TULIS KE FILE ==================== //
$out = fopen('php://output', 'w');

// Header kolom Excel
fputcsv($out, ['No', 'Nama Aset', 'Status', 'Tanggal', 'Keterangan']);

// Isi data
$no = 1;
while ($r = mysqli_fetch_assoc($res)) {
    fputcsv($out, [
        $no++,
        $r['nama_aset'],
        $r['status'],
        $r['tanggal'],
        $r['keterangan']
    ]);
}

fclose($out);
exit;
