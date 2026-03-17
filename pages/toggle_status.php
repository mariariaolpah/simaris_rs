<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || ($_SESSION['role'] ?? '') != 'admin') {
    die("Akses ditolak.");
}

include(__DIR__ . '/../config/koneksi.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die("ID pengguna tidak valid.");
}

// Ambil status sekarang
$res = mysqli_query($koneksi, "SELECT status FROM pengguna WHERE id_pengguna=$id");
if (!$res || mysqli_num_rows($res) == 0) {
    die("User tidak ditemukan.");
}

$row = mysqli_fetch_assoc($res);
$newStatus = ($row['status'] ?? 'aktif') == 'aktif' ? 'nonaktif' : 'aktif';

// Update status
$update = mysqli_query($koneksi, "UPDATE pengguna SET status='$newStatus' WHERE id_pengguna=$id");
if (!$update) {
    die("Gagal update status: " . mysqli_error($koneksi));
}

// Kembali ke halaman sebelumnya
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
