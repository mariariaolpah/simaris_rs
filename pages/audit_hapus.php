<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Proses Hapus
    $hapus = mysqli_query($koneksi, "DELETE FROM audit_fisik WHERE id_audit = $id");

    if ($hapus) {
        echo "<script>alert('Data audit berhasil dihapus!');window.location='audit_fisik.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data');window.location='audit_fisik.php';</script>";
    }
} else {
    header("Location: audit_fisik.php");
}
