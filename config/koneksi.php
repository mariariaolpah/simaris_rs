<?php
$host = "localhost";
$user = "root";        // username XAMPP
$pass = "";            // password XAMPP biasanya kosong
$db   = "simaris_rs";  // nama database yang sudah dibuat

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
