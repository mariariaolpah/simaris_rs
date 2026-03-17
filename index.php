<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMARIS - RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #333;
            overflow-x: hidden;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: linear-gradient(90deg, #4e73df, #1cc88a);
            padding: 15px 35px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            color: #fff !important;
            font-weight: 600;
            font-size: 18px;
            letter-spacing: 0.3px;
        }

        .navbar-brand img {
            width: 42px;
            height: 42px;
            margin-right: 10px;
            border-radius: 50%;
        }

        .btn-login {
            background: #0dcaf0;
            color: #fff;
            border-radius: 25px;
            padding: 6px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: #0bbbe0;
            transform: translateY(-1px);
        }

        /* ===== HERO ===== */
        .hero-section {
            position: relative;
            text-align: center;
            padding: 120px 20px 60px;
            background: #f1f5ff;
            overflow: hidden;
        }

        .hero-bg-svg {
            position: absolute;
            top: -50px;
            left: 0;
            width: 100%;
            z-index: 0;
            opacity: 0.15;
        }

        .hero-section svg.text-svg {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            opacity: 0.06;
            z-index: 0;
        }

        .hero-section h1,
        .hero-section h4,
        .hero-section p {
            position: relative;
            z-index: 1;
        }

        .hero-section h1 {
            color: #2c3e97;
            font-weight: 800;
        }

        .hero-section h4 {
            color: #2c3e97;
            font-weight: 600;
        }

        .hero-section p {
            color: #555;
            font-size: 15px;
            max-width: 700px;
            margin: 0 auto 40px;
        }

        /* ===== FEATURE CARDS ===== */
        .card-section {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 25px;
            margin-bottom: 60px;
        }

        .feature-card {
            background: #fff;
            border-radius: 14px;
            padding: 25px 20px;
            width: 290px;
            text-align: center;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .feature-card img {
            width: 55px;
            height: 55px;
            margin-bottom: 15px;
        }

        .feature-card h5 {
            color: #2c3e97;
            font-weight: 700;
        }

        .feature-card p {
            color: #666;
            font-size: 14px;
        }

        /* ===== KONTAK (baru) ===== */
        .contact-section {
            background: #fff;
            padding: 80px 20px;
        }

        .contact-section h2 {
            color: #1a3c8b;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .contact-icon {
            background-color: #1a73e8;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .contact-icon img {
            width: 24px;
            height: 24px;
            filter: brightness(0) invert(1);
        }

        .contact-text h5 {
            color: #1a3c8b;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .contact-text p,
        .contact-text a {
            color: #333;
            margin: 0;
            font-size: 15px;
            text-decoration: none;
        }

        .contact-text a:hover {
            color: #1a73e8;
            text-decoration: underline;
        }

        /* ===== FOOTER ===== */
        footer {
            background-color: #fff;
            text-align: center;
            padding: 16px;
            font-size: 14px;
            color: #555;
            border-top: 1px solid #e5e7eb;
        }

        footer strong {
            color: #2c3e97;
        }

        @media (max-width: 768px) {
            .feature-card {
                width: 90%;
            }

            .hero-section svg.text-svg {
                width: 130%;
            }

            .contact-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .contact-icon {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="#">
            <img src="assets/img/logo.png" alt="Logo RS Bhayangkara">
            SIMARIS RS Bhayangkara
        </a>
        <a href="login.php" class="btn btn-login">Login</a>
    </nav>

    <!-- HERO -->
    <section class="hero-section">
        <svg class="hero-bg-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 800">
            <polygon fill="#1a73e8" points="0,800 1440,0 1440,200 0,1000" />
            <polygon fill="#00b4d8" points="0,400 1440,100 1440,300 0,900" />
        </svg>

        <svg class="text-svg" viewBox="0 0 1200 400">
            <defs>
                <linearGradient id="grad" x1="0" x2="1" y1="0" y2="1">
                    <stop offset="0%" stop-color="#4e73df" />
                    <stop offset="100%" stop-color="#1cc88a" />
                </linearGradient>
            </defs>
            <text x="50%" y="50%" text-anchor="middle" fill="url(#grad)" font-size="150" font-weight="800"
                font-family="'Poppins', sans-serif">SIMARIS</text>
        </svg>

        <h1>SIMARIS</h1>
        <h4>Sistem Informasi Manajemen Infrastruktur dan Aset Rumah Sakit</h4>
        <p>Selamat datang di portal <strong>SIMARIS</strong> RS Bhayangkara Banjarmasin.<br>
            Akses data infrastruktur, aset, dan laporan dengan cepat, aman, dan efisien.</p>
    </section>

    <!-- FITUR -->
    <div class="card-section container" data-aos="fade-up">
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="100">
            <img src="https://cdn-icons-png.flaticon.com/512/1046/1046857.png" alt="Infrastruktur Icon">
            <h5>Infrastruktur</h5>
            <p>Kelola data infrastruktur rumah sakit secara cepat dan efisien.</p>
        </div>
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="200">
            <img src="https://cdn-icons-png.flaticon.com/512/2921/2921222.png" alt="Aset Icon">
            <h5>Aset</h5>
            <p>Pantau dan kontrol seluruh aset RS Bhayangkara secara terintegrasi.</p>
        </div>
        <div class="feature-card" data-aos="zoom-in" data-aos-delay="300">
            <img src="https://cdn-icons-png.flaticon.com/512/3176/3176366.png" alt="Laporan Icon">
            <h5>Laporan</h5>
            <p>Hasilkan laporan otomatis dengan data yang selalu diperbarui.</p>
        </div>
    </div>

    <!-- KONTAK (baru mirip gambar) -->
    <section class="contact-section">
        <div class="container">
            <h2>KONTAK</h2>
            <div class="col-md-8 mx-auto">
                <div class="contact-item">
                    <div class="contact-icon">
                        <img src="https://cdn-icons-png.flaticon.com/512/535/535239.png" alt="Address">
                    </div>
                    <div class="contact-text">
                        <h5>Address</h5>
                        <p>Jl. A. Yani No.KM. 2, RW.3, Kebun Bunga, Kec. Banjarmasin Tim., Kota Banjarmasin, Kalimantan Selatan 70237</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <img src="https://cdn-icons-png.flaticon.com/512/561/561127.png" alt="Website">
                    </div>
                    <div class="contact-text">
                        <h5>Website</h5>
                        <a href="https://rsbhayangkarabanjarmasin.kalsel.polri.go.id" target="_blank">https://rsbhayangkarabanjarmasin.kalsel.polri.go.id</a>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <img src="https://cdn-icons-png.flaticon.com/512/732/732200.png" alt="Email">
                    </div>
                    <div class="contact-text">
                        <h5>Email Us</h5>
                        <p>rsbhybjm@gmail.com</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <img src="https://cdn-icons-png.flaticon.com/512/597/597177.png" alt="Telepon">
                    </div>
                    <div class="contact-text">
                        <h5>Telepon</h5>
                        <p>0812-3456-7890</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        © 2025 RS Bhayangkara. Semua hak dilindungi. | Created by <strong>Maria Olpah</strong>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>

</html>