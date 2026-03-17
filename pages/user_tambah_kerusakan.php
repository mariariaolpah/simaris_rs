<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['level'] != 'user') {
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
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
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

        .container-form {
            width: 100%;
            max-width: 550px;
            margin: 30px auto;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px 12px;
        }
    </style>
</head>

<body>

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

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" name="simpan" class="btn btn-success px-4">
                            <i class="bi bi-send"></i> Kirim
                        </button>
                        <a href="user_data_kerusakan.php" class="btn btn-secondary px-4">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>

</html>