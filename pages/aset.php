<?php
session_start();

if (!isset($_SESSION['level']) && isset($_SESSION['role'])) {
    $_SESSION['level'] = $_SESSION['role'];
}
if (!isset($_SESSION['level'])) {
    $_SESSION['level'] = 'user';
}

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

// ================= FILTER & PAGINATION =================
$limit = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Menangkap Tab Aktif (Default: medis)
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'medis';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";
$tahun_filter = isset($_GET['tahun']) ? mysqli_real_escape_string($koneksi, $_GET['tahun']) : "";
$asal_usul_filter = isset($_GET['asal_usul']) ? mysqli_real_escape_string($koneksi, $_GET['asal_usul']) : "";

$whereConditions = [];

// Filter berdasarkan pencarian
if ($search != '') {
    $whereConditions[] = "(nama_aset LIKE '%$search%' OR jenis LIKE '%$search%' OR lokasi LIKE '%$search%')";
}

// Filter berdasarkan Tab
if ($kategori_filter == 'medis') {
    $whereConditions[] = "kategori_aset = 'Medis'";
} elseif ($kategori_filter == 'non-medis') {
    $whereConditions[] = "kategori_aset = 'Non-Medis'";
}

// Filter berdasarkan Tahun
if ($tahun_filter != '') {
    $whereConditions[] = "YEAR(tanggal_masuk) = '$tahun_filter'";
}

// Filter berdasarkan Asal Usul
if ($asal_usul_filter != '') {
    $whereConditions[] = "asal_usul = '$asal_usul_filter'";
}

$whereClause = "";
if (count($whereConditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// Helper URL parameter agar pagination dan tab tidak reset saat difilter
$url_params = "";
if ($search != '') $url_params .= '&search=' . urlencode($search);
if ($tahun_filter != '') $url_params .= '&tahun=' . urlencode($tahun_filter);
if ($asal_usul_filter != '') $url_params .= '&asal_usul=' . urlencode($asal_usul_filter);

// Ambil data dengan limit, offset, dan filter
$query = mysqli_query($koneksi, "SELECT * FROM aset $whereClause ORDER BY id_aset ASC LIMIT $limit OFFSET $offset");
// Hitung total data untuk paginasi
$countQuery = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM aset $whereClause");
$total_row = mysqli_fetch_assoc($countQuery);
$total_data = $total_row['total'];
$total_page = ceil($total_data / $limit);

$aset_list = [];
while ($row = mysqli_fetch_assoc($query)) {
    $aset_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Aset / Infrastruktur | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f4f6f9;
            color: #333;
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
            justify-content: space-between;
            align-items: center;
        }

        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
            margin-top: 15px;
            padding: 0 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            font-weight: 500;
            color: #64748b;
            padding: 12px 20px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            color: #2c7a7b;
            border-bottom: 3px solid #cbd5e1;
        }

        .nav-tabs .nav-link.active-medis {
            color: #dc2626 !important;
            font-weight: bold;
            border-bottom: 3px solid #dc2626;
            background: #fffafb;
        }

        .nav-tabs .nav-link.active-nonmedis {
            color: #0284c7 !important;
            font-weight: bold;
            border-bottom: 3px solid #0284c7;
            background: #f0f9ff;
        }

        .nav-tabs .nav-link.active-semua {
            color: #16a34a !important;
            font-weight: bold;
            border-bottom: 3px solid #16a34a;
            background: #f0fdf4;
        }

        .table-responsive {
            border-radius: 0 0 12px 12px;
            overflow-x: auto;
        }

        table {
            margin-bottom: 0 !important;
        }

        table th {
            background-color: #f8fafc !important;
            color: #4a5568 !important;
            font-weight: bold !important;
            text-transform: uppercase;
            font-size: 0.8rem;
            padding: 14px 16px !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        table td {
            vertical-align: middle !important;
            white-space: nowrap;
            padding: 12px 16px !important;
            font-size: 0.9rem;
            color: #4a5568;
        }

        table tbody tr:hover {
            background-color: #fafffd !important;
        }

        .badge-medis {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }

        .badge-nonmedis {
            background-color: #e0f2fe;
            color: #0284c7;
            border: 1px solid #7dd3fc;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }

        .btn-action-group {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .btn-sm-custom {
            padding: 5px 10px;
            font-size: 0.85rem;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-box-seam"></i> MANAJEMEN ASET / INFRASTRUKTUR</h4>
                <div class="small fw-medium">
                    <i class="bi bi-person-circle-fill"></i> <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                    <span class="badge bg-light text-dark ms-1" style="text-transform: uppercase;"><?= htmlspecialchars($_SESSION['level']); ?></span>
                </div>
            </div>

            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-table"></i> <span>Daftar Inventaris RS Bhayangkara</span>
                        </div>

                        <div class="d-flex gap-3 align-items-center">
                            <form method="GET" class="m-0 d-flex gap-2 align-items-center">
                                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">

                                <div class="d-flex flex-column gap-1">
                                    <select name="tahun" class="form-select form-select-sm bg-light text-dark border-0" style="border-radius: 6px; cursor: pointer; width: 140px;">
                                        <option value="">Semua Tahun</option>
                                        <?php
                                        $tahun_sekarang = date('Y');
                                        for ($i = $tahun_sekarang; $i >= 2020; $i--) {
                                            $selected = ($tahun_filter == $i) ? 'selected' : '';
                                            echo "<option value='$i' $selected>$i</option>";
                                        }
                                        ?>
                                    </select>

                                    <select name="asal_usul" class="form-select form-select-sm bg-light text-dark border-0" style="border-radius: 6px; cursor: pointer; width: 140px;">
                                        <option value="">Semua Asal Usul</option>
                                        <option value="Pembelian" <?= ($asal_usul_filter == 'Pembelian') ? 'selected' : '' ?>>Pembelian</option>
                                        <option value="Hibah" <?= ($asal_usul_filter == 'Hibah') ? 'selected' : '' ?>>Hibah</option>
                                        <option value="Sewa" <?= ($asal_usul_filter == 'Sewa') ? 'selected' : '' ?>>Sewa</option>
                                    </select>
                                </div>

                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input type="text" name="search" class="form-control bg-light text-dark border-0"
                                        placeholder="Cari aset..." style="border-radius: 6px 0 0 6px;"
                                        value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-light text-dark" style="border-radius: 0 6px 6px 0; border-left: 1px solid #dee2e6;">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>

                            <div class="d-flex gap-2">
                                <a href="aset_tambah.php" class="btn btn-light btn-sm text-nowrap" style="border-radius: 6px;">
                                    <i class="bi bi-plus-lg"></i> Tambah
                                </a>
                                <a href="aset_cetak.php?kategori=<?= $kategori_filter ?><?= $url_params ?>"
                                    target="_blank" class="btn btn-light btn-sm text-nowrap" style="border-radius: 6px;">
                                    <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
                                </a>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'medis') ? 'active-medis' : '' ?>"
                                href="?kategori=medis<?= $url_params ?>">
                                ⚕️ Aset Medis (Alkes)
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
                                📦 Semua Aset
                            </a>
                        </li>
                    </ul>

                    <div class="card-body p-0 pt-2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Nama Aset</th>
                                        <th>Kategori</th>
                                        <th>Jenis</th>
                                        <th>Tipe</th>
                                        <th>Lokasi Ruangan</th>
                                        <th>Total Stok</th>
                                        <th>Rincian Ketersediaan</th>
                                        <th>Asal Usul</th>
                                        <th>Harga Perolehan</th>
                                        <th>Umur Eko.</th>
                                        <th>Tanggal Masuk</th>
                                        <th>Dokumen</th>
                                        <th style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($aset_list)): ?>
                                        <tr>
                                            <td colspan="14" class="text-muted py-5 text-center">
                                                <i class="bi bi-inboxes text-secondary d-block mb-2" style="font-size: 2rem;"></i>
                                                Data untuk kategori ini belum tersedia atau tidak ditemukan.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($aset_list as $i => $a): ?>
                                            <tr>
                                                <td class="text-secondary"><?= (($page - 1) * $limit) + $i + 1 ?></td>
                                                <td class="fw-bold text-dark text-start"><?= htmlspecialchars($a['nama_aset']) ?></td>

                                                <td>
                                                    <?php if (isset($a['kategori_aset']) && $a['kategori_aset'] == 'Medis'): ?>
                                                        <span class="badge-medis">Medis</span>
                                                    <?php elseif (isset($a['kategori_aset']) && $a['kategori_aset'] == 'Non-Medis'): ?>
                                                        <span class="badge-nonmedis">Non-Medis</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-secondary border">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td><?= htmlspecialchars($a['jenis']) ?></td>
                                                <td><?= htmlspecialchars($a['tipe_aset']) ?></td>
                                                <td class="text-start"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($a['lokasi']) ?></td>

                                                <td class="text-center">
                                                    <div class="fw-bold text-primary" style="font-size: 1.1rem;">
                                                        <?= isset($a['total_stok']) ? htmlspecialchars($a['total_stok']) : (isset($a['stok']) ? htmlspecialchars($a['stok']) : '0') ?> Unit
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="d-flex flex-column gap-1 align-items-start">
                                                        <span class="badge bg-success w-100 text-start" style="font-size: 0.8rem; font-weight: normal;">
                                                            <i class="bi bi-check-circle-fill me-1"></i> Tersedia: <?= isset($a['stok_tersedia']) ? htmlspecialchars($a['stok_tersedia']) : '0' ?>
                                                        </span>
                                                        <span class="badge bg-danger w-100 text-start" style="font-size: 0.8rem; font-weight: normal;">
                                                            <i class="bi bi-x-circle-fill me-1"></i> Rusak: <?= isset($a['stok_rusak']) ? htmlspecialchars($a['stok_rusak']) : '0' ?>
                                                        </span>
                                                        <span class="badge bg-warning text-dark w-100 text-start" style="font-size: 0.8rem; font-weight: normal;">
                                                            <i class="bi bi-exclamation-circle-fill me-1"></i> Perawatan: <?= isset($a['stok_perawatan']) ? htmlspecialchars($a['stok_perawatan']) : '0' ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($a['asal_usul']) ?></td>
                                                <td class="text-end fw-bold text-dark">Rp <?= number_format($a['harga'], 0, ',', '.') ?></td>

                                                <td class="fw-bold text-success">
                                                    <?= (isset($a['umur_ekonomis']) && $a['umur_ekonomis'] > 0) ? $a['umur_ekonomis'] . ' Thn' : '-' ?>
                                                </td>

                                                <td><?= formatTanggal($a['tanggal_masuk']) ?></td>

                                                <td>
                                                    <?php if (!empty($a['dokumen'])): ?>
                                                        <a href="../assets/dokumen/<?= htmlspecialchars($a['dokumen']) ?>"
                                                            class="btn btn-outline-info btn-sm-custom" target="_blank" title="Lihat Berkas">
                                                            <i class="bi bi-file-earmark-text-fill"></i> Lihat
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <div class="btn-action-group">
                                                        <a href="aset_edit.php?id=<?= $a['id_aset'] ?>" class="btn btn-warning text-white btn-sm-custom" title="Edit Data">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="aset_hapus.php?id=<?= $a['id_aset'] ?>" class="btn btn-danger btn-sm-custom" title="Hapus Data"
                                                            onclick="return confirm('Apakah Anda yakin ingin menghapus aset ini?');">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer bg-white d-flex justify-content-end py-3 border-top-0">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" style="border-radius: 6px 0 0 6px;"
                                            href="?page=<?= $page - 1 ?>&kategori=<?= $kategori_filter ?><?= $url_params ?>">
                                            Prev
                                        </a>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link bg-light text-dark fw-bold"><?= $page ?> / <?= $total_page ?></span>
                                    </li>
                                    <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                        <a class="page-link" style="border-radius: 0 6px 6px 0;"
                                            href="?page=<?= $page + 1 ?>&kategori=<?= $kategori_filter ?><?= $url_params ?>">
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