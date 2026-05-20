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

// Ambil data aset lengkap dengan lokasi ruangan
$aset = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY nama_aset ASC");

if (isset($_POST['simpan'])) {
    // Ambil input dan amankan dengan mysqli_real_escape_string
    $nama_aset  = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $status     = mysqli_real_escape_string($koneksi, $_POST['status']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $tanggal    = date('Y-m-d');

    // Ambil nama pelapor dari session user yang sedang login
    $pelapor    = mysqli_real_escape_string($koneksi, $_SESSION['nama_pengguna']);

    // Set sumber pelaporan otomatis menjadi App User
    $sumber     = 'App User';

    // Tambahkan pelapor dan sumber ke dalam query insert
    $query = mysqli_query($koneksi, "
        INSERT INTO kerusakan (nama_aset, tanggal, status, keterangan, pelapor, sumber) 
        VALUES ('$nama_aset', '$tanggal', '$status', '$keterangan', '$pelapor', '$sumber')
    ");

    if ($query) {
        echo "<script>alert('Laporan berhasil dikirim!'); window.location='user_data_kerusakan.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan laporan!');</script>";
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
                            <label class="form-label fw-bold">Nama Pelapor</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-circle"></i></span>
                                <input type="text" class="form-control bg-light border-start-0" value="<?= htmlspecialchars($_SESSION['nama_pengguna'] ?? 'User'); ?>" readonly>
                            </div>
                            <div class="form-text text-muted"><i class="bi bi-info-circle"></i> Nama pelapor otomatis disesuaikan dengan akun Anda.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Aset / Alat yang Bermasalah</label>
                            <select name="nama_aset" class="form-select" required>
                                <option value="">-- Pilih Aset --</option>
                                <?php while ($row = mysqli_fetch_assoc($aset)) : ?>
                                    <option value="<?= htmlspecialchars($row['nama_aset']); ?>">
                                        <?= htmlspecialchars($row['nama_aset']); ?> — [ 📍 Ruang: <?= htmlspecialchars($row['lokasi'] ?? '-'); ?> ]
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text text-muted"><i class="bi bi-geo-alt"></i> Perhatikan ruangan lokasi alat agar tidak salah melapor.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Status Kerusakan</label>
                            <select name="status" class="form-select" required>
                                <option value="Rusak">Rusak (Mati Total / Berat)</option>
                                <option value="Perlu Perawatan">Perlu Perawatan (Malfungsi Ringan)</option>
                                <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan / Rincian Kerusakan</label>
                            <textarea name="keterangan" class="form-control" rows="4" placeholder="Jelaskan detail kerusakan alat secara singkat..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="user_data_kerusakan.php" class="btn btn-secondary px-4">
                                <i class="bi bi-x-circle"></i> Batal
                            </a>
                            <button type="submit" name="simpan" class="btn btn-success px-4">
                                <i class="bi bi-send"></i> Kirim Laporan
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>