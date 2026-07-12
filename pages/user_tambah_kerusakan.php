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
    // Ambil input dan amankan
    $nama_aset  = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $status     = mysqli_real_escape_string($koneksi, $_POST['status']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $tanggal    = date('Y-m-d');
    $pelapor    = mysqli_real_escape_string($koneksi, $_SESSION['nama_pengguna']);
    $sumber     = 'App User';

    // ================= LOGIKA UPLOAD FOTO BUKTI ================= //
    $foto_bukti = ""; // Default kosong jika user tidak upload foto
    if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] === 0) {
        $nama_file = $_FILES['foto_bukti']['name'];
        $tmp_name  = $_FILES['foto_bukti']['tmp_name'];

        // Beri nama unik agar file tidak tertimpa
        $foto_bukti = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $nama_file);

        // Folder tujuan (Pastikan folder ini sudah kamu buat!)
        $lokasi_simpan = "../assets/img/kerusakan/" . $foto_bukti;

        // Pindahkan file ke folder tujuan
        move_uploaded_file($tmp_name, $lokasi_simpan);
    }
    // ============================================================ //

    // ================= LOGIKA SMART ROUTING (PEMISAHAN TUGAS OTOMATIS) ================= //

    if ($status == 'Perlu Perawatan') {
        // ---- 1. JALUR PERAWATAN (Masuk ke tabel perawatan untuk AHMAD FAUZI) ----
        $teknisi_p = 'Ahmad Fauzi';
        $status_awal = 'Belum Dimulai'; // Status default di tabel perawatan

        // Insert ke tabel perawatan (Ahmad Fauzi akan melihat ini di dashboardnya)
        $query = mysqli_query($koneksi, "
            INSERT INTO perawatan (nama_aset, teknisi, petugas_kalibrasi, tanggal, status_progres) 
            VALUES ('$nama_aset', '$teknisi_p', '$teknisi_p', '$tanggal', '$status_awal')
        ");

        if ($query) {
            // Update kondisi aset utama
            mysqli_query($koneksi, "UPDATE aset SET kondisi = 'Perlu Perawatan' WHERE nama_aset = '$nama_aset'");
            echo "<script>alert('Laporan berhasil dikirim! Laporan diteruskan ke tim Perawatan (Ahmad Fauzi).'); window.location='user_data_kerusakan.php';</script>";
        } else {
            echo "<script>alert('Gagal meneruskan laporan ke data perawatan!');</script>";
        }
    } else {
        // ---- 2. JALUR PERBAIKAN KERUSAKAN (Masuk ke tabel kerusakan untuk BUDI SETIAWAN) ----
        $teknisi_k = 'Budi Setiawan';

        // Insert ke tabel kerusakan beserta foto bukti (Budi Setiawan akan melihat ini di dashboardnya)
        $query = mysqli_query($koneksi, "
            INSERT INTO kerusakan (nama_aset, tanggal, status, keterangan, pelapor, sumber, teknisi, foto_bukti) 
            VALUES ('$nama_aset', '$tanggal', '$status', '$keterangan', '$pelapor', '$sumber', '$teknisi_k', '$foto_bukti')
        ");

        if ($query) {
            // Sinkronisasi stok kerusakan otomatis
            if ($status == "Rusak") {
                mysqli_query($koneksi, "UPDATE aset SET stok_tersedia = stok_tersedia - 1, stok_rusak = stok_rusak + 1 WHERE nama_aset = '$nama_aset'");
            } elseif ($status == "Dalam Perbaikan") {
                mysqli_query($koneksi, "UPDATE aset SET stok_tersedia = stok_tersedia - 1, stok_perawatan = stok_perawatan + 1 WHERE nama_aset = '$nama_aset'");
            }
            echo "<script>alert('Laporan berhasil dikirim! Laporan diteruskan ke tim Perbaikan (Budi Setiawan).'); window.location='user_data_kerusakan.php';</script>";
        } else {
            echo "<script>alert('Gagal meneruskan laporan ke data kerusakan!');</script>";
        }
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
        <h4 class="fw-bold text-dark mb-4">Buat Laporan / Pengaduan Aset</h4>

        <div class="container-form">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bug"></i> Form Laporan Kendala Aset
                </div>

                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

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
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategori Kendala</label>
                            <select name="status" class="form-select" required>
                                <option value="Rusak">Rusak (Mati Total / Berat) - Tim Perbaikan</option>
                                <option value="Perlu Perawatan">Perlu Perawatan (Malfungsi Ringan) - Tim Pemeliharaan</option>
                            </select>
                            <div class="form-text text-primary fw-medium"><i class="bi bi-arrow-split"></i> Pemilihan status ini akan menentukan teknisi mana yang menangani.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Keterangan / Rincian Kendala</label>
                            <textarea name="keterangan" class="form-control" rows="4" placeholder="Jelaskan detail kerusakan alat secara singkat..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Unggah Foto Bukti (Opsional)</label>
                            <input type="file" name="foto_bukti" class="form-control" accept="image/jpeg, image/png, image/jpg">
                            <div class="form-text text-muted"><i class="bi bi-image"></i> Format yang diizinkan: JPG, JPEG, PNG.</div>
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