<?php
session_start();

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// Cek role admin
if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak!");
}

include(__DIR__ . '/../config/koneksi.php');

// ================= AMBIL MASTER DATA RUANGAN DARI TABEL ASET =================
$ruangan_query = mysqli_query($koneksi, "SELECT DISTINCT lokasi FROM aset WHERE lokasi IS NOT NULL AND lokasi != '' ORDER BY lokasi ASC");

$id = intval($_GET['id']);
$user_query = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE id_pengguna=$id");
$user = mysqli_fetch_assoc($user_query);

if (!$user) {
    echo "<script>alert('User tidak ditemukan');window.location='user.php';</script>";
    exit;
}

if (isset($_POST['update'])) {
    $nip      = mysqli_real_escape_string($koneksi, $_POST['nip']);
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $level    = mysqli_real_escape_string($koneksi, $_POST['level']);
    $jabatan  = mysqli_real_escape_string($koneksi, $_POST['jabatan']);
    $ruangan  = mysqli_real_escape_string($koneksi, $_POST['ruangan']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    mysqli_query($koneksi, "UPDATE pengguna 
        SET nip='$nip', nama_pengguna='$nama', username='$username', level='$level', jabatan='$jabatan', ruangan='$ruangan', role='$role'
        WHERE id_pengguna=$id");

    if (!empty($_POST['password'])) {
        $password_baru = mysqli_real_escape_string($koneksi, $_POST['password']);
        mysqli_query($koneksi, "UPDATE pengguna SET password='$password_baru' WHERE id_pengguna=$id");
    }

    echo "<script>alert('Data pegawai berhasil diperbarui!');window.location='user.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Data Pegawai | SIMARIS</title>
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
                <i class="bi bi-pencil-square"></i> Edit Data Pegawai & Akun
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">NIP / NIK Pegawai</label>
                        <input type="text" name="nip" class="form-control" value="<?= htmlspecialchars($user['nip'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap Pegawai</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama_pengguna']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control" value="<?= htmlspecialchars($user['jabatan'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ruangan / Unit Kerja</label>
                            <select name="ruangan" class="form-select" required>
                                <option value="">-- Pilih Ruangan Kerja --</option>
                                <option value="IPSRS / Ruang Teknisi" <?= ($user['ruangan'] ?? '') == 'IPSRS / Ruang Teknisi' ? 'selected' : '' ?>>IPSRS / Ruang Teknisi</option>
                                <option value="Ruang Admin / IT" <?= ($user['ruangan'] ?? '') == 'Ruang Admin / IT' ? 'selected' : '' ?>>Ruang Admin / IT</option>

                                <?php while ($r = mysqli_fetch_assoc($ruangan_query)) : ?>
                                    <option value="<?= htmlspecialchars($r['lokasi']) ?>" <?= ($user['ruangan'] ?? '') == $r['lokasi'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['lokasi']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="bg-light p-3 rounded mb-3">
                        <small class="text-primary fw-bold d-block mb-2"><i class="bi bi-key-fill"></i> REVISI AKUN LOGIN</small>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru (Opsional)</label>
                            <input type="text" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin diganti">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Level Tampilan</label>
                                <select name="level" class="form-select" required>
                                    <option value="admin" <?= ($user['level'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                    <option value="pegawai" <?= ($user['level'] == 'pegawai') ? 'selected' : '' ?>>Pegawai</option>
                                    <option value="teknisi" <?= ($user['level'] == 'teknisi') ? 'selected' : '' ?>>Teknisi</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role Hak Akses</label>
                                <select name="role" class="form-select">
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="user" <?= $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" name="update" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Update Data
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