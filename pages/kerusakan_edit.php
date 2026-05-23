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
    $nama_aset   = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $status_baru = mysqli_real_escape_string($koneksi, $_POST['status']);
    $tanggal     = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $keterangan  = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $pelapor     = mysqli_real_escape_string($koneksi, $_POST['pelapor']);
    $teknisi     = mysqli_real_escape_string($koneksi, $_POST['teknisi']);

    $status_lama = $k['status']; // Tangkap status sebelum diubah

    // QUERY UPDATE DATA ASLI 
    $update_query = mysqli_query($koneksi, "UPDATE kerusakan SET 
                    nama_aset='$nama_aset', 
                    status='$status_baru', 
                    tanggal='$tanggal', 
                    keterangan='$keterangan',
                    pelapor='$pelapor',
                    teknisi='$teknisi' 
                    WHERE id=$id");

    // [MODIFIKASI] SINKRONISASI STOK OTOMATIS BERDASARKAN PERUBAHAN STATUS
    if ($update_query) {
        // Jika statusnya berubah (misal dari Rusak menjadi Selesai Diperbaiki)
        if ($status_lama != $status_baru) {

            // 1. Cabut/Kurangi angka dari kategori stok yang LAMA
            if ($status_lama == "Rusak") {
                mysqli_query($koneksi, "UPDATE aset SET stok_rusak = stok_rusak - 1 WHERE nama_aset='$nama_aset'");
            } elseif ($status_lama == "Perlu Perawatan" || $status_lama == "Dalam Perbaikan") {
                mysqli_query($koneksi, "UPDATE aset SET stok_perawatan = stok_perawatan - 1 WHERE nama_aset='$nama_aset'");
            } elseif ($status_lama == "Selesai Diperbaiki") {
                // Jaga-jaga kalau barang yang sudah selesai ternyata rusak lagi saat di edit
                mysqli_query($koneksi, "UPDATE aset SET stok_tersedia = stok_tersedia - 1 WHERE nama_aset='$nama_aset'");
            }

            // 2. Masukkan/Tambahkan angka ke kategori stok yang BARU
            if ($status_baru == "Rusak") {
                mysqli_query($koneksi, "UPDATE aset SET stok_rusak = stok_rusak + 1 WHERE nama_aset='$nama_aset'");
            } elseif ($status_baru == "Perlu Perawatan" || $status_baru == "Dalam Perbaikan") {
                mysqli_query($koneksi, "UPDATE aset SET stok_perawatan = stok_perawatan + 1 WHERE nama_aset='$nama_aset'");
            } elseif ($status_baru == "Selesai Diperbaiki") {
                mysqli_query($koneksi, "UPDATE aset SET stok_tersedia = stok_tersedia + 1 WHERE nama_aset='$nama_aset'");
            }
        }

        echo "<script>alert('Data laporan dan stok berhasil diperbarui!');window.location='kerusakan.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data kerusakan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Data Kerusakan | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
            color: #333;
            font-family: 'Poppins', sans-serif;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #page-content-wrapper {
            flex: 1;
            max-width: 100%;
            overflow-x: hidden;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .content {
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 15px 20px;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
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

        .highlight-danger {
            background-color: #fff5f5;
            border-left: 4px solid #e53e3e;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-pencil-square"></i> EDIT PELAPORAN KERUSAKAN</h4>
                <div class="small fw-medium">
                    <i class="bi bi-person-circle-fill"></i> <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                </div>
            </div>

            <div class="content">
                <div class="card" style="max-width: 750px; margin: 0 auto;">
                    <div class="card-header">
                        <i class="bi bi-file-earmark-diff-fill"></i>
                        <span>Form Perbarui Status Pengaduan Kerusakan</span>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST">

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Nama Aset / Komponen Alat</label>
                                    <input type="text" name="nama_aset" class="form-control bg-light" value="<?= htmlspecialchars($k['nama_aset']) ?>" required readonly>
                                    <div class="form-text text-muted">Nama aset terkunci agar akurat.</div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Nama Pelapor</label>
                                    <input type="text" name="pelapor" class="form-control" value="<?= htmlspecialchars($k['pelapor'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="highlight-danger">
                                <h6 class="fw-bold text-danger mb-1"><i class="bi bi-shield-fill-exclamation"></i> Pembaruan Status Tindak Lanjut</h6>
                                <small class="text-secondary d-block">Merubah status ke **"Selesai Diperbaiki"** akan mengembalikan dan memulihkan stok **Tersedia** di menu inventaris secara otomatis.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">Status Perbaikan</label>
                                    <select name="status" class="form-select border-danger" required>
                                        <option value="Rusak" <?= $k['status'] == 'Rusak' ? 'selected' : '' ?>>Rusak (Mati Total)</option>
                                        <option value="Perlu Perawatan" <?= $k['status'] == 'Perlu Perawatan' ? 'selected' : '' ?>>Perlu Perawatan</option>
                                        <option value="Dalam Perbaikan" <?= $k['status'] == 'Dalam Perbaikan' ? 'selected' : '' ?>>Dalam Perbaikan</option>
                                        <option value="Selesai Diperbaiki" <?= $k['status'] == 'Selesai Diperbaiki' ? 'selected' : '' ?>>Selesai Diperbaiki</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">Ditangani Teknisi</label>
                                    <select name="teknisi" class="form-select border-primary">
                                        <option value="">-- Pilih Teknisi --</option>
                                        <?php
                                        // Menarik nama pengguna dengan level teknisi, lalu di-selected jika sesuai dengan yang di database
                                        $query_teknisi = mysqli_query($koneksi, "SELECT nama_pengguna FROM pengguna WHERE level = 'teknisi' AND status = 'aktif'");
                                        while ($t = mysqli_fetch_assoc($query_teknisi)) {
                                            $selected = ($t['nama_pengguna'] == $k['teknisi']) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($t['nama_pengguna']) . "' $selected>" . htmlspecialchars($t['nama_pengguna']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label">Tanggal Pembaruan</label>
                                    <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($k['tanggal']) ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Rincian Keluhan / Catatan Hasil Penanganan Teknis</label>
                                <textarea name="keterangan" class="form-control" rows="4" required><?= htmlspecialchars($k['keterangan']) ?></textarea>
                            </div>

                            <hr>

                            <div class="row g-2 mt-2">
                                <div class="col-sm-8">
                                    <button type="submit" name="update" class="btn btn-success w-100 py-2 fw-bold" style="border-radius: 8px;">
                                        <i class="bi bi-save-fill"></i> Perbarui Data Kerusakan
                                    </button>
                                </div>
                                <div class="col-sm-4">
                                    <a href="kerusakan.php" class="btn btn-secondary w-100 py-2" style="border-radius: 8px;">
                                        Batal Kembali
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