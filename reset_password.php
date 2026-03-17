<?php
include(__DIR__ . '/config/koneksi.php');

if (isset($_POST['reset'])) {

    $username = $_POST['username'];
    $email = $_POST['email'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi'];

    if (strlen($password_baru) < 8) {
        $error = "Password minimal 8 karakter";
    } else if ($password_baru != $konfirmasi) {
        $error = "Konfirmasi password tidak sama";
    } else {

        $cek = mysqli_query(
            $koneksi,
            "SELECT * FROM pengguna WHERE username='$username' AND email='$email'"
        );

        if (mysqli_num_rows($cek) > 0) {

            // kode baru untuk skripsi
            $password_md5 = md5($password_baru); // Enkripsi dulu password barunya

            mysqli_query(
                $koneksi,
                "UPDATE pengguna SET password='$password_md5' WHERE username='$username'"
            );

            mysqli_query(
                $koneksi,
                "INSERT INTO log_aktivitas (username, aktivitas)
                VALUES ('$username','Reset Password')"
            );

            $success = "Password berhasil diubah. Silakan login.";
        } else {

            $error = "Username atau Email tidak ditemukan";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Reset Password - SIMARIS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 420px;
            background: white;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        h2 {
            text-align: center;
            font-weight: 700;
            margin-bottom: 20px;
        }

        label {
            font-weight: 500;
            margin-top: 8px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 12px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        button {
            padding: 10px;
            width: 100%;
            background: linear-gradient(90deg, #4e73df, #1cc88a);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
        }

        button:hover {
            opacity: 0.9;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }

        .eye {
            cursor: pointer;
            margin-left: 5px;
        }

        /* BUTTON KEMBALI KE LOGIN */
        .btn-login {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            background: linear-gradient(90deg, #4e73df, #1cc88a);
            color: white;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-login:hover {
            opacity: 0.9;
            color: white;
        }

        .btn-lupa {
            display: block;
            text-align: center;
            margin-top: 10px;
            padding: 10px;
            background: linear-gradient(90deg, #4e73df, #1cc88a);
            color: white;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-lupa:hover {
            opacity: 0.9;
            color: white;
        }
    </style>

</head>

<body>

    <div class="container">

        <h2>Reset Password</h2>

        <?php if (isset($error)) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <?php if (isset($success)) { ?>
            <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <form method="POST">

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password Baru</label>
            <input type="password" name="password_baru" id="password_baru" required>
            <span class="eye" onclick="togglePassword()">👁</span>

            <label>Konfirmasi Password</label>
            <input type="password" name="konfirmasi" id="konfirmasi" required>
            <span class="eye" onclick="toggleKonfirmasi()">👁</span>

            <button type="submit" name="reset">Reset Password</button>

        </form>

        <a href="login.php" class="btn-login">Kembali ke Login</a>


        <script>
            function togglePassword() {
                var x = document.getElementById("password_baru");
                if (x.type === "password") {
                    x.type = "text";
                } else {
                    x.type = "password";
                }
            }

            function toggleKonfirmasi() {
                var x = document.getElementById("konfirmasi");
                if (x.type === "password") {
                    x.type = "text";
                } else {
                    x.type = "password";
                }
            }
        </script>

</body>

</html>