<?php
session_start();

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// Cek role admin
if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak!");
}

include(__DIR__ . '/../config/koneksi.php');

if (!isset($_GET['id'])) {
    die("ID user tidak ditemukan!");
}

$id = intval($_GET['id']);

// Hapus user
mysqli_query($koneksi, "DELETE FROM pengguna WHERE id_pengguna=$id");

// Redirect kembali ke halaman user
header("Location: user.php?msg=deleted");
exit;
