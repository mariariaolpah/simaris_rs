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

// Proses simpan user baru
if (isset($_POST['simpan'])) {
    $nama     = $_POST['nama'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $level    = $_POST['level'];
    $role     = $_POST['role'];

    $sql = "INSERT INTO pengguna (nama_pengguna, username, password, level, role)
            VALUES ('$nama', '$username', '$password', '$level', '$role')";

    if (mysqli_query($koneksi, $sql)) {
        // Redirect ke user.php (sesuai nama file baru)
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
    <title>Tambah User | SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
        }

        .container {
            max-width: 700px;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-success,
        .btn-secondary {
            min-width: 100px;
        }

        .admin-info i {
            margin-right: 8px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #842029;
            border-radius: 8px;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">

        <div class="dashboard-header">
            <h3>Tambah User Baru</h3>
            <div class="admin-info">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
            </div>
        </div>

        <?php if (!empty($error_msg)) : ?>
            <div class="alert-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="card shadow-sm p-4">
            <form method="POST">
                <div class="mb-3">
                    <label>Nama Pengguna</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="text" name="password" class="form-control" required placeholder="user123">
                </div>
                <div class="mb-3">
                    <label>Level</label>
                    <input type="text" name="level" class="form-control" required placeholder="user/admin">
                </div>
                <div class="mb-3">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="admin">Admin</option>
                        <option value="user" selected>User</option>
                    </select>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" name="simpan" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Simpan
                    </button>
                    <a href="user.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>