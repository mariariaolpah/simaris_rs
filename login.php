<?php
session_start();
include(__DIR__ . '/config/koneksi.php');

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password_input = $_POST['password'];

    $query = mysqli_query($koneksi, "SELECT * FROM pengguna WHERE username='$username'");

    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);

        // cek 2 kemungkinan: md5 ATAU biasa
        if (
            $user['password'] == md5($password_input) ||
            $user['password'] == $password_input
        ) {

            // cek status
            if ($user['status'] != 'aktif') {
                $error = "Akun tidak aktif!";
            } else {

                $_SESSION['id_pengguna'] = $user['id_pengguna'];
                $_SESSION['nama_pengguna'] = $user['nama_pengguna'];
                $_SESSION['role'] = $user['role'];

                // arahkan
                if ($user['role'] == 'admin') {
                    header("Location: pages/dashboard.php");
                } else {
                    header("Location: pages/dashboard_user.php");
                }
                exit;
            }
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        .wave-bg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: 0.25;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .login-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            width: 400px;
            padding: 40px 35px;
            text-align: center;
            animation: fadeInUp 1s ease;
        }

        .login-card img {
            width: 70px;
            height: 70px;
            margin-bottom: 15px;
        }

        .login-card h3 {
            color: #000;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-card h5 {
            color: #555;
            font-weight: 400;
            font-size: 15px;
            margin-bottom: 25px;
        }

        .login-subtitle {
            text-align: left;
            font-size: 14px;
            color: #555;
            font-weight: 400;
            margin-bottom: 8px;
        }

        .form-label {
            font-weight: 500;
            color: #333;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px;
        }

        .btn-login {
            background: linear-gradient(90deg, #4e73df, #1cc88a);
            border: none;
            border-radius: 25px;
            color: #fff;
            font-weight: 600;
            padding: 10px;
            width: 100%;
            transition: 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            opacity: 0.9;
        }

        .btn-secondary {
            border-radius: 25px;
            margin-top: 10px;
        }

        .alert {
            border-radius: 10px;
        }

        footer {
            position: absolute;
            bottom: 15px;
            width: 100%;
            text-align: center;
            color: #fff;
            font-size: 13px;
            opacity: 0.8;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 500px) {
            .login-card {
                width: 90%;
                padding: 35px 25px;
            }
        }
    </style>
</head>

<body>

    <!-- SVG LATAR -->
    <svg class="wave-bg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 800">
        <polygon fill="#1a73e8" points="0,800 1440,0 1440,200 0,1000" />
        <polygon fill="#00b4d8" points="0,400 1440,100 1440,300 0,900" />
    </svg>

    <!-- FORM LOGIN -->
    <div class="login-wrapper">
        <div class="login-card">
            <img src="assets/img/logo.png" alt="Logo RS Bhayangkara">
            <h3>SIMARIS</h3>
            <h5>Sistem Informasi Manajemen Infrastruktur dan Aset Rumah Sakit</h5>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="post" action="" autocomplete="off">
                <p class="login-subtitle">Silahkan Login !</p>

                <!-- Username -->
                <div class="mb-3 text-start">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username Anda" required autocomplete="off">
                </div>

                <!-- Password -->
                <div class="mb-3 text-start">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan password Anda" required autocomplete="new-password">
                </div>

                <button type="submit" name="login" class="btn-login">Login</button>

                <a href="reset_password.php" class="text-decoration-none d-block mt-2" style="color:#4e73df; font-weight:500;">
                    Lupa Password?
                </a>

                <a href="index.php" class="btn btn-secondary w-100">Kembali ke Beranda</a>
            </form>

        </div>
    </div>

    <footer>
        © 2025 SIMARIS | RS Bhayangkara Banjarmasin
    </footer>

    <!-- Script untuk reset input saat load dan fokus -->
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const usernameInput = document.querySelector('input[name="username"]');
            const passwordInput = document.querySelector('input[name="password"]');

            // Kosongkan input saat halaman dimuat
            if (usernameInput) usernameInput.value = '';
            if (passwordInput) passwordInput.value = '';

            // Hapus otomatis saat fokus
            if (usernameInput) usernameInput.addEventListener('focus', () => usernameInput.value = '');
            if (passwordInput) passwordInput.addEventListener('focus', () => passwordInput.value = '');
        });
    </script>

</body>

</html>