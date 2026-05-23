<?php
session_start();

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// Include koneksi database
include __DIR__ . '/../config/koneksi.php';

// Ambil nama user
$nama_user = $_SESSION['nama_pengguna'];

// ==============================
// HITUNG DATA ASET DARI TABEL ASET
// ==============================

// Total aset
$total_aset = mysqli_fetch_array(mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS total FROM aset"
))['total'];

// Aset kondisi Baik
$aset_baik = mysqli_fetch_array(mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jml FROM aset WHERE LOWER(kondisi) = 'baik'"
))['jml'];

// Aset Perlu Perawatan
$aset_perawatan = mysqli_fetch_array(mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jml FROM aset WHERE kondisi LIKE '%Perawatan%'"
))['jml'];

// Aset Rusak
$aset_rusak = mysqli_fetch_array(mysqli_query(
    $koneksi,
    "SELECT COUNT(*) AS jml FROM aset WHERE kondisi LIKE '%Rusak%'"
))['jml'];

// 5 aset terbaru
$aset_terbaru = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY id_aset DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Pegawai | SIMARIS RS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }

        #sidebar-wrapper {
            width: 230px;
            background: linear-gradient(180deg, #2c7a7b, #1cc88a);
            border-right: 1px solid rgba(255, 255, 255, 0.3);
            min-height: 100vh;
            position: fixed;
        }

        .sidebar-heading {
            text-align: center;
            font-weight: bold;
            padding: 1rem 0;
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 1.2rem;
        }

        .list-group-item {
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            background: transparent;
        }

        .list-group-item:hover {
            background-color: rgba(0, 0, 0, 0.2);
            color: #fff;
            cursor: pointer;
        }

        #page-content-wrapper {
            margin-left: 230px;
            width: calc(100% - 230px);
            padding: 20px 30px;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 15px 20px;
            font-size: 1.5rem;
            font-weight: bold;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card {
            background-color: #58d8c5;
            color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .table thead {
            background-color: #2c7a7b;
            color: #fff;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/sidebar_user.php'; ?>

    <div id="page-content-wrapper">
        <div class="dashboard-header">
            <span>DASHBOARD PEGAWAI</span>
            <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars($nama_user) ?></span>
        </div>

        <div class="card mb-4 p-4 text-center border-0 shadow-sm" style="border-radius: 12px;">
            <h3 class="fw-bold">Selamat Datang, <?= htmlspecialchars($nama_user) ?> 👋</h3>
            <p class="text-muted mb-0">
                Anda sedang menggunakan <strong>Dashboard Pegawai SIMARIS RS Bhayangkara</strong>.<br>
                Pantau status aset dan kelola laporan kerusakan dengan mudah di sini.
            </p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #36b9cc, #2a96a5);">
                    <h6>Total Aset</h6>
                    <p style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0;"><?= $total_aset ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #1cc88a, #13855c);">
                    <h6>Aset Baik</h6>
                    <p style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0;"><?= $aset_baik ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #f6c23e, #dda20a);">
                    <h6>Perawatan</h6>
                    <p style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0;"><?= $aset_perawatan ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #e74a3b, #be2617);">
                    <h6>Rusak</h6>
                    <p style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0;"><?= $aset_rusak ?></p>
                </div>
            </div>
        </div>

        <div class="row mt-4">

            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                    <div class="card-header text-white"
                        style="background: linear-gradient(90deg, #2c7a7b, #1cc88a); font-weight:bold; border: none;">
                        Aset Baru Ditambahkan
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0 text-center">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Aset</th>
                                    <th>Kategori</th>
                                    <th>Lokasi</th>
                                    <th>Kondisi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                // kalau pointer mysqli sudah dipakai sebelumnya → reset
                                mysqli_data_seek($aset_terbaru, 0);
                                while ($row = mysqli_fetch_assoc($aset_terbaru)) :
                                    $kondisi = $row['kondisi'] ?? '';
                                    $badge = 'bg-secondary';
                                    if ($kondisi == 'Baik') $badge = 'bg-success';
                                    elseif ($kondisi == 'Rusak') $badge = 'bg-danger';
                                    elseif (stripos($kondisi, 'Perawatan') !== false) $badge = 'bg-warning text-dark';
                                ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td class="fw-bold text-start"><?= htmlspecialchars($row['nama_aset']); ?></td>
                                        <td><?= htmlspecialchars($row['kategori_aset'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['lokasi'] ?? '-'); ?></td>
                                        <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($kondisi); ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-3 h-100 border-0 shadow-sm" style="border-radius: 12px;">
                    <h5 class="fw-bold text-center mb-3">Grafik Ketersediaan Aset</h5>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    <script>
        Chart.register(ChartDataLabels);

        const asetBaik = <?= $aset_baik ?>;
        const asetPerawatan = <?= $aset_perawatan ?>;
        const asetRusak = <?= $aset_rusak ?>;
        const totalAset = <?= $total_aset ?>;

        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: ['Total Aset', 'Kondisi Baik', 'Perawatan', 'Kondisi Rusak'],
                datasets: [{
                    label: 'Jumlah Aset',
                    data: [totalAset, asetBaik, asetPerawatan, asetRusak],
                    backgroundColor: ['#36b9cc', '#1cc88a', '#f6c23e', '#e74a3b'],
                    borderRadius: 6,
                    maxBarThickness: 50 // INI KUNCINYA: membatasi lebar maksimal bar agar tidak gendut/terlalu raksasa
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Menyesuaikan dengan tinggi pembungkusnya (300px)
                plugins: {
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#4a5568',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: function(value) {
                            return value;
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: '25%' // Memberi ruang kosong di bagian atas agar angka (datalabels) tidak terpotong tepi atas
                    }
                }
            }
        });
    </script>

</body>

</html>