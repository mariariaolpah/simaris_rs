<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ================= FORMAT TANGGAL =================
function formatTanggal($tanggal)
{
    if (!$tanggal || $tanggal == '0000-00-00') {
        return '-';
    }
    return date('d-m-Y', strtotime($tanggal));
}

// ================= PAGINATION & FILTER ================= //
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// MENGAMBIL PARAMETER FILTER DARI URL
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';
$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : "";
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : "";

$whereConditions = [];

// === FILTER OTOMATIS BERDASARKAN ROLE TEKNISI (AHMAD FAUZI) ===
$nama_user_aktif = $_SESSION['nama_pengguna'] ?? '';
if (strpos(strtolower($nama_user_aktif), 'ahmad') !== false) {
    // Hanya menampilkan data di mana Ahmad bertindak sebagai Teknisi Perawatan atau Petugas Kalibrasi
    $whereConditions[] = "(p.teknisi LIKE '%ahmad%' OR p.petugas_kalibrasi LIKE '%ahmad%')";
}

// FILTER PENCARIAN
if ($search != '') {
    $whereConditions[] = "(p.nama_aset LIKE '%$search%' 
        OR p.teknisi LIKE '%$search%' 
        OR p.petugas_kalibrasi LIKE '%$search%' 
        OR p.status LIKE '%$search%'
        OR a.lokasi LIKE '%$search%'
        OR a.kategori_aset LIKE '%$search%')";
}

// FILTER KATEGORI TAB
if ($kategori_filter == 'medis') {
    $whereConditions[] = "a.kategori_aset = 'Medis'";
} elseif ($kategori_filter == 'non-medis') {
    $whereConditions[] = "a.kategori_aset = 'Non-Medis'";
}

// FILTER TAHUN
if (!empty($tahun_filter)) {
    $whereConditions[] = "YEAR(p.tanggal) = $tahun_filter";
}

// FILTER STATUS
if ($status_filter != '') {
    $whereConditions[] = "p.status = '$status_filter'";
}

$whereClause = "";
if (count($whereConditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// HELPER URL PARAMETERS (Agar filter tidak hilang saat ganti halaman / tab)
$url_params = "";
if ($search != '') $url_params .= '&search=' . urlencode($search);
if ($tahun_filter != '') $url_params .= '&tahun=' . urlencode($tahun_filter);
if ($status_filter != '') $url_params .= '&status=' . urlencode($status_filter);

// ================= QUERY DATA (JOIN ASET FIX COLLATION) ================= //
$count_query = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM perawatan p
    LEFT JOIN aset a ON p.nama_aset COLLATE utf8mb4_general_ci = a.nama_aset COLLATE utf8mb4_general_ci
    $whereClause
");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_page = ceil($total_data / $limit);

$query = mysqli_query($koneksi, "
    SELECT p.*, a.kategori_aset, a.lokasi 
    FROM perawatan p
    LEFT JOIN aset a ON p.nama_aset COLLATE utf8mb4_general_ci = a.nama_aset COLLATE utf8mb4_general_ci
    $whereClause
    ORDER BY p.id DESC
    LIMIT $limit OFFSET $offset
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Perawatan | SIMARIS</title>
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
            padding: 40px 30px 50px 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        .card-header {
            font-weight: 600;
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 15px 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
            margin-top: 15px;
            padding: 0 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 12px 20px;
            color: #64748b;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active-medis {
            color: #dc2626 !important;
            border-bottom: 3px solid #dc2626;
            background: #fffafb;
        }

        .nav-tabs .nav-link.active-nonmedis {
            color: #0284c7 !important;
            border-bottom: 3px solid #0284c7;
            background: #f0f9ff;
        }

        .nav-tabs .nav-link.active-semua {
            color: #16a34a !important;
            border-bottom: 3px solid #16a34a;
            background: #f0fdf4;
        }

        table th {
            background: #f8fafc !important;
            white-space: nowrap;
        }

        table td {
            vertical-align: middle !important;
            white-space: nowrap;
        }

        .btn-action {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .anim-blink {
            animation: blinker 1.5s linear infinite;
        }

        @keyframes blinker {
            50% {
                opacity: 0.3;
            }
        }

        .filter-select {
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>

<body>

    <div id="wrapper">

        <?php
        // Mendeteksi apakah user yang login adalah teknisi
        $nama_user_aktif = strtolower($_SESSION['nama_pengguna'] ?? '');
        $is_teknisi = (strpos($nama_user_aktif, 'budi') !== false || strpos($nama_user_aktif, 'ahmad') !== false);

        if ($is_teknisi) {
            // Jika teknisi, tampilkan sidebar khusus teknisi dari dalam folder pages
            include(__DIR__ . '/sidebar_teknisi.php');
        } else {
            // Jika bukan (misal admin), tampilkan sidebar default
            include(__DIR__ . '/../sidebar.php');
        }
        ?>

        <div id="page-content-wrapper">

            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-tools"></i> PERAWATAN / PEMELIHARAAN</h4>
                <div>
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                </div>
            </div>

            <div class="content">

                <div class="card">

                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <div class="d-flex align-items-center fw-medium">
                            <i class="bi bi-calendar-check me-2"></i> Data Perawatan & Jadwal Kalibrasi
                        </div>

                        <div class="d-flex gap-2 flex-wrap align-items-center ms-auto">

                            <form method="GET" class="d-flex gap-1 align-items-center m-0">

                                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">

                                <select name="status" class="form-select text-dark filter-select" style="min-width: 140px;">
                                    <option value="">Semua Status</option>
                                    <option value="Belum Dimulai" <?= ($status_filter == 'Belum Dimulai') ? 'selected' : '' ?>>Belum Dimulai</option>
                                    <option value="Sedang Proses" <?= ($status_filter == 'Sedang Proses') ? 'selected' : '' ?>>Sedang Proses</option>
                                    <option value="Selesai" <?= ($status_filter == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                                </select>

                                <select name="tahun" class="form-select text-dark filter-select" style="min-width: 130px;">
                                    <option value="">Semua Tahun</option>
                                    <?php
                                    $thn_skrg = date('Y');
                                    for ($thn = $thn_skrg; $thn >= ($thn_skrg - 10); $thn--) {
                                        $selected = ($tahun_filter == $thn) ? 'selected' : '';
                                        echo "<option value='$thn' $selected>$thn</option>";
                                    }
                                    ?>
                                </select>

                                <input type="text"
                                    name="search"
                                    class="form-control text-dark filter-select"
                                    placeholder="Cari aset..."
                                    value="<?= htmlspecialchars($search) ?>"
                                    style="min-width: 150px;">

                                <button class="btn btn-secondary btn-sm" style="border-radius: 6px;">
                                    <i class="bi bi-search"></i>
                                </button>

                            </form>

                            <a href="perawatan_tambah.php" class="btn btn-light btn-sm text-dark d-flex align-items-center" style="border-radius: 6px; height: 31px;">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </a>

                            <a href="perawatan_cetak.php?kategori=<?= urlencode($kategori_filter) ?><?= $url_params ?>"
                                class="btn btn-danger btn-sm text-white d-flex align-items-center" target="_blank" style="border-radius: 6px; height: 31px;">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
                            </a>

                        </div>

                    </div>

                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'medis') ? 'active-medis' : '' ?>"
                                href="?kategori=medis<?= $url_params ?>">
                                🩺 Aset Medis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'non-medis') ? 'active-nonmedis' : '' ?>"
                                href="?kategori=non-medis<?= $url_params ?>">
                                🖥️ Aset Non-Medis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'semua') ? 'active-semua' : '' ?>"
                                href="?kategori=semua<?= $url_params ?>">
                                📋 Semua Aset
                            </a>
                        </li>
                    </ul>

                    <div class="card-body table-responsive p-0 pt-2">

                        <table class="table table-bordered table-hover text-center m-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Aset</th>
                                    <th>Lokasi Ruangan</th>
                                    <th>Kategori</th>
                                    <th>Teknisi Perawatan</th>
                                    <th>Petugas Kalibrasi</th>
                                    <th>Tgl Perawatan</th>
                                    <th>Jadwal Kalibrasi Berikutnya</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (mysqli_num_rows($query) == 0): ?>
                                    <tr>
                                        <td colspan="10" class="py-5 text-center text-muted">
                                            <i class="bi bi-inboxes d-block mb-2" style="font-size: 2rem;"></i>
                                            Tidak ada jadwal perawatan / kalibrasi di kategori ini.
                                        </td>
                                    </tr>
                                <?php else: ?>

                                    <?php
                                    $no = $offset + 1;
                                    while ($p = mysqli_fetch_assoc($query)):
                                    ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td class="text-start fw-bold"><?= htmlspecialchars($p['nama_aset']) ?></td>

                                            <td class="text-start">
                                                <i class="bi bi-geo-alt text-danger me-1"></i> <?= htmlspecialchars($p['lokasi'] ?? '-') ?>
                                            </td>

                                            <td>
                                                <?php if (($p['kategori_aset'] ?? '') == 'Medis'): ?>
                                                    <span class="badge bg-danger">Medis</span>
                                                <?php elseif (($p['kategori_aset'] ?? '') == 'Non-Medis'): ?>
                                                    <span class="badge bg-primary">Non-Medis</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td><?= htmlspecialchars($p['teknisi'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($p['petugas_kalibrasi'] ?? '-') ?></td>
                                            <td><?= formatTanggal($p['tanggal']) ?></td>

                                            <td>
                                                <?php
                                                $tgl_berikutnya = $p['tanggal_kalibrasi_berikutnya'];
                                                if ($tgl_berikutnya && $tgl_berikutnya != '0000-00-00') {
                                                    echo "<span class='fw-bold'>" . formatTanggal($tgl_berikutnya) . "</span><br>";

                                                    $selisih_detik = strtotime($tgl_berikutnya) - strtotime('today');
                                                    $selisih_hari = floor($selisih_detik / (60 * 60 * 24));

                                                    if ($selisih_hari <= 7 && $selisih_hari >= 0) {
                                                        echo "<span class='badge bg-warning text-dark mt-1 anim-blink'><i class='bi bi-exclamation-triangle'></i> H-$selisih_hari Kalibrasi!</span>";
                                                    } elseif ($selisih_hari < 0) {
                                                        $lewat = abs($selisih_hari);
                                                        echo "<span class='badge bg-danger mt-1 anim-blink'><i class='bi bi-x-circle'></i> Terlewat $lewat Hari!</span>";
                                                    } else {
                                                        echo "<span class='badge bg-success mt-1'>Aman</span>";
                                                    }
                                                } else {
                                                    echo "-";
                                                }
                                                ?>
                                            </td>

                                            <td>
                                                <?php if ($p['status'] == 'Selesai'): ?>
                                                    <span class="badge bg-success">Selesai</span>
                                                <?php elseif ($p['status'] == 'Sedang Proses'): ?>
                                                    <span class="badge bg-warning text-dark">Proses</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Belum Dimulai</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <div class="btn-action">
                                                    <a href="perawatan_edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm text-white">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <a href="perawatan_hapus.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus data perawatan ini?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                <?php endif; ?>

                            </tbody>
                        </table>

                    </div>

                    <?php if ($total_page > 1): ?>
                        <div class="card-footer bg-white border-0 pt-3 pb-3">
                            <div class="d-flex justify-content-end">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?kategori=<?= urlencode($kategori_filter) ?>&page=<?= $page - 1 ?><?= $url_params ?>">Prev</a>
                                        </li>
                                        <li class="page-item disabled">
                                            <span class="page-link"><?= $page ?> / <?= $total_page ?></span>
                                        </li>
                                        <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?kategori=<?= urlencode($kategori_filter) ?>&page=<?= $page + 1 ?><?= $url_params ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</body>

</html>