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

$id = intval($_GET['id']);
$user_query = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE id_pengguna=$id");
$user = mysqli_fetch_assoc($user_query);

if (!$user) {
    echo "<script>alert('User tidak ditemukan');window.location='user.php';</script>";
    exit;
}

if (isset($_POST['update'])) {
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $level    = mysqli_real_escape_string($koneksi, $_POST['level']);
    $role     = mysqli_real_escape_string($koneksi, $_POST['role']);

    mysqli_query($koneksi, "UPDATE pengguna 
        SET nama_pengguna='$nama', username='$username', level='$level', role='$role'
        WHERE id_pengguna=$id");

    if (!empty($_POST['password'])) {
        $password_baru = password_hash($_POST['password'], PASSWORD_DEFAULT);
        mysqli_query($koneksi, "UPDATE pengguna SET password='$password_baru' WHERE id_pengguna=$id");
    }

    echo "<script>alert('User berhasil diupdate');window.location='user.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit User | SIMARIS RS Bhayangkara</title>
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
                <i class="bi bi-pencil-square"></i> Edit User
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nama Pengguna</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama_pengguna']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password Baru (opsional)</label>
                        <input type="text" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin diubah">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <input type="text" name="level" class="form-control" value="<?= htmlspecialchars($user['level']); ?>" required placeholder="user/admin">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control">
                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?= $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" name="update" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Update
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