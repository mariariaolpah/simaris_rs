<?php
session_start();
include(__DIR__ . '/../config/koneksi.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($koneksi, "DELETE FROM peminjaman WHERE id_pinjam = $id");
    echo "<script>alert('Data berhasil dihapus');window.location='peminjaman.php';</script>";
}
