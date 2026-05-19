<?php
session_start();
include(__DIR__ . '/../config/koneksi.php');

$id = $_GET['id'];

mysqli_query($koneksi, "
    DELETE FROM riwayat_lokasi 
    WHERE id_riwayat=$id
");

echo "
<script>
    alert('Riwayat berhasil dihapus!');
    window.location='aset_pindah.php';
</script>";
