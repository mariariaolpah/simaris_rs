<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}
include(__DIR__ . '/../config/koneksi.php');

// =======================
// FUNGSI HITUNG DATA
// =======================
function getCount($koneksi, $table, $tahun = null)
{
    $whereExtra = '';
    $sql = '';

    switch ($table) {
        case 'aset':
            $tanggalKolom = 'tanggal_masuk';
            break;
        case 'kerusakan':
        case 'perawatan':
        case 'perbaikan':
            $tanggalKolom = 'tanggal';
            break;
        case 'perawatan_berjalan':
            $table = 'perawatan';
            $tanggalKolom = 'tanggal';
            $whereExtra = " AND status IN ('Belum Dimulai', 'Sedang Proses')";
            break;
        default:
            $tanggalKolom = 'tanggal';
    }

    if ($table === 'perbaikan') {
        $table = 'kerusakan';
        $whereExtra .= " AND (status LIKE '%Perbaiki%' OR status LIKE '%Perbaikan%' OR status LIKE '%Selesai%')";
    }

    if ($tahun) {
        $sql = "SELECT COUNT(*) AS total 
                FROM $table 
                WHERE YEAR($tanggalKolom) = '$tahun' $whereExtra";
    } else {
        $sql = "SELECT COUNT(*) AS total 
                FROM $table 
                WHERE 1=1 $whereExtra";
    }

    $res = mysqli_query($koneksi, $sql);
    if (!$res) {
        echo "<pre style='color:red;'><b>❌ QUERY ERROR ($table):</b> " . mysqli_error($koneksi) . "</pre>";
        echo "<pre><b>SQL:</b> $sql</pre>";
        return 0;
    }

    $data = mysqli_fetch_assoc($res);
    return (int)($data['total'] ?? 0);
}



// =======================
// AMBIL DATA LAPORAN
// =======================

// Ambil tahun sekarang
$tahunSekarang = date('Y');

// Buat daftar laporan
$laporan_list = [
    ['Laporan Aset', getCount($koneksi, 'aset', $tahunSekarang), $tahunSekarang],
    ['Laporan Kerusakan', getCount($koneksi, 'kerusakan', $tahunSekarang), $tahunSekarang],
    ['Laporan Perawatan', getCount($koneksi, 'perawatan', $tahunSekarang), $tahunSekarang],
    ['Laporan Perbaikan', getCount($koneksi, 'perbaikan', $tahunSekarang), $tahunSekarang],
    ['Laporan Perawatan Berjalan', getCount($koneksi, 'perawatan_berjalan', $tahunSekarang), $tahunSekarang],
];

// =======================
// FILTER CARI & STATUS
// =======================

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Filter Search (mencari pada nama laporan)
if ($search !== '') {
    $laporan_list = array_filter($laporan_list, function ($l) use ($search) {
        return stripos($l[0], $search) !== false;
    });
}

// Filter Status
if ($statusFilter !== '') {
    $laporan_list = array_filter($laporan_list, function ($l) use ($statusFilter) {
        $status = $l[0] === 'Laporan Perawatan Berjalan' ? 'Sedang Proses' : 'Selesai';
        return $status === $statusFilter;
    });
}

// Tidak perlu lagi menambahkan $laporan_list[] setelah ini, karena sudah ada


// ===== Tambahkan Laporan Manajemen User =====
$user_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM pengguna"))['total'] ?? 0;


// =======================
// LINK LAPORAN
// =======================
function getReportLink($jenis)
{
    switch ($jenis) {
        case 'Laporan Aset':
            return 'laporan_aset.php';
        case 'Laporan Kerusakan':
            return 'laporan_kerusakan.php';
        case 'Laporan Perawatan':
            return 'laporan_perawatan.php';
        case 'Laporan Perbaikan':
            return 'laporan_perbaikan.php';
        case 'Laporan Perawatan Berjalan':
            return 'laporan_perawatan_berjalan.php';
        default:
            return '#';
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan | SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            margin: 0;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #sidebar-wrapper {
            width: 220px;
            background: linear-gradient(180deg, #2c7a7b, #1cc88a);
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        .sidebar-heading {
            padding: 1.5rem 1rem;
            font-size: 1.2rem;
            font-weight: 700;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, .3);
        }

        .list-group-item {
            background: transparent;
            color: #fff;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, .1);
        }

        .list-group-item:hover {
            background-color: rgba(255, 255, 255, .15);
        }

        #page-content-wrapper {
            flex: 1;
            padding: 0;
        }

        /* === HEADER BARU (SAMA KAYA PERAWATAN) === */
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
            font-size: 1.75rem;
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

        .card-header {
            font-weight: 600;
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
        }
    </style>
</head>

<body>

    <div id="wrapper">

        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">

            <!-- HEADER -->
            <div class="dashboard-header">
                <h3>LAPORAN</h3>

                <div class="admin-info">
                    <i class="bi bi-person-circle"></i>
                    <span><?= $_SESSION['nama_pengguna']; ?> (<?= $_SESSION['level']; ?>)</span>
                </div>
            </div>

            <!-- CONTENT -->
            <div class="content">

                <!-- 1️⃣ Filter Pencarian -->
                <form class="d-flex gap-2 mb-3" method="GET">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari laporan..." value="<?= $_GET['search'] ?? '' ?>">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="Belum Dimulai">Belum Dimulai</option>
                        <option value="Sedang Proses">Sedang Proses</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                    <button type="submit" class="btn btn-success btn-sm">🔍 Filter</button>
                </form>

                <!-- 2️⃣ Tombol Export -->
                <div class="mb-3 text-end">
                    <a href="export_semua_laporan_excel.php" class="btn btn-outline-primary btn-sm">📥 Export Semua Excel</a>
                    <a href="cetak_semua_laporan_pdf.php" target="_blank" class="btn btn-danger btn-sm">🖨 Cetak Semua PDF</a>
                </div>


                <!-- 3️⃣ Tabel Laporan dengan Label Warna -->
                <div class="card">
                    <div class="card-header">Data Laporan</div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Jenis Laporan</th>
                                    <th>Jumlah</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($laporan_list as $i => $l):
                                    // Tentukan warna status
                                    $status = $l[0] === 'Laporan Perawatan Berjalan' ? 'Sedang Proses' : 'Selesai';
                                    $badgeColor = match ($status) {
                                        'Belum Dimulai' => 'secondary',
                                        'Sedang Proses' => 'warning',
                                        'Selesai' => 'success',
                                        default => 'primary'
                                    };
                                ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= $l[0] ?></td>
                                        <td><?= $l[1] ?></td>
                                        <td><?= $l[2] ?></td>
                                        <td><span class="badge bg-<?= $badgeColor ?>"><?= $status ?></span></td>
                                        <td>
                                            <a href="<?= getReportLink($l[0]) ?>" class="btn btn-sm btn-success"><i class="bi bi-eye"></i> Lihat</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>