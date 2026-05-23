<?php
session_start();

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// Cek role admin
if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

include(__DIR__ . '/../config/koneksi.php');

// ================= AMBIL MASTER DATA RUANGAN DARI TABEL ASET =================
$ruangan_query = mysqli_query($koneksi, "SELECT DISTINCT lokasi FROM aset WHERE lokasi IS NOT NULL AND lokasi != '' ORDER BY lokasi ASC");

// Proses simpan user/pegawai baru
if (isset($_POST['simpan'])) {
    $nip      = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);
    $level    = mysqli_real_escape_string($koneksi, $_POST['level']);
    $jabatan  = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $ruangan  = mysqli_real_escape_string($koneksi, $_POST['ruangan']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    $sql = "INSERT INTO pengguna (nip, nama_pengguna, username, password, level, jabatan, ruangan, role)
            VALUES ('$nip', '$nama', '$username', '$password', '$level', '$jabatan', '$ruangan', '$role')";

    if (mysqli_query($koneksi, $sql)) {
        header("Location: user.php?msg=created");
        exit;
    } else {
        $error_msg = "Error: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Pegawai & Akun | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Poppins', sans-serif;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: white;
            font-weight: 600;
            border-radius: 12px 12px 0 0 !important;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
        }
    </style>
</head>

<body>
    <div class="container mt-5 d-flex justify-content-center">
        <div class="card w-100" style="max-width: 600px;">
            <div class="card-header p-3">
                <i class="bi bi-person-plus-fill"></i> Tambah Data Pegawai & Akun Baru
            </div>
            <div class="card-body p-4">
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?= $error_msg; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">NIP / NIK Pegawai</label>
                        <input type="text" name="nip" class="form-control" placeholder="Masukkan NIP/NIK" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap Pegawai</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap beserta gelar" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Perawat Pelaksana, Staf IT" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ruangan / Unit Kerja</label>
                            <select name="ruangan" class="form-select" required>
                                <option value="">-- Pilih Ruangan Kerja --</option>
                                <option value="IPSRS / Ruang Teknisi">IPSRS / Ruang Teknisi</option>
                                <option value="Ruang Admin / IT">Ruang Admin / IT</option>
                                <?php while ($r = mysqli_fetch_assoc($ruangan_query)) : ?>
                                    <option value="<?= htmlspecialchars($r['lokasi']) ?>">
                                        <?= htmlspecialchars($r['lokasi']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-muted">Pilihan otomatis sinkron dengan lokasi aset.</small>
                        </div>
                    </div>
                    <hr>
                    <div class="bg-light p-3 rounded mb-3">
                        <small class="text-primary fw-bold d-block mb-2"><i class="bi bi-key-fill"></i> KREDENSIAL AKUN LOGIN</small>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="text" name="password" class="form-control" required placeholder="User123">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Level Tampilan</label>
                                <select name="level" class="form-select" required>
                                    <option value="">-- Pilih Level --</option>
                                    <option value="admin">Admin</option>
                                    <option value="pegawai">Pegawai</option>
                                    <option value="teknisi">Teknisi</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role Hak Akses</label>
                                <select name="role" class="form-select">
                                    <option value="admin">Admin</option>
                                    <option value="user" selected>User (Pegawai Biasa)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="submit" name="simpan" class="btn btn-success px-4">
                            <i class="bi bi-check-circle"></i> Simpan Pegawai
                        </button>
                        <a href="user.php" class="btn btn-secondary px-4">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>