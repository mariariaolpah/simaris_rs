<?php
session_start();
// Proteksi halaman
if (!isset($_SESSION['id_pengguna']) || strtolower($_SESSION['level']) != 'teknisi') {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ==============================================================================
// SISTEM OTOMATIS PENGISI TANGGAL SELESAI (SUDAH DIPERBAIKI DARI ERROR 0000-00-00)
// ==============================================================================
// Jika teknisi A (Budi) mengubah status jadi Selesai di form, dashboard otomatis mengisi tanggalnya
mysqli_query($koneksi, "UPDATE kerusakan SET tanggal_selesai = CURRENT_DATE() WHERE (status = 'Selesai' OR status = 'Selesai Diperbaiki') AND tanggal_selesai IS NULL");

// Jika teknisi B (Ahmad) mengubah status jadi Selesai, dashboard otomatis mengisi 2 tanggalnya
mysqli_query($koneksi, "UPDATE perawatan SET tanggal_selesai = CURRENT_DATE(), tanggal_selesai_kalibrasi = CURRENT_DATE() WHERE status = 'Selesai' AND tanggal_selesai IS NULL");
// ==============================================================================

$nama_teknisi = "Ahmad Fauzi"; // Mengunci data master ke Ahmad Fauzi

// Deteksi fleksibel nama akun
$is_budi = (strpos(strtolower($_SESSION['nama_pengguna']), 'budi') !== false);
$is_ahmad = (strpos(strtolower($_SESSION['nama_pengguna']), 'ahmad') !== false);

$total_kerusakan = 0;
$proses_kerusakan = 0;
$selesai_kerusakan = 0;

$total_perawatan = 0;
$proses_perawatan = 0;
$selesai_perawatan = 0;
$belum_perawatan = 0;

// Variabel hitung kalibrasi kritis untuk Ahmad Fauzi
$jumlah_terlewat = 0;
$jumlah_mendekati = 0;

if ($is_budi) {
    // ================= STATISTIK DATA KERUSAKAN (BUDI) ================= //
    $q_total_k = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kerusakan WHERE teknisi LIKE '%budi%'");
    $total_kerusakan = mysqli_fetch_assoc($q_total_k)['total'] ?? 0;

    $q_proses_k = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kerusakan WHERE teknisi LIKE '%budi%' AND status = 'Diproses'");
    $proses_kerusakan = mysqli_fetch_assoc($q_proses_k)['total'] ?? 0;

    $q_selesai_k = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kerusakan WHERE teknisi LIKE '%budi%' AND status = 'Selesai'");
    $selesai_kerusakan = mysqli_fetch_assoc($q_selesai_k)['total'] ?? 0;

    // Menampilkan semua riwayat
    $q_tabel_k = mysqli_query($koneksi, "SELECT * FROM kerusakan WHERE teknisi LIKE '%budi%' ORDER BY id DESC");
}

if ($is_ahmad) {
    // ================= STATISTIK DATA PERAWATAN & KALIBRASI (AHMAD FAUZI) ================= //
    $q_total_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan WHERE teknisi = '$nama_teknisi' OR petugas_kalibrasi = '$nama_teknisi'");
    $total_perawatan = mysqli_fetch_assoc($q_total_p)['total'] ?? 0;

    $q_proses_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan WHERE (teknisi = '$nama_teknisi' OR petugas_kalibrasi = '$nama_teknisi') AND status = 'Sedang Proses'");
    $proses_perawatan = mysqli_fetch_assoc($q_proses_p)['total'] ?? 0;

    $q_selesai_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan WHERE (teknisi = '$nama_teknisi' OR petugas_kalibrasi = '$nama_teknisi') AND status = 'Selesai'");
    $selesai_perawatan = mysqli_fetch_assoc($q_selesai_p)['total'] ?? 0;

    $q_belum_p = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan WHERE (teknisi = '$nama_teknisi' OR petugas_kalibrasi = '$nama_teknisi') AND status = 'Belum Dimulai'");
    $belum_perawatan = mysqli_fetch_assoc($q_belum_p)['total'] ?? 0;

    // Pindai kalibrasi kritis/terlewat untuk memicu alert Patient Safety
    $q_scan_kritis = mysqli_query($koneksi, "SELECT tanggal_kalibrasi_berikutnya FROM perawatan WHERE (teknisi = '$nama_teknisi' OR petugas_kalibrasi = '$nama_teknisi') AND status != 'Selesai'");
    while ($scan = mysqli_fetch_assoc($q_scan_kritis)) {
        $tgl_b = $scan['tanggal_kalibrasi_berikutnya'];
        if ($tgl_b && $tgl_b != '0000-00-00') {
            $selisih = floor((strtotime($tgl_b) - strtotime('today')) / (60 * 60 * 24));
            if ($selisih < 0) {
                $jumlah_terlewat++;
            } elseif ($selisih <= 7) {
                $jumlah_mendekati++;
            }
        }
    }

    // Menampilkan semua riwayat
    $q_tabel_p = mysqli_query($koneksi, "SELECT * FROM perawatan WHERE (teknisi = '$nama_teknisi' OR petugas_kalibrasi = '$nama_teknisi') ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Teknisi | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
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
            background: linear-gradient(135deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 30px;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            box-shadow: 0 10px 25px rgba(44, 122, 123, 0.15);
        }

        .welcome-text h2 {
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .content {
            padding: 35px 30px;
        }

        .stat-card {
            border: none;
            border-radius: 16px;
            padding: 24px;
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(148, 163, 184, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(148, 163, 184, 0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            top: -40px;
            right: -40px;
        }

        .card-icon-box {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 16px;
        }

        .theme-total {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .theme-total .card-icon-box {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .theme-process {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .theme-process .card-icon-box {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .theme-pending {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
        }

        .theme-pending .card-icon-box {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .theme-success {
            background: linear-gradient(135deg, #10b981, #047857);
            color: white;
        }

        .theme-success .card-icon-box {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        .main-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            background: #ffffff;
            overflow: hidden;
            margin-top: 30px;
        }

        .main-card-header {
            background: #ffffff;
            padding: 24px 30px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .main-card-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1e293b;
        }

        .table thead th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody td {
            padding: 18px 16px;
            vertical-align: middle;
            color: #334155;
            font-size: 0.95rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .btn-action-work {
            background: #eff6ff;
            color: #2563eb;
            border: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-action-work:hover {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .custom-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .live-clock {
            background: rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes alert-pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5);
            }

            70% {
                transform: scale(1.01);
                box-shadow: 0 0 0 12px rgba(239, 68, 68, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .patient-safety-banner {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-left: 6px solid #ef4444;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 30px;
            animation: alert-pulse 2s infinite ease-in-out;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        @keyframes text-blink {
            50% {
                opacity: 0.4;
            }
        }

        .anim-blink {
            animation: text-blink 1.2s linear infinite;
        }
    </style>
</head>

<body>
    <div id="wrapper">

        <?php include(__DIR__ . '/sidebar_teknisi.php'); ?>

        <div id="page-content-wrapper">

            <div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="welcome-text">
                    <span class="text-uppercase tracking-wider opacity-75 fw-bold small">Pusat Kendali Operasional</span>
                    <h2 class="m-0 mt-1">Halo, Kamu Login Sebagai Teknisi <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>!</h2>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="live-clock">
                        <i class="bi bi-clock-history"></i> <span id="clock-display">00:00:00 WITA</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 bg-white text-dark px-3 py-2 rounded-4 shadow-sm">
                        <i class="bi bi-shield-check text-success fs-5"></i>
                        <span class="fw-bold small text-uppercase">Role: <?= htmlspecialchars($_SESSION['level']); ?></span>
                    </div>
                </div>
            </div>

            <div class="content">

                <?php if ($is_ahmad) : ?>

                    <?php if ($jumlah_terlewat > 0 || $jumlah_mendekati > 0) : ?>
                        <div class="patient-safety-banner shadow-sm">
                            <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 50px; height: 50px; flex-shrink: 0;">
                                <i class="bi bi-exclamation-octagon-fill fs-4 anim-blink"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-danger mb-1"><i class="bi bi-shield-fill-exclamation"></i> PERINGATAN KESELAMATAN PASIEN!</h6>
                                <p class="small text-dark m-0">
                                    Ditemukan <strong><?= $jumlah_terlewat; ?> alat medis terlewat kalibrasi</strong> dan <strong><?= $jumlah_mendekati; ?> alat mendekati batas waktu</strong>. Mohon segera lakukan kalibrasi ulang demi akurasi diagnosa pasien.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-6 col-lg-3">
                            <div class="stat-card theme-total">
                                <div class="card-icon-box"><i class="bi bi-journal-check"></i></div>
                                <div class="stat-number"><?= $total_perawatan; ?></div>
                                <div class="stat-label">Total Agenda</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stat-card theme-pending">
                                <div class="card-icon-box"><i class="bi bi-hourglass-split"></i></div>
                                <div class="stat-number"><?= $belum_perawatan; ?></div>
                                <div class="stat-label">Belum Mulai</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stat-card theme-process">
                                <div class="card-icon-box"><i class="bi bi-arrow-repeat"></i></div>
                                <div class="stat-number"><?= $proses_perawatan; ?></div>
                                <div class="stat-label">Sedang Proses</div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="stat-card theme-success">
                                <div class="card-icon-box"><i class="bi bi-check-circle-fill"></i></div>
                                <div class="stat-number"><?= $selesai_perawatan; ?></div>
                                <div class="stat-label">Selesai Dirawat</div>
                            </div>
                        </div>
                    </div>

                    <div class="main-card">
                        <div class="main-card-header">
                            <h5 class="main-card-title">
                                <i class="bi bi-calendar-range text-primary"></i> Jadwal Pemeliharaan & Kalibrasi Berjalan
                            </h5>
                            <span class="badge bg-light text-dark px-3 py-2 rounded-3 fw-bold border">Tugas Aktif Anda</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle text-center m-0">
                                <thead>
                                    <tr>
                                        <th class="text-start">Nama Aset Utama</th>
                                        <th>Tgl Perawatan</th>
                                        <th>Tgl Selesai Perawatan</th>
                                        <th>Jadwal Kalibrasi Berikutnya</th>
                                        <th>Tgl Selesai Kalibrasi</th>
                                        <th>Status Kalibrasi</th>
                                        <th>Status Rawat</th>
                                        <th>Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($q_tabel_p) > 0) {
                                        while ($row = mysqli_fetch_assoc($q_tabel_p)) {
                                            $tgl_p = date('d-m-Y', strtotime($row['tanggal']));
                                            $tgl_k = $row['tanggal_kalibrasi_berikutnya'];

                                            $tgl_selesai_p = (!empty($row['tanggal_selesai']) && $row['tanggal_selesai'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_selesai'])) : '-';
                                            $tgl_selesai_k = (!empty($row['tanggal_selesai_kalibrasi']) && $row['tanggal_selesai_kalibrasi'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_selesai_kalibrasi'])) : '-';

                                            if ($tgl_k && $tgl_k != '0000-00-00') {
                                                $formatted_k = date('d-m-Y', strtotime($tgl_k));
                                                $selisih_hari = floor((strtotime($tgl_k) - strtotime('today')) / (60 * 60 * 24));

                                                if ($selisih_hari < 0) {
                                                    $lewat = abs($selisih_hari);
                                                    $kalibrasi_status = "<span class='badge bg-danger px-2 py-1 text-white anim-blink d-block'><i class='bi bi-x-circle'></i> Terlewat $lewat Hari!</span>";
                                                } elseif ($selisih_hari <= 7) {
                                                    $kalibrasi_status = "<span class='badge bg-warning text-dark px-2 py-1 d-block'><i class='bi bi-exclamation-triangle'></i> H-$selisih_hari Kritis</span>";
                                                } else {
                                                    $kalibrasi_status = "<span class='badge bg-success text-white px-2 py-1 d-block'><i class='bi bi-shield-check'></i> Aman</span>";
                                                }
                                            } else {
                                                $formatted_k = "-";
                                                $kalibrasi_status = "<span class='text-muted small'>-</span>";
                                            }

                                            $badge_class = ($row['status'] == 'Selesai' || $row['status'] == 'Selesai Diperbaiki') ? 'bg-success text-white' : (($row['status'] == 'Belum Dimulai') ? 'bg-secondary' : 'bg-warning text-dark');

                                            echo "<tr>
                                                <td class='fw-bold text-start text-dark'>
                                                    <i class='bi bi-box-seam text-secondary me-2'></i>{$row['nama_aset']}
                                                </td>
                                                <td><i class='bi bi-calendar3 text-muted me-1'></i>{$tgl_p}</td>
                                                <td class='text-primary fw-bold'>{$tgl_selesai_p}</td>
                                                <td class='fw-bold text-primary'>{$formatted_k}</td>
                                                <td class='text-info fw-bold'>{$tgl_selesai_k}</td>
                                                <td>{$kalibrasi_status}</td>
                                                <td><span class='badge custom-badge {$badge_class}'>{$row['status']}</span></td>
                                                <td>
                                                    <a href='perawatan_edit.php?id={$row['id']}' class='btn-action-work'>
                                                        <i class='bi bi-play-circle'></i> Tindak Lanjut
                                                    </a>
                                                </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center py-5 text-muted'>Tidak ada agenda pemeliharaan.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>


                <?php if ($is_budi) : ?>
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <div class="stat-card theme-total">
                                <div class="card-icon-box"><i class="bi bi-exclamation-triangle"></i></div>
                                <div class="stat-number"><?= $total_kerusakan; ?></div>
                                <div class="stat-label">Total Keluhan Kerusakan</div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="stat-card theme-process">
                                <div class="card-icon-box"><i class="bi bi-wrench"></i></div>
                                <div class="stat-number"><?= $proses_kerusakan; ?></div>
                                <div class="stat-label">Progress Perbaikan</div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="stat-card theme-success">
                                <div class="card-icon-box"><i class="bi bi-patch-check"></i></div>
                                <div class="stat-number"><?= $selesai_kerusakan; ?></div>
                                <div class="stat-label">Perbaikan Selesai</div>
                            </div>
                        </div>
                    </div>

                    <div class="main-card">
                        <div class="main-card-header">
                            <h5 class="main-card-title">
                                <i class="bi bi-activity text-danger"></i> Antrean Laporan Kerusakan Alat
                            </h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle text-center m-0">
                                <thead>
                                    <tr>
                                        <th class="text-start">Nama Aset</th>
                                        <th>Tanggal Lapor</th>
                                        <th>Tanggal Selesai Diperbaiki</th>
                                        <th>Status Kerja</th>
                                        <th>Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($q_tabel_k) > 0) {
                                        while ($row = mysqli_fetch_assoc($q_tabel_k)) {
                                            $tgl = date('d-m-Y', strtotime($row['tanggal']));

                                            $tgl_selesai_k = (!empty($row['tanggal_selesai']) && $row['tanggal_selesai'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_selesai'])) : '-';

                                            $badge_class = ($row['status'] == 'Selesai' || $row['status'] == 'Selesai Diperbaiki') ? 'bg-success text-white' : 'bg-warning text-dark';
                                            echo "<tr>
                                                <td class='fw-bold text-start text-dark'>{$row['nama_aset']}</td>
                                                <td>{$tgl}</td>
                                                <td class='text-success fw-bold'>{$tgl_selesai_k}</td>
                                                <td><span class='badge custom-badge {$badge_class}'>{$row['status']}</span></td>
                                                <td>
                                                    <a href='kerusakan_edit.php?id={$row['id']}' class='btn-action-work'>
                                                        <i class='bi bi-wrench'></i> Eksekusi
                                                    </a>
                                                </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Tidak ada antrean laporan kerusakan alat medis.</td></tr>";
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

    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock-display').textContent = `${hours}:${minutes}:${seconds} WITA`;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>

</html>