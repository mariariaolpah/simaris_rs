<?php
session_start();
// Proteksi halaman
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['level']) != 'teknisi') {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$nama_teknisi = mysqli_real_escape_string($koneksi, $_SESSION['nama_pengguna']);

// Deteksi flexibel nama akun biar tidak terkunci spasi atau teks tambahan (kurung)
$is_budi = (strpos(strtolower($_SESSION['nama_pengguna']), 'budi') !== false);
$is_ahmad = (strpos(strtolower($_SESSION['nama_pengguna']), 'ahmad') !== false);

$total_kerusakan = 0;
$proses_kerusakan = 0;
$selesai_kerusakan = 0;
$total_perawatan = 0;
$proses_perawatan = 0;
$selesai_perawatan = 0;

if ($is_budi) {
    // ================= STATISTIK DATA KERUSAKAN (BUDI) ================= //
    $q_total_k = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kerusakan WHERE teknisi = '$nama_teknisi'");
    $total_kerusakan = mysqli_fetch_assoc($q_total_k)['total'] ?? 0;

    $q_proses_k = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kerusakan WHERE teknisi = '$nama_teknisi' AND (status = 'Diproses' OR status = 'Dalam Perbaikan')");
    $proses_kerusakan = mysqli_fetch_assoc($q_proses_k)['total'] ?? 0;

    $q_selesai_k = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kerusakan WHERE teknisi = '$nama_teknisi' AND (status = 'Selesai Diperbaiki' OR status = 'Selesai')");
    $selesai_kerusakan = mysqli_fetch_assoc($q_selesai_k)['total'] ?? 0;
}

if ($is_ahmad) {
    // ================= STATISTIK DATA PERAWATAN (AHMAD) ================= //
    $q_total_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan WHERE teknisi = '$nama_teknisi'");
    $total_perawatan = mysqli_fetch_assoc($q_total_p)['total'] ?? 0;

    $q_proses_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan WHERE teknisi = '$nama_teknisi' AND (status = 'Sedang Proses' OR status = 'Diproses')");
    $proses_perawatan = mysqli_fetch_assoc($q_proses_p)['total'] ?? 0;

    $q_selesai_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan WHERE teknisi = '$nama_teknisi' AND status = 'Selesai'");
    $selesai_perawatan = mysqli_fetch_assoc($q_selesai_p)['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Teknisi | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #page-content-wrapper {
            flex: 1;
            width: 100%;
            overflow-x: hidden;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content {
            padding: 30px;
        }

        .card {
            border-radius: 12px;
        }

        .table-custom th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div id="wrapper">

        <?php include(__DIR__ . '/sidebar_teknisi.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-speedometer2"></i> DASHBOARD TEKNISI</h4>
                <div>
                    <i class="bi bi-person-circle"></i> Hak Akses: <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                </div>
            </div>

            <div class="content">

                <div class="alert alert-success shadow-sm border-0 rounded-3 mb-4">
                    <h4 class="alert-heading fw-bold">Selamat Datang, <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>!</h4>
                    <p class="mb-0">
                        Anda masuk ke sistem sebagai:
                        <span class="badge bg-dark fs-6 mt-1">
                            <?php
                            if ($is_budi) {
                                echo "Teknisi Spesialis Perbaikan Kerusakan Aset";
                            } elseif ($is_ahmad) {
                                echo "Teknisi Spesialis Perawatan, Pemeliharaan & Kalibrasi Alat Medis";
                            } else {
                                echo "Teknisi Operasional";
                            }
                            ?>
                        </span>
                    </p>
                </div>

                <?php if ($is_budi) : ?>
                    <div class="row mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-wrench text-danger"></i> Lembar Statistik Tugas Perbaikan Aset</h5>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Total Perbaikan</h6>
                                    <h2 class="m-0 fw-bold"><?= $total_kerusakan ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-dark border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Sedang Diproses</h6>
                                    <h2 class="m-0 fw-bold"><?= $proses_kerusakan ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Selesai Diperbaiki</h6>
                                    <h2 class="m-0 fw-bold"><?= $selesai_kerusakan ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-card-checklist text-danger"></i> Antrean Penanganan Kerusakan Aset Anda</span>
                            <a href="kerusakan.php" class="btn btn-sm btn-outline-danger">Lihat Semua Laporan</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover table-custom mb-0 text-center align-middle">
                                <thead>
                                    <tr>
                                        <th>Nama Aset</th>
                                        <th>Keterangan Rusak</th>
                                        <th>Status Laporan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $q_tabel_k = mysqli_query($koneksi, "SELECT * FROM kerusakan WHERE teknisi = '$nama_teknisi' AND status NOT IN ('Selesai Diperbaiki', 'Selesai') ORDER BY id DESC LIMIT 5");
                                    if (mysqli_num_rows($q_tabel_k) > 0) {
                                        while ($row = mysqli_fetch_assoc($q_tabel_k)) {
                                            $badge_color = ($row['status'] == 'Baru' || $row['status'] == '') ? 'bg-secondary' : 'bg-warning text-dark';
                                            echo "<tr>
                                                <td class='fw-bold text-start'>{$row['nama_aset']}</td>
                                                <td class='text-start'>{$row['keterangan']}</td>
                                                <td><span class='badge {$badge_color}'>{$row['status']}</span></td>
                                                <td><a href='kerusakan_edit.php?id={$row['id']}' class='btn btn-sm btn-primary'><i class='bi bi-tools'></i> Ambil Tindakan</a></td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Tidak ada laporan kerusakan aset yang ditugaskan ke Anda saat ini.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>


                <?php if ($is_ahmad) : ?>
                    <div class="row mb-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-heart-pulse text-primary"></i> Lembar Agenda Perawatan &amp; Kalibrasi</h5>
                        <div class="col-md-4">
                            <div class="card bg-primary text-white border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Total Jadwal Perawatan</h6>
                                    <h2 class="m-0 fw-bold"><?= $total_perawatan ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm mb-3" style="background-color: #fca5a5; color: #000;">
                                <div class="card-body">
                                    <h6 class="card-title">Sedang Berjalan</h6>
                                    <h2 class="m-0 fw-bold"><?= $proses_perawatan ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Selesai Terlaksana</h6>
                                    <h2 class="m-0 fw-bold"><?= $selesai_perawatan ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-calendar2-check text-primary"></i> Jadwal Pemeliharaan Alat Medis Terdekat Anda</span>
                            <a href="perawatan.php" class="btn btn-sm btn-outline-primary">Lihat Semua Jadwal</a>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover table-custom mb-0 text-center align-middle">
                                <thead>
                                    <tr>
                                        <th>Nama Alat / Komponen</th>
                                        <th>Rencana Tanggal</th>
                                        <th>Status Pemeliharaan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $q_tabel_p = mysqli_query($koneksi, "SELECT * FROM perawatan WHERE teknisi = '$nama_teknisi' AND status NOT IN ('Selesai') ORDER BY id DESC LIMIT 5");
                                    if (mysqli_num_rows($q_tabel_p) > 0) {
                                        while ($row = mysqli_fetch_assoc($q_tabel_p)) {
                                            $tgl = date('d-m-Y', strtotime($row['tanggal']));
                                            $badge_color = ($row['status'] == 'Belum Dimulai') ? 'bg-secondary' : 'bg-warning text-dark';
                                            echo "<tr>
                                                <td class='fw-bold text-start'>{$row['nama_aset']}</td>
                                                <td>{$tgl}</td>
                                                <td><span class='badge {$badge_color}'>{$row['status']}</span></td>
                                                <td><a href='perawatan_edit.php?id={$row['id']}' class='btn btn-sm btn-primary'><i class='bi bi-tools'></i> Kerjakan</a></td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Seluruh jadwal pemeliharaan dan kalibrasi alat sudah selesai diproses.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>

</html>