<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Update status dari Menunggu Persetujuan menjadi Dipinjam
    $query = "UPDATE peminjaman SET status_pinjam = 'Dipinjam' WHERE id_pinjam = $id";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Peminjaman disetujui! Status alat menjadi Dipinjam.');window.location='peminjaman.php';</script>";
    } else {
        echo "<script>alert('Gagal menyetujui peminjaman!');window.location='peminjaman.php';</script>";
    }
} else {
    header("Location: peminjaman.php");
}
