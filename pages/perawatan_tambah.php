<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ambil data kerusakan
$dataKerusakan = mysqli_query($koneksi, "SELECT id, nama_aset FROM kerusakan");

if (isset($_POST['simpan'])) {
    $id_kerusakan = mysqli_real_escape_string($koneksi, $_POST['id_kerusakan']);
    $nama_aset    = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $teknisi      = mysqli_real_escape_string($koneksi, $_POST['teknisi']);
    $tanggal      = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $status       = mysqli_real_escape_string($koneksi, $_POST['status']);

    mysqli_query($koneksi, "INSERT INTO perawatan 
        (id_kerusakan, nama_aset, teknisi, tanggal, status) 
        VALUES 
        ('$id_kerusakan','$nama_aset','$teknisi','$tanggal','$status')");

    echo "<script>alert('Data perawatan berhasil ditambahkan');window.location='perawatan.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Perawatan | SIMARIS RS Bhayangkara</title>
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
            width: 100%;
            max-width: 550px;
            margin: 0 auto;
            padding: 20px 0;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.2rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px 12px;
        }

        .btn-success {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: linear-gradient(90deg, #319795, #17a673);
            transform: translateY(-2px);
        }

        .btn-secondary {
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="container-form">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Tambah Data Perawatan
            </div>
            <div class="card-body">
                <form method="post">

                    <!-- PILIH KERUSAKAN -->
                    <div class="mb-3">
                        <label class="form-label">Pilih Data Kerusakan</label>
                        <select name="id_kerusakan" class="form-select" required>
                            <option value="">-- Pilih Aset Rusak --</option>
                            <?php while ($k = mysqli_fetch_assoc($dataKerusakan)) : ?>
                                <option value="<?= $k['id']; ?>">
                                    <?= htmlspecialchars($k['nama_aset']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- NAMA ASET -->
                    <div class="mb-3">
                        <label class="form-label">Nama Aset</label>
                        <input type="text" name="nama_aset" class="form-control" placeholder="Masukkan nama aset" required>
                    </div>

                    <!-- TEKNISI -->
                    <div class="mb-3">
                        <label class="form-label">Teknisi</label>
                        <input type="text" name="teknisi" class="form-control" placeholder="Nama teknisi" required>
                    </div>

                    <!-- TANGGAL -->
                    <div class="mb-3">
                        <label class="form-label">Tanggal Perawatan</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- STATUS -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="Belum Dimulai">Belum Dimulai</option>
                            <option value="Sedang Proses">Sedang Proses</option>
                            <option value="Selesai">Selesai</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" name="simpan" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                        <a href="perawatan.php" class="btn btn-secondary px-4">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</body>

</html>