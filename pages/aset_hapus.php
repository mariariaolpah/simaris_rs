<?php
include(__DIR__ . '/../config/koneksi.php');
include(__DIR__ . '/../auth_check.php');

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    $cek = mysqli_query($koneksi, "SELECT * FROM aset WHERE id_aset=$id");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($koneksi, "DELETE FROM aset WHERE id_aset=$id");
        echo "<script>alert('Aset berhasil dihapus');window.location='index.php?page=aset';</script>";
    } else {
        echo "<script>alert('Aset tidak ditemukan');window.location='index.php?page=aset';</script>";
    }
} else {
    echo "<script>alert('ID tidak valid');window.location='index.php?page=aset';</script>";
}
