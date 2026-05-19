<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = mysqli_query($koneksi, "SELECT * FROM perawatan WHERE id=$id");
$p = mysqli_fetch_assoc($query);

if (!$p) {
    echo "<script>alert('Data perawatan tidak ditemukan');window.location='perawatan.php';</script>";
    exit;
}

if (isset($_POST['update'])) {
    $nama_aset     = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $teknisi       = mysqli_real_escape_string($koneksi, $_POST['teknisi']);
    $tanggal       = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $tgl_kalibrasi = mysqli_real_escape_string($koneksi, $_POST['tanggal_kalibrasi_berikutnya']);
    $status        = mysqli_real_escape_string($koneksi, $_POST['status']);

    $update = mysqli_query($koneksi, "UPDATE perawatan SET teknisi='$teknisi', tanggal='$tanggal', tanggal_kalibrasi_berikutnya='$tgl_kalibrasi', status='$status' WHERE id=$id");

    // Sinkronisasi otomatis ke tabel master aset berdasarkan status
    if ($update) {
        if ($status == "Selesai") {
            mysqli_query($koneksi, "UPDATE aset SET kondisi='Baik' WHERE nama_aset='$nama_aset'");
        } else {
            mysqli_query($koneksi, "UPDATE aset SET kondisi='Perlu Perawatan' WHERE nama_aset='$nama_aset'");
        }
        echo "<script>alert('Data perawatan berhasil diperbarui!');window.location='perawatan.php';</script>";
    } else {
        echo "<script>alert('Gagal mengupdate data!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Perawatan | SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6f9;
            min-height: 100vh;
            display: flex;
        }

        #wrapper {
            display: flex;
            width: 100%;
        }

        #page-content-wrapper {
            flex: 1;
            padding: 40px 30px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container-form {
            width: 100%;
            max-width: 650px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2c7a7b;
            box-shadow: 0 0 0 0.2rem rgba(44, 122, 123, 0.25);
        }

        .btn-success {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            border: none;
            font-weight: 500;
            border-radius: 8px;
        }

        .btn-secondary {
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div id="wrapper">

        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="container-form">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-pencil-square"></i> Edit Data Perawatan
                    </div>
                    <div class="card-body p-4">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Aset</label>
                                <input type="text" name="nama_aset" class="form-control bg-light" value="<?= htmlspecialchars($p['nama_aset']) ?>" required readonly>
                                <small class="text-muted">Nama aset dikunci agar riwayat perawatan tidak tertukar.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Teknisi / Vendor</label>
                                <input type="text" name="teknisi" class="form-control" value="<?= htmlspecialchars($p['teknisi']) ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tgl Perawatan Saat Ini</label>
                                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($p['tanggal']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold text-danger">Jadwal Kalibrasi Berikutnya</label>
                                    <input type="date" name="tanggal_kalibrasi_berikutnya" class="form-control border-danger" value="<?= htmlspecialchars($p['tanggal_kalibrasi_berikutnya']) ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Status Tindakan</label>
                                <select name="status" class="form-select" required>
                                    <option value="Belum Dimulai" <?= $p['status'] == 'Belum Dimulai' ? 'selected' : '' ?>>Belum Dimulai</option>
                                    <option value="Sedang Proses" <?= $p['status'] == 'Sedang Proses' ? 'selected' : '' ?>>Sedang Proses</option>
                                    <option value="Selesai" <?= $p['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>
                            </div>

                            <hr>

                            <div class="row g-2 mt-2">
                                <div class="col-sm-8">
                                    <button type="submit" name="update" class="btn btn-success w-100 py-2">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </div>
                                <div class="col-sm-4">
                                    <a href="perawatan.php" class="btn btn-secondary w-100 py-2 text-center">
                                        Batal
                                    </a>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>