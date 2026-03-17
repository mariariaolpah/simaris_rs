<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = mysqli_query($koneksi, "SELECT * FROM kerusakan WHERE id=$id");
$k = mysqli_fetch_assoc($query);

if (!$k) {
    echo "<script>alert('Data kerusakan tidak ditemukan');window.location='kerusakan.php';</script>";
    exit;
}

if (isset($_POST['update'])) {
    $nama_aset  = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $status     = mysqli_real_escape_string($koneksi, $_POST['status']);
    $tanggal    = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    mysqli_query($koneksi, "UPDATE kerusakan SET nama_aset='$nama_aset', status='$status', tanggal='$tanggal', keterangan='$keterangan' WHERE id=$id");
    echo "<script>alert('Data kerusakan berhasil diubah');window.location='kerusakan.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Kerusakan | SIMARIS RS Bhayangkara</title>
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

        .form-control {
            border-radius: 10px;
            padding: 10px 12px;
        }

        h3 {
            text-align: center;
            margin-bottom: 20px;
            font-weight: 700;
            color: #155e75;
        }

        .container-form {
            width: 100%;
            max-width: 550px;
            margin: 30px auto;
        }
    </style>
</head>

<body>
    <div class="container-form">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Edit Data Kerusakan
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Nama Aset</label>
                        <input type="text" name="nama_aset" class="form-control"
                            value="<?= htmlspecialchars($k['nama_aset']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="Rusak" <?= $k['status'] == 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                            <option value="Perlu Perawatan" <?= $k['status'] == 'Perlu Perawatan' ? 'selected' : '' ?>>Perlu Perawatan</option>
                            <option value="Dalam Perbaikan" <?= $k['status'] == 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                            <option value="Selesai Diperbaiki" <?= $k['status'] == 'Selesai Diperbaiki' ? 'selected' : '' ?>>Selesai Diperbaiki</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control"
                            value="<?= htmlspecialchars($k['tanggal']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($k['keterangan']) ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" name="update" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Update
                        </button>
                        <a href="kerusakan.php" class="btn btn-secondary px-4">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>