<?php
session_start();

// 1. Cek apakah pengguna sudah login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Cek aman dengan isset() agar PHP tidak protes saat session kosong
if (isset($_SESSION['level']) && strtolower(trim($_SESSION['level'])) == 'admin') {
    header("Location: dashboard.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$aset = mysqli_query($koneksi, "SELECT * FROM aset");

if (isset($_POST['simpan'])) {
    $nama_aset = $_POST['nama_aset'];
    $tanggal = date('Y-m-d');
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];

    $query = mysqli_query($koneksi, "
        INSERT INTO kerusakan (nama_aset, tanggal, status, keterangan) 
        VALUES ('$nama_aset', '$tanggal', '$status', '$keterangan')
    ");

    if ($query) {
        echo "<script>alert('Laporan berhasil dikirim!'); window.location='user_data_kerusakan.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan laporan');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Buat Laporan Kerusakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Poppins', sans-serif;
        }

        #page-content-wrapper {
            margin-left: 230px;
            padding: 25px 35px;
        }

        .container-form {
            max-width: 550px;
            margin: 0 auto;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: white;
            font-weight: bold;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
        }

        .btn-success {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/sidebar_user.php'; ?>

    <div id="page-content-wrapper">
        <h4 class="fw-bold text-dark mb-4">Buat Laporan Kerusakan</h4>

        <div class="container-form">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bug"></i> Laporan Kerusakan Aset
                </div>

                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Aset</label>
                            <select name="nama_aset" class="form-control" required>
                                <option value="">-- Pilih Aset --</option>
                                <?php while ($row = mysqli_fetch_assoc($aset)) : ?>
                                    <option value="<?= $row['nama_aset']; ?>"><?= $row['nama_aset']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Status Kerusakan</label>
                            <select name="status" class="form-control" required>
                                <option value="Rusak">Rusak</option>
                                <option value="Perlu Perawatan">Perlu Perawatan</option>
                                <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan</label>
                            <textarea name="keterangan" class="form-control" required></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="user_data_kerusakan.php" class="btn btn-secondary px-4">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                            <button type="submit" name="simpan" class="btn btn-success px-4">
                                <i class="bi bi-send"></i> Kirim
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>