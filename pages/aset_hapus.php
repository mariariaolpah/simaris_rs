<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    $cek = mysqli_query($koneksi, "SELECT * FROM aset WHERE id_aset=$id");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($koneksi, "DELETE FROM aset WHERE id_aset=$id");
        echo "<script>alert('Aset berhasil dihapus');window.location='aset.php';</script>";
    } else {
        echo "<script>alert('Aset tidak ditemukan');window.location='aset.php';</script>";
    }
} else {
    echo "<script>alert('ID tidak valid');window.location='aset.php';</script>";
}
