<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ==================== FILTER DATA ==================== //
$search  = $_GET['search'] ?? '';
$status  = $_GET['status'] ?? '';
$role    = $_GET['role'] ?? '';

$where = [];
if ($search != '')  $where[] = "(nama_pengguna LIKE '%" . mysqli_real_escape_string($koneksi, $search) . "%' OR username LIKE '%" . mysqli_real_escape_string($koneksi, $search) . "%')";
if ($status != '')  $where[] = "status = '" . mysqli_real_escape_string($koneksi, $status) . "'";
if ($role != '')    $where[] = "role = '" . mysqli_real_escape_string($koneksi, $role) . "'";

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// ==================== QUERY DATA ==================== //
$sql = "SELECT * FROM pengguna $whereSQL ORDER BY nama_pengguna ASC";
$res = mysqli_query($koneksi, $sql);

// ==================== HEADER EXCEL ==================== //
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_User_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// ==================== TABEL EXCEL ==================== //
echo "<table border='1'>";
echo "<tr style='background-color:#48c9b0; color:#fff; font-weight:bold;'>";
echo "<th>No</th>";
echo "<th>Nama Pengguna</th>";
echo "<th>Username</th>";
echo "<th>Level</th>";
echo "<th>Role</th>";
echo "<th>Status</th>";
echo "</tr>";

$no = 1;
while ($row = mysqli_fetch_assoc($res)) {
    $bgColor = ($row['status'] === 'aktif') ? '#d4edda' : '#fff3cd';
    echo "<tr style='background-color:$bgColor'>";
    echo "<td>{$no}</td>";
    echo "<td>" . htmlspecialchars($row['nama_pengguna']) . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['level']) . "</td>";
    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "</tr>";
    $no++;
}
echo "</table>";
exit;
