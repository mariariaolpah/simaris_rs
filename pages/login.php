<?php
session_start();
include(__DIR__ . '/../config/koneksi.php'); // naik ke root folder

// ==== PROSES LOGIN ====
if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $cek = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username='$username' AND password='$password'");

    if (!$cek) {
        die("Query gagal: " . mysqli_error($koneksi));
    }

    if (mysqli_num_rows($cek) > 0) {
        $user = mysqli_fetch_assoc($cek);
        $_SESSION['id_pengguna'] = $user['id_pengguna'];
        $_SESSION['nama_pengguna'] = $user['nama_pengguna'];
        $_SESSION['level'] = $user['level'];
        header("Location: ../dashboard.php"); // <- jika dashboard di root
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
