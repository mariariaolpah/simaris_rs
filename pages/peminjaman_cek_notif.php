<?php
include(__DIR__ . '/../config/koneksi.php');

// Hitung berapa baris yang statusnya 'Menunggu Persetujuan'
$query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM peminjaman WHERE status_pinjam = 'Menunggu Persetujuan'");
$data = mysqli_fetch_assoc($query);

// Lempar hasilnya dalam format JSON agar bisa dibaca Javascript
echo json_encode(['pending_count' => (int)$data['total']]);
