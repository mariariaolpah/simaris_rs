<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Ambil daftar aset yang kondisinya 'Baik' saja untuk dipinjam
$aset_query = mysqli_query($koneksi, "SELECT * FROM aset WHERE kondisi = 'Baik' ORDER BY nama_aset ASC");

// KUNCI BIAR MATCH: Ambil data master ruangan dari tabel 'lokasi_aset' (Sama persis dengan form Aset)
$q_lokasi = mysqli_query($koneksi, "SELECT * FROM lokasi_aset ORDER BY nama_lokasi ASC");

if (isset($_POST['simpan'])) {
    $id_aset = $_POST['id_aset'];
    $nama_peminjam = mysqli_real_escape_string($koneksi, $_POST['nama_peminjam']);
    $lokasi_tujuan = mysqli_real_escape_string($koneksi, $_POST['lokasi_tujuan']);
    $estimasi_kembali = $_POST['estimasi_kembali'];
    $tgl_pinjam = $_POST['tanggal_pinjam'];

    // QUERY INSERT KE DATABASE
    $insert = mysqli_query($koneksi, "INSERT INTO peminjaman 
                            (id_aset, nama_peminjam, lokasi_tujuan, tanggal_pinjam, estimasi_kembali, status_pinjam) 
                            VALUES 
                            ('$id_aset', '$nama_peminjam', '$lokasi_tujuan', '$tgl_pinjam', '$estimasi_kembali', 'Dipinjam')");

    if ($insert) {
        echo "<script>alert('Sistem berhasil mencatat pergerakan alat!');window.location='peminjaman.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Peminjaman | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f4f6f9;
            color: #333;
            font-family: 'Poppins', sans-serif;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #page-content-wrapper {
            flex: 1;
            max-width: 100%;
            overflow-x: hidden;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .content {
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 15px 20px;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2c7a7b;
            box-shadow: 0 0 0 0.2rem rgba(44, 122, 123, 0.25);
        }

        .highlight-box {
            background-color: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-geo-alt"></i> LACAK PEMINJAMAN ALAT</h4>
                <div class="small fw-medium">
                    <i class="bi bi-person-circle-fill"></i> <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                    <span class="badge bg-light text-dark ms-1" style="text-transform: uppercase;"><?= htmlspecialchars($_SESSION['level']); ?></span>
                </div>
            </div>

            <div class="content">
                <div class="card" style="max-width: 750px; margin: 0 auto;">
                    <div class="card-header">
                        <i class="bi bi-clipboard-plus-fill"></i>
                        <span>Form Catatan Pergerakan Aset</span>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST">

                            <div class="mb-4">
                                <label class="form-label">Pilih Alat / Aset Rumah Sakit</label>
                                <select name="id_aset" class="form-select" required>
                                    <option value="">-- Pilih Alat Kesehatan / Inventaris --</option>
                                    <?php while ($a = mysqli_fetch_assoc($aset_query)) : ?>
                                        <option value="<?= $a['id_aset'] ?>">
                                            <?= htmlspecialchars($a['nama_aset']) ?> — (📍 Asal: <?= htmlspecialchars($a['lokasi']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text text-success"><i class="bi bi-check-circle-fill"></i> Hanya menampilkan alat dengan kondisi "Baik".</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Nama Peminjam / Penanggung Jawab</label>
                                <input type="text" name="nama_peminjam" class="form-control" placeholder="Contoh: Perawat Jaga IGD / Dr. Herman" required>
                            </div>

                            <div class="highlight-box">
                                <label class="form-label text-primary"><i class="bi bi-hospital"></i> Alat Akan Dibawa Ke Ruangan Mana?</label>
                                <select name="lokasi_tujuan" class="form-select border-primary mb-1" required>
                                    <option value="">-- Pilih Ruangan Tujuan --</option>
                                    <?php while ($lok = mysqli_fetch_assoc($q_lokasi)): ?>
                                        <option value="<?= htmlspecialchars($lok['nama_lokasi']) ?>">
                                            <?= htmlspecialchars($lok['nama_lokasi']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-secondary">Daftar ruangan ini sudah *match* (terhubung) dengan master data ruangan aset.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Tanggal Mulai Pinjam</label>
                                    <input type="date" name="tanggal_pinjam" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Estimasi Tanggal Kembali</label>
                                    <input type="date" name="estimasi_kembali" class="form-control" required>
                                </div>
                            </div>

                            <hr>

                            <div class="row g-2 mt-2">
                                <div class="col-sm-8">
                                    <button type="submit" name="simpan" class="btn btn-success w-100 py-2 fw-bold" style="border-radius: 8px;">
                                        <i class="bi bi-save-fill"></i> Simpan Transaksi Peminjaman
                                    </button>
                                </div>
                                <div class="col-sm-4">
                                    <a href="peminjaman.php" class="btn btn-secondary w-100 py-2" style="border-radius: 8px;">
                                        Batal Kembali
                                    </a>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>