<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Ambil daftar lokasi dari master lokasi
$lokasi_list = [];
$lokasi_query = mysqli_query($koneksi, "SELECT * FROM lokasi_aset ORDER BY nama_lokasi ASC");
while ($row = mysqli_fetch_assoc($lokasi_query)) {
    $lokasi_list[] = $row;
}

if (isset($_POST['simpan'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis']);
    $tipe  = mysqli_real_escape_string($koneksi, $_POST['tipe_aset']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi']);
    $tanggal = $_POST['tanggal_masuk'];

    // Fitur Tambahan Skripsi
    $asal_usul = mysqli_real_escape_string($koneksi, $_POST['asal_usul']);
    $harga = mysqli_real_escape_string($koneksi, $_POST['harga']);

    mysqli_query($koneksi, "INSERT INTO aset (nama_aset, jenis, tipe_aset, lokasi, kondisi, asal_usul, harga, tanggal_masuk)
                            VALUES ('$nama', '$jenis', '$tipe', '$lokasi', '$kondisi', '$asal_usul', '$harga', '$tanggal')");

    echo "<script>alert('Aset berhasil ditambahkan');window.location='aset.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Aset | SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container-form {
            width: 420px;
            max-width: 90%;
        }

        .form-control,
        .form-select {
            height: 42px;
            font-size: 0.9rem;
            border-radius: 8px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 16px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.15rem;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>

</head>

<body>

    <div class="container-form">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Tambah Aset / Infrastruktur
            </div>
            <div class="card-body">
                <form method="post">

                    <div class="mb-3">
                        <label class="form-label">Nama Aset</label>
                        <input type="text" name="nama_aset" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <input type="text" name="jenis" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipe Aset</label>
                        <input type="text" name="tipe_aset" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Lokasi</label>
                        <select name="lokasi" class="form-control" required>
                            <option value="">-- Pilih Lokasi --</option>
                            <?php foreach ($lokasi_list as $lok) { ?>
                                <option value="<?= $lok['nama_lokasi'] ?>">
                                    <?= $lok['nama_lokasi'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kondisi</label>
                        <select name="kondisi" class="form-select" required>
                            <option value="">-- Pilih Kondisi --</option>
                            <option value="Baik">Baik</option>
                            <option value="Perlu Perawatan">Perlu Perawatan</option>
                            <option value="Rusak">Rusak</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Asal-Usul Barang</label>
                        <select name="asal_usul" class="form-select" required>
                            <option value="">-- Pilih Asal-Usul --</option>
                            <option value="Pembelian">Pembelian / Anggaran RS</option>
                            <option value="Hibah">Hibah / Bantuan</option>
                            <option value="Sewa">Sewa</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Harga Perolehan / Nilai Aset (Rp)</label>
                        <input type="number" name="harga" class="form-control" placeholder="Contoh: 5000000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" name="simpan" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                        <a href="aset.php" class="btn btn-secondary px-4">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>

</html>