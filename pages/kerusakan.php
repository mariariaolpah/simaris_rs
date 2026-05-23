<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ================= PAGINATION & FILTER ================= //
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// MENGAMBIL PARAMETER FILTER DARI URL
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'medis';
$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : "";
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : "";

$whereConditions = [];

// FILTER PENCARIAN
if ($search != '') {
    $whereConditions[] = "(kerusakan.nama_aset LIKE '%$search%' 
        OR kerusakan.status LIKE '%$search%' 
        OR kerusakan.keterangan LIKE '%$search%'
        OR kerusakan.pelapor LIKE '%$search%'
        OR kerusakan.teknisi LIKE '%$search%'
        OR aset.lokasi LIKE '%$search%')";
}

// FILTER KATEGORI
if ($kategori_filter == 'medis') {
    $whereConditions[] = "aset.kategori_aset = 'Medis'";
} elseif ($kategori_filter == 'non-medis') {
    $whereConditions[] = "aset.kategori_aset = 'Non-Medis'";
}

// FILTER TAHUN
if (!empty($tahun_filter)) {
    $whereConditions[] = "YEAR(kerusakan.tanggal) = $tahun_filter";
}

// FILTER STATUS
if ($status_filter != '') {
    $whereConditions[] = "kerusakan.status = '$status_filter'";
}

// ==== PROTEKSI & FILTER KHUSUS ROLE TEKNISI (BUDI SETIAWAN) ====
$nama_user_aktif = $_SESSION['nama_pengguna'] ?? '';
if (isset($_SESSION['level']) && $_SESSION['level'] == 'teknisi') {
    if (strpos(strtolower($nama_user_aktif), 'budi') !== false) {
        $whereConditions[] = "kerusakan.teknisi LIKE '%budi%'";
    } else {
        $nama_teknisi = mysqli_real_escape_string($koneksi, $nama_user_aktif);
        $whereConditions[] = "kerusakan.teknisi = '$nama_teknisi'";
    }
}

// MERANGKAI KONDISI WHERE
$whereClause = "";
if (count($whereConditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// HELPER URL PARAMETERS (Agar filter tidak hilang saat ganti halaman / tab)
$url_params = "";
if ($search != '') $url_params .= '&search=' . urlencode($search);
if ($tahun_filter != '') $url_params .= '&tahun=' . urlencode($tahun_filter);
if ($status_filter != '') $url_params .= '&status=' . urlencode($status_filter);

// ================= TOTAL DATA ================= //
$count_query = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM kerusakan 
    LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset
    $whereClause
");

$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_page = ceil($total_data / $limit);

// ================= QUERY DATA ================= //
$query = mysqli_query($koneksi, "
    SELECT 
        kerusakan.*,
        kerusakan.tanggal AS tanggal_lapor,
        aset.kategori_aset,
        aset.lokasi,
        aset.stok_tersedia,
        aset.stok_rusak,
        aset.stok_perawatan
    FROM kerusakan 
    LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset
    $whereClause
    ORDER BY kerusakan.id DESC
    LIMIT $offset, $limit
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Kerusakan Aset | SIMARIS</title>

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
        }

        .content {
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            flex-wrap: wrap;
            /* Mencegah form berantakan di layar kecil */
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

        .table-responsive {
            overflow-x: auto;
        }

        table th {
            background: #f8fafc !important;
            white-space: nowrap;
            padding: 14px !important;
        }

        table td {
            white-space: nowrap;
            padding: 12px !important;
            vertical-align: middle !important;
        }

        .badge-medis {
            background: #fee2e2;
            color: #dc2626;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .badge-nonmedis {
            background: #e0f2fe;
            color: #0284c7;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .kolom-keterangan {
            white-space: normal !important;
            min-width: 250px;
            text-align: left;
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
        if (isset($_SESSION['level']) && $_SESSION['level'] == 'teknisi') {
            include(__DIR__ . '/sidebar_teknisi.php');
        } else {
            include(__DIR__ . '/../sidebar.php');
        }
        ?>

        <div id="page-content-wrapper">

            <div class="dashboard-header">
                <h4 class="fw-bold m-0">
                    <i class="bi bi-exclamation-octagon"></i> DATA KERUSAKAN ASET
                </h4>

                <div>
                    <i class="bi bi-person-circle-fill"></i>
                    <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                </div>
            </div>

            <div class="content">

                <div class="card">

                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">

                        <div class="d-flex align-items-center fw-medium">
                            <i class="bi bi-table me-2"></i>
                            Daftar Pelaporan Kerusakan <?= ($_SESSION['level'] == 'teknisi') ? '(Tugas Anda)' : '' ?>
                        </div>

                        <div class="d-flex gap-2 flex-wrap align-items-center ms-auto">

                            <form method="GET" class="d-flex gap-1 align-items-center m-0">

                                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">

                                <select name="status" class="form-select text-dark filter-select" style="min-width: 140px;">
                                    <option value="">Semua Status</option>
                                    <option value="Baru" <?= ($status_filter == 'Baru') ? 'selected' : '' ?>>Baru</option>
                                    <option value="Rusak" <?= ($status_filter == 'Rusak') ? 'selected' : '' ?>>Rusak</option>
                                    <option value="Perlu Perawatan" <?= ($status_filter == 'Perlu Perawatan') ? 'selected' : '' ?>>Perlu Perawatan</option>
                                    <option value="Diproses" <?= ($status_filter == 'Diproses') ? 'selected' : '' ?>>Diproses</option>
                                    <option value="Dalam Perbaikan" <?= ($status_filter == 'Dalam Perbaikan') ? 'selected' : '' ?>>Dalam Perbaikan</option>
                                    <option value="Selesai Diperbaiki" <?= ($status_filter == 'Selesai Diperbaiki') ? 'selected' : '' ?>>Selesai Diperbaiki</option>
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
                                    placeholder="Cari aset/teknisi..."
                                    value="<?= htmlspecialchars($search) ?>"
                                    style="min-width: 150px;">

                                <button class="btn btn-light btn-sm" style="border-radius: 6px;">
                                    <i class="bi bi-search"></i>
                                </button>

                            </form>

                            <?php if ($_SESSION['level'] != 'teknisi'): ?>
                                <a href="kerusakan_tambah.php" class="btn btn-light btn-sm d-flex align-items-center" style="border-radius: 6px; height: 31px;">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah
                                </a>
                            <?php endif; ?>

                            <a href="kerusakan_cetak.php?kategori=<?= urlencode($kategori_filter) ?><?= $url_params ?>"
                                target="_blank"
                                class="btn btn-light btn-sm d-flex align-items-center" style="border-radius: 6px; height: 31px;">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
                            </a>

                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs">

                    <li class="nav-item">
                        <a class="nav-link <?= ($kategori_filter == 'medis') ? 'active-medis' : '' ?>"
                            href="?kategori=medis<?= $url_params ?>">
                            💼 Aset Medis
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

                <div class="card-body p-0 pt-2">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover text-center align-middle">

                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Aset & Info Stok</th>
                                    <th>Lokasi Ruangan</th>
                                    <th>Kategori</th>
                                    <th>Pelapor</th>
                                    <th>Tanggal Lapor</th>
                                    <th>Rincian Kerusakan</th>
                                    <th>Teknisi Perbaikan</th>
                                    <th>Status Laporan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php if (mysqli_num_rows($query) == 0): ?>

                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-5">
                                            <i class="bi bi-inboxes d-block mb-2" style="font-size: 2rem;"></i>
                                            Tidak ada data laporan kerusakan yang sesuai filter.
                                        </td>
                                    </tr>

                                <?php else: ?>

                                    <?php $no = $offset + 1; ?>

                                    <?php while ($row = mysqli_fetch_assoc($query)): ?>

                                        <tr>

                                            <td><?= $no++ ?></td>

                                            <td class="text-start">
                                                <span class="fw-bold d-block mb-1 text-dark">
                                                    <?= htmlspecialchars($row['nama_aset']) ?>
                                                </span>
                                                <div class="d-flex gap-1" style="font-size: 0.75rem;">
                                                    <span class="badge bg-danger rounded-pill fw-normal" title="Total stok yang tercatat rusak">
                                                        Rusak: <?= isset($row['stok_rusak']) ? htmlspecialchars($row['stok_rusak']) : '0' ?>
                                                    </span>
                                                    <span class="badge bg-success rounded-pill fw-normal" title="Sisa stok yang masih bisa dipakai/dipinjam">
                                                        Tersedia: <?= isset($row['stok_tersedia']) ? htmlspecialchars($row['stok_tersedia']) : '0' ?>
                                                    </span>
                                                </div>
                                            </td>

                                            <td class="text-start">
                                                <i class="bi bi-geo-alt text-danger me-1"></i>
                                                <?= htmlspecialchars($row['lokasi'] ?? '-') ?>
                                            </td>

                                            <td>

                                                <?php if (($row['kategori_aset'] ?? '') == 'Medis'): ?>
                                                    <span class="badge-medis">Medis</span>
                                                <?php else: ?>
                                                    <span class="badge-nonmedis">Non-Medis</span>
                                                <?php endif; ?>

                                            </td>

                                            <td>
                                                <i class="bi bi-person text-secondary me-1"></i>
                                                <?= htmlspecialchars($row['pelapor'] ?? '-') ?>
                                            </td>

                                            <td>
                                                <?= !empty($row['tanggal_lapor'])
                                                    ? date('d-m-Y', strtotime($row['tanggal_lapor']))
                                                    : '-' ?>
                                            </td>

                                            <td class="kolom-keterangan">
                                                <?= htmlspecialchars($row['keterangan']) ?>
                                            </td>

                                            <td class="fw-medium text-primary">
                                                <?= htmlspecialchars($row['teknisi'] ?? '-') ?>
                                            </td>

                                            <td>

                                                <?php
                                                $status = $row['status'] ?? 'Baru';
                                                $bg = 'bg-secondary';

                                                if ($status == 'Rusak') {
                                                    $bg = 'bg-danger';
                                                } elseif ($status == 'Perlu Perawatan') {
                                                    $bg = 'bg-warning text-dark';
                                                } elseif ($status == 'Dalam Perbaikan' || $status == 'Diproses') {
                                                    $bg = 'bg-info text-dark';
                                                } elseif ($status == 'Selesai Diperbaiki' || $status == 'Selesai') {
                                                    $bg = 'bg-success';
                                                }
                                                ?>

                                                <span class="badge <?= $bg ?> shadow-sm px-3 py-2">
                                                    <?= htmlspecialchars($status) ?>
                                                </span>

                                            </td>

                                            <td>

                                                <div class="d-flex gap-1 justify-content-center">

                                                    <a href="kerusakan_edit.php?id=<?= $row['id'] ?>"
                                                        class="btn btn-warning btn-sm text-white" title="Edit Laporan / Update Status">
                                                        <i class="bi bi-pencil-square"></i> <?= ($_SESSION['level'] == 'teknisi') ? 'Eksekusi' : '' ?>
                                                    </a>

                                                    <?php if ($_SESSION['level'] != 'teknisi'): ?>
                                                        <a href="kerusakan_hapus.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Hapus data laporan ini?')" title="Hapus Laporan">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                    <div class="card-footer bg-white d-flex justify-content-end py-3 border-top-0">
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" style="border-radius: 6px 0 0 6px;"
                                        href="?page=<?= $page - 1 ?>&kategori=<?= urlencode($kategori_filter) ?><?= $url_params ?>">
                                        Prev
                                    </a>
                                </li>
                                <li class="page-item disabled">
                                    <span class="page-link bg-light text-dark fw-bold"><?= $page ?> / <?= max(1, $total_page) ?></span>
                                </li>
                                <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                    <a class="page-link" style="border-radius: 0 6px 6px 0;"
                                        href="?page=<?= $page + 1 ?>&kategori=<?= urlencode($kategori_filter) ?><?= $url_params ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>

                </div>

            </div>

        </div>

    </div>

    </div>

</body>

</html>