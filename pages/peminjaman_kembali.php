<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $tgl_sekarang = date('Y-m-d');

    // Update status dan tanggal kembali di tabel peminjaman
    $query = "UPDATE peminjaman SET 
                tanggal_kembali = '$tgl_sekarang', 
                status_pinjam = 'Dikembalikan' 
              WHERE id_pinjam = $id";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Alat telah dikembalikan');window.location='peminjaman.php';</script>";
    } else {
        echo "<script>alert('Gagal memproses data');window.location='peminjaman.php';</script>";
    }
} else {
    header("Location: peminjaman.php");
}
