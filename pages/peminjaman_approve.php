<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Cari tahu id_aset yang sedang diajukan
    $q_pinjam = mysqli_query($koneksi, "SELECT id_aset FROM peminjaman WHERE id_pinjam = $id");
    $d_pinjam = mysqli_fetch_assoc($q_pinjam);
    $id_aset = $d_pinjam['id_aset'];

    // [VALIDASI] Cek ketersediaan stok sebelum menyetujui
    $cek_stok = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT stok_tersedia FROM aset WHERE id_aset = '$id_aset'"));
    if ($cek_stok['stok_tersedia'] <= 0) {
        echo "<script>alert('GAGAL: Tidak bisa disetujui karena stok fisik alat ini sedang kosong/habis!');window.location='peminjaman.php';</script>";
        exit;
    }

    // Update status dari Menunggu Persetujuan menjadi Dipinjam
    $query = "UPDATE peminjaman SET status_pinjam = 'Dipinjam' WHERE id_pinjam = $id";

    if (mysqli_query($koneksi, $query)) {
        // [MODIFIKASI] Kurangi stok karena barang resmi keluar/dipinjam
        mysqli_query($koneksi, "UPDATE aset SET stok_tersedia = stok_tersedia - 1 WHERE id_aset = '$id_aset'");

        echo "<script>alert('Peminjaman disetujui! Status alat menjadi Dipinjam dan stok berkurang.');window.location='peminjaman.php';</script>";
    } else {
        echo "<script>alert('Gagal menyetujui peminjaman!');window.location='peminjaman.php';</script>";
    }
} else {
    header("Location: peminjaman.php");
}
