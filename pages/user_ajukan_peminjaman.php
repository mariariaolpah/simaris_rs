<?php
session_start();

// 1. Cek apakah pengguna sudah login atau belum
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Cek aman: Pastikan session level ADA, baru dicek apakah dia admin
if (isset($_SESSION['level']) && strtolower(trim($_SESSION['level'])) == 'admin') {
    header("Location: dashboard.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Ambil daftar aset yang kondisinya 'Baik' saja
$aset_query = mysqli_query($koneksi, "SELECT * FROM aset WHERE kondisi = 'Baik' ORDER BY nama_aset ASC");

// Ambil data ruangan untuk lokasi tujuan
$q_lokasi = mysqli_query($koneksi, "SELECT * FROM lokasi_aset ORDER BY nama_lokasi ASC");

if (isset($_POST['ajukan'])) {
    $id_aset = $_POST['id_aset'];
    $nama_peminjam = $_SESSION['nama_pengguna'];
    $lokasi_tujuan = mysqli_real_escape_string($koneksi, $_POST['lokasi_tujuan']);
    $estimasi_kembali = $_POST['estimasi_kembali'];
    $tgl_pinjam = $_POST['tanggal_pinjam'];

    // PERBAIKAN: Menambahkan kolom 'sumber' dan nilainya 'App User'
    $insert = mysqli_query($koneksi, "INSERT INTO peminjaman 
                            (id_aset, nama_peminjam, lokasi_tujuan, tanggal_pinjam, estimasi_kembali, status_pinjam, sumber) 
                            VALUES 
                            ('$id_aset', '$nama_peminjam', '$lokasi_tujuan', '$tgl_pinjam', '$estimasi_kembali', 'Menunggu Persetujuan', 'App User')");

    if ($insert) {
        echo "<script>alert('Pengajuan peminjaman berhasil dikirim! Silakan tunggu konfirmasi persetujuan dari Admin IT.');window.location='dashboard_user.php';</script>";
    } else {
        echo "<script>alert('Gagal mengirim pengajuan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Ajukan Peminjaman | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
            max-width: 650px;
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
        <h4 class="fw-bold text-dark mb-4">Pengajuan Peminjaman Alat</h4>

        <div class="container-form">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-cart-plus-fill me-2"></i> Form Pengajuan Peminjaman
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info mb-4" style="border-radius: 8px; font-size: 0.9rem;">
                        <i class="bi bi-info-circle-fill"></i> Pengajuan ini akan dikirim ke Admin IT. Alat baru bisa diambil setelah disetujui.
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Alat Kesehatan / Inventaris</label>
                            <select name="id_aset" class="form-select" required>
                                <option value="">-- Pilih Alat --</option>
                                <?php while ($a = mysqli_fetch_assoc($aset_query)) : ?>
                                    <option value="<?= $a['id_aset'] ?>">
                                        <?= htmlspecialchars($a['nama_aset']) ?> (📍 Asal: <?= htmlspecialchars($a['lokasi']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Dibawa Ke Ruangan</label>
                            <select name="lokasi_tujuan" class="form-select" required>
                                <option value="">-- Pilih Ruangan Tujuan --</option>
                                <?php while ($lok = mysqli_fetch_assoc($q_lokasi)): ?>
                                    <option value="<?= htmlspecialchars($lok['nama_lokasi']) ?>">
                                        <?= htmlspecialchars($lok['nama_lokasi']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tanggal Mulai</label>
                                <input type="date" name="tanggal_pinjam" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Estimasi Kembali</label>
                                <input type="date" name="estimasi_kembali" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <a href="dashboard_user.php" class="btn btn-secondary px-4">Batal</a>
                            <button type="submit" name="ajukan" class="btn btn-success px-4">
                                <i class="bi bi-send-fill"></i> Kirim Pengajuan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>