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

    // Cari tahu aset mana yang sedang dikembalikan
    $q_pinjam = mysqli_query($koneksi, "SELECT id_aset FROM peminjaman WHERE id_pinjam = $id");
    $d_pinjam = mysqli_fetch_assoc($q_pinjam);
    $id_aset = $d_pinjam['id_aset'];

    // Update status dan tanggal kembali di tabel peminjaman
    $query = "UPDATE peminjaman SET 
                tanggal_kembali = '$tgl_sekarang', 
                status_pinjam = 'Dikembalikan' 
              WHERE id_pinjam = $id";

    if (mysqli_query($koneksi, $query)) {
        // [MODIFIKASI] Tambah kembali stok_tersedia karena alat sudah di gudang
        mysqli_query($koneksi, "UPDATE aset SET stok_tersedia = stok_tersedia + 1 WHERE id_aset = '$id_aset'");

        echo "<script>alert('Alat telah dikembalikan dan stok otomatis bertambah!');window.location='peminjaman.php';</script>";
    } else {
        echo "<script>alert('Gagal memproses data');window.location='peminjaman.php';</script>";
    }
} else {
    header("Location: peminjaman.php");
}
