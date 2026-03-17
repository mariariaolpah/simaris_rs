
<?php
// auth_check.php
session_start();

// Jika pengguna belum login, arahkan ke halaman login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}
?>
