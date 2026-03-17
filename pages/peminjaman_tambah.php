<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Ambil daftar aset yang kondisinya 'Baik' saja untuk dipinjam
$aset_query = mysqli_query($koneksi, "SELECT * FROM aset WHERE kondisi = 'Baik' ORDER BY nama_aset ASC");

if (isset($_POST['simpan'])) {
    $id_aset = $_POST['id_aset'];
    $nama_peminjam = mysqli_real_escape_string($koneksi, $_POST['nama_peminjam']);
    $tgl_pinjam = $_POST['tanggal_pinjam'];

    // Simpan ke tabel peminjaman
    mysqli_query($koneksi, "INSERT INTO peminjaman (id_aset, nama_peminjam, tanggal_pinjam, status_pinjam) 
                            VALUES ('$id_aset', '$nama_peminjam', '$tgl_pinjam', 'Dipinjam')");

    echo "<script>alert('Data peminjaman berhasil dicatat!');window.location='peminjaman.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Peminjaman | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
        }

        .card {
            width: 420px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            padding: 15px;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="card-header text-center">FORM PEMINJAMAN BARU</div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Pilih Alat/Aset</label>
                    <select name="id_aset" class="form-select" required>
                        <option value="">-- Pilih Alat --</option>
                        <?php while ($a = mysqli_fetch_assoc($aset_query)) : ?>
                            <option value="<?= $a['id_aset'] ?>"><?= $a['nama_aset'] ?> - (<?= $a['lokasi'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Peminjam</label>
                    <input type="text" name="nama_peminjam" class="form-control" placeholder="Input nama petugas/ruangan" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Pinjam</label>
                    <input type="date" name="tanggal_pinjam" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="simpan" class="btn btn-success">Simpan Transaksi</button>
                    <a href="peminjaman.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>