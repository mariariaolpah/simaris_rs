<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// include koneksi dulu (penting sebelum query apa pun)
include(__DIR__ . '/../config/koneksi.php');

// pilih sidebar sesuai level (tetap tidak mengubah admin)
if (isset($_SESSION['level']) && $_SESSION['level'] == 'user') {
    $use_sidebar = __DIR__ . '/../sidebar_user.php';
} else {
    $use_sidebar = __DIR__ . '/../sidebar.php';
}

// Jika yang login adalah USER, tampilkan tampilan khusus user
if (isset($_SESSION['level']) && $_SESSION['level'] == 'user') {
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <title>Dashboard User | SIMARIS RS</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background: #f1f8f6;
                padding: 40px;
            }

            .card {
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            h2 {
                color: #1cc88a;
                font-weight: 700;
            }
        </style>
    </head>

    <body>
        <div class="container text-center">
            <div class="card p-5">
                <h2>Selamat Datang, <?= $_SESSION['nama_pengguna']; ?> 👋</h2>
                <p class="mt-3">Anda login sebagai <strong>USER</strong> di sistem SIMARIS RS Bhayangkara.</p>
                <hr>
                <a href="laporan_perbaikan.php" class="btn btn-outline-success m-2">📄 Lihat Laporan Perbaikan</a>
                <a href="../logout.php" class="btn btn-outline-danger m-2">🚪 Logout</a>
            </div>
        </div>
    </body>

    </html>
<?php
    exit; // hentikan agar dashboard admin di bawah tidak ikut tampil
}

// Ambil data aset dari tabel aset
$total_aset = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM aset"))['t'];

// --- TAMBAHAN FITUR SKRIPSI: Hitung total harga aset ---
$query_harga = mysqli_query($koneksi, "SELECT SUM(harga) AS total_nilai FROM aset");
$data_harga = mysqli_fetch_assoc($query_harga);
$total_rupiah = $data_harga['total_nilai'] ? $data_harga['total_nilai'] : 0;
// -------------------------------------------------------

// Hitung aset berdasarkan kolom kondisi di tabel aset
$aset_baik = mysqli_fetch_array(mysqli_query($koneksi, "
    SELECT COUNT(*) as t FROM aset 
    WHERE kondisi LIKE '%Baik%'
"))['t'];

$aset_perawatan = mysqli_fetch_array(mysqli_query($koneksi, "
    SELECT COUNT(*) as t FROM aset 
    WHERE kondisi LIKE '%Perlu Perawatan%' 
       OR kondisi LIKE '%Perawatan%'
       OR kondisi LIKE '%Dalam Perbaikan%'
"))['t'];

$aset_rusak = mysqli_fetch_array(mysqli_query($koneksi, "
    SELECT COUNT(*) as t FROM aset 
    WHERE kondisi LIKE '%Rusak%'
"))['t'];

// Ambil 5 aset terbaru
$aset_terbaru = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY id_aset DESC LIMIT 5");

// Statistik perawatan
$perawatan_total = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) AS t FROM perawatan"))['t'];
$perawatan_dalam_proses = mysqli_fetch_array(mysqli_query($koneksi, "
    SELECT COUNT(*) AS t FROM perawatan 
    WHERE status IN ('Sedang Proses','Belum Dimulai')
"))['t'];
$perawatan_selesai = mysqli_fetch_array(mysqli_query($koneksi, "
    SELECT COUNT(*) AS t FROM perawatan 
    WHERE status='Selesai'
"))['t'];

// --- TAMBAHAN BARU: DATA KATEGORI & KALIBRASI ---
// Hitung Kategori Aset untuk Grafik
$q_kategori = mysqli_query($koneksi, "SELECT kategori_aset, COUNT(*) as jumlah FROM aset GROUP BY kategori_aset");
$jml_medis = 0;
$jml_nonmedis = 0;
$jml_lainnya = 0;
while ($row = mysqli_fetch_assoc($q_kategori)) {
    if ($row['kategori_aset'] == 'Medis') $jml_medis = $row['jumlah'];
    elseif ($row['kategori_aset'] == 'Non-Medis') $jml_nonmedis = $row['jumlah'];
    else $jml_lainnya += $row['jumlah'];
}

// Ambil Data Kalibrasi Mendesak (H-7)
$q_kalibrasi = mysqli_query($koneksi, "SELECT nama_aset, tanggal_kalibrasi_berikutnya, status 
                                       FROM perawatan 
                                       WHERE tanggal_kalibrasi_berikutnya IS NOT NULL 
                                       AND tanggal_kalibrasi_berikutnya > '2000-01-01'
                                       AND DATEDIFF(tanggal_kalibrasi_berikutnya, CURDATE()) <= 7
                                       ORDER BY tanggal_kalibrasi_berikutnya ASC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            margin: 0;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #page-content-wrapper {
            flex: 1;
            padding: 0;
            overflow-x: hidden;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #fff;
        }

        .dashboard-header h3 {
            margin: 0;
            font-weight: 700;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 1rem;
        }

        .content {
            padding: 40px 30px 50px 30px;
        }

        .welcome-card {
            background: #ffffff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2c7a7b;
        }

        /* STAT CARD RAPIH */
        .stat-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            flex: 1 1 calc(25% - 15px);
            min-width: 180px;
            background-color: #58d8c5;
            color: #fff;
            border-radius: 0.75rem;
            text-align: center;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform .2s;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 120px;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .card-header {
            font-weight: 600;
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
        }

        table.table-bordered {
            border: 1px solid #d1f0eb;
        }

        table.table-hover tbody tr:hover {
            background-color: #d1f0eb;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h3>DASHBOARD</h3>
                <div class="admin-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?= $_SESSION['nama_pengguna']; ?> (<?= $_SESSION['role']; ?>)</span>
                </div>
            </div>

            <div class="content">
                <div class="welcome-card">
                    <h2>Selamat Datang di SIMARIS RS Bhayangkara</h2>
                    <p>Kelola aset dan infrastruktur rumah sakit secara efisien melalui sistem ini.</p>
                </div>

                <div class="stat-row">
                    <div class="stat-card" style="background-color: #1cc88a;">
                        <h6>Total Nilai Aset (Rp)</h6>
                        <p style="font-size: 1.6rem; font-weight: 700; margin: 0; color: #fff;">
                            Rp <?= number_format($total_rupiah, 0, ',', '.'); ?>
                        </p>
                    </div>

                    <div class="stat-card">
                        <h6>Total Aset & Infrastruktur</h6>
                        <p style="font-size: 2.5rem; font-weight: 700; margin: 0; color: #fff;"><?= $total_aset ?></p>
                    </div>
                    <div class="stat-card">
                        <h6>Aset Baik</h6>
                        <p style="font-size: 2.5rem; font-weight: 700; margin: 0; color: #fff;"><?= $aset_baik ?></p>
                    </div>
                    <div class="stat-card">
                        <h6>Perlu Perawatan</h6>
                        <p style="font-size: 2.5rem; font-weight: 700; margin: 0; color: #fff;"><?= $aset_perawatan ?></p>
                    </div>
                    <div class="stat-card">
                        <h6>Aset Rusak</h6>
                        <p style="font-size: 2.5rem; font-weight: 700; margin: 0; color: #fff;"><?= $aset_rusak ?></p>
                    </div>
                    <div class="stat-card">
                        <h6>Total Perawatan</h6>
                        <p style="font-size: 2.5rem; font-weight: 700; margin: 0; color: #fff;"><?= $perawatan_total ?></p>
                    </div>
                    <div class="stat-card">
                        <h6>Perawatan Dalam Proses</h6>
                        <p style="font-size: 2.5rem; font-weight: 700; margin: 0; color: #fff;"><?= $perawatan_dalam_proses ?></p>
                    </div>
                    <div class="stat-card">
                        <h6>Perawatan Selesai</h6>
                        <p style="font-size: 2.5rem; font-weight: 700; margin: 0; color: #fff;"><?= $perawatan_selesai ?></p>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-7">
                        <div class="card h-100">
                            <div class="card-header">Data Aset & Infrastruktur Terbaru</div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Aset</th>
                                            <th>Tipe</th>
                                            <th>Lokasi</th>
                                            <th>Kondisi</th>
                                            <th>Tanggal Pembelian</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; ?>
                                        <?php while ($row = mysqli_fetch_assoc($aset_terbaru)) : ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><?= $row['nama_aset']; ?></td>
                                                <td><?= $row['jenis']; ?></td>
                                                <td><?= $row['lokasi']; ?></td>
                                                <td><?= $row['kondisi']; ?></td>
                                                <td><?= $row['tanggal_masuk']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card h-100">
                            <div class="card-header">Persentase Kondisi Aset</div>
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <canvas id="asetChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-5">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header">Proporsi Kategori Aset</div>
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <canvas id="kategoriChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-danger text-white border-0">
                                <i class="bi bi-exclamation-triangle-fill"></i> Peringatan Kalibrasi (Mendesak)
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover mb-0 text-center align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Aset</th>
                                            <th>Jadwal Kalibrasi</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($q_kalibrasi) == 0): ?>
                                            <tr>
                                                <td colspan="3" class="py-4 text-success fw-bold">
                                                    <i class="bi bi-check-circle"></i> Aman! Tidak ada kalibrasi mendesak.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php while ($kal = mysqli_fetch_assoc($q_kalibrasi)):
                                                $selisih_detik = strtotime($kal['tanggal_kalibrasi_berikutnya']) - time();
                                                $selisih_hari = floor($selisih_detik / (60 * 60 * 24));
                                            ?>
                                                <tr>
                                                    <td class="text-start ps-3 fw-bold"><?= htmlspecialchars($kal['nama_aset']) ?></td>
                                                    <td><?= date('d-m-Y', strtotime($kal['tanggal_kalibrasi_berikutnya'])) ?></td>
                                                    <td>
                                                        <?php if ($selisih_hari < 0): ?>
                                                            <span class="badge bg-danger">Terlewat <?= abs($selisih_hari) ?> Hari</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Sisa <?= $selisih_hari ?> Hari</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    // Grafik Kondisi Aset (Lama)
                    const ctx = document.getElementById('asetChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Baik', 'Perlu Perawatan', 'Rusak'],
                            datasets: [{
                                data: [<?= $aset_baik ?>, <?= $aset_perawatan ?>, <?= $aset_rusak ?>],
                                backgroundColor: ['#2c7a7b', '#58d8c5', '#e74c3c'],
                                borderColor: '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });

                    // Grafik Kategori Aset (Baru)
                    const ctxKat = document.getElementById('kategoriChart').getContext('2d');
                    new Chart(ctxKat, {
                        type: 'doughnut',
                        data: {
                            labels: ['Medis (Alkes)', 'Non-Medis', 'Belum Diatur'],
                            datasets: [{
                                data: [<?= $jml_medis ?>, <?= $jml_nonmedis ?>, <?= $jml_lainnya ?>],
                                backgroundColor: ['#e74c3c', '#3498db', '#95a5a6'],
                                borderColor: '#fff',
                                borderWidth: 2
                            }]
                        },
                        options: {
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                </script>

            </div>
        </div>
    </div>
    <audio id="notifSound" src="../assets/notification.mp3" preload="auto"></audio>

    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            // Ambil SEMUA baris di dalam tabel
            const semuaBaris = document.querySelectorAll('.table-hover tbody tr');
            let daftarAlat = [];

            // 1. Kumpulkan data alat yang waktunya "Sisa" ATAU sudah "Terlewat"
            semuaBaris.forEach(function(baris) {
                if (baris.innerText.includes("Sisa") || baris.innerText.includes("Terlewat")) {
                    // Merapikan spasi agar nama alat, tanggal, dan statusnya nyambung enak dibaca
                    let teksBersih = baris.innerText.replace(/\s+/g, ' ').trim();
                    daftarAlat.push("👉 " + teksBersih);
                }
            });

            // 2. Jika ada alat terdeteksi (satu atau lebih), jalankan bunyi & tulisan
            if (daftarAlat.length > 0) {
                // A. Trigger putar suara notifikasi duluan
                const putarSuara = document.getElementById('notifSound');
                putarSuara.play().catch(function(e) {
                    console.log("Browser menahan suara jika belum ada klik dari admin.");
                });

                // B. Tahan kotak tulisan selama 0.5 detik (500ms) agar suara berbunyi barengan
                setTimeout(function() {
                    let teksPeringatan = "⚠️ PENGINGAT KALIBRASI ALKES:\n\nSistem mendeteksi jadwal berikut:\n\n";

                    // Menggabungkan SEMUA alat yang terdeteksi ke bawah (pakai enter / baris baru)
                    teksPeringatan += daftarAlat.join("\n");

                    teksPeringatan += "\n\nMohon segera tindak lanjuti agar operasional rumah sakit tidak terganggu!";

                    alert(teksPeringatan);
                }, 500);
            }
        });
    </script>
    </script>
</body>

</html>