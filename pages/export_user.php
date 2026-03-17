<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['role'] ?? '') != 'admin') {
    die("Akses ditolak.");
}

include(__DIR__ . '/../config/koneksi.php');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Nama', 'Username', 'Level', 'Role', 'Status']);

$sql = "SELECT * FROM pengguna ORDER BY id_pengguna DESC";
$result = mysqli_query($koneksi, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id_pengguna'],
        $row['nama_pengguna'],
        $row['username'],
        $row['level'],
        $row['role'],
        $row['status'] ?? 'aktif'
    ]);
}

fclose($output);
exit;
