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

// SEARCH
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";

// FILTER KATEGORI (DEFAULT: MEDIS AGAR FOKUS PADA INOVASI ALKES)
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'medis';

$whereConditions = [];
if ($search != '') {
    $whereConditions[] = "(aset.nama_aset LIKE '%$search%' OR audit_fisik.auditor LIKE '%$search%' OR audit_fisik.keterangan LIKE '%$search%')";
}

if ($kategori_filter == 'medis') {
    $whereConditions[] = "aset.kategori_aset = 'Medis'";
} elseif ($kategori_filter == 'non-medis') {
    $whereConditions[] = "aset.kategori_aset = 'Non-Medis'";
}

$whereClause = "";
if (count($whereConditions) > 0) {
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
}

// TOTAL DATA UNTUK PAGINATION
$count_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM audit_fisik 
    JOIN aset ON audit_fisik.id_aset = aset.id_aset
    $whereClause");
$total_data = mysqli_fetch_assoc($count_query)['total'];
$total_page = ceil($total_data / $limit);

// QUERY UTAMA DENGAN FILTER KATEGORI SINKRON
$query = mysqli_query($koneksi, "SELECT audit_fisik.*, aset.nama_aset, aset.kategori_aset FROM audit_fisik 
    JOIN aset ON audit_fisik.id_aset = aset.id_aset
    $whereClause
    ORDER BY id_audit DESC
    LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Audit Fisik Aset | SIMARIS</title>
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
            justify-content: space-between;
            align-items: center;
        }

        /* TABS STYLING SINKRON */
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

        /* TABEL LAYOUT SINKRON */
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
            white-space: nowrap;
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

        /* BADGE KATEGORI SINKRON */
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

        /* SOLUSI KUNCI: MODIFIKASI KHUSUS KOLOM KETERANGAN AGAR TURUN KE BAWAH */
        .kolom-keterangan {
            white-space: normal !important;
            /* Membuat teks panjang otomatis bungkus turun ke bawah */
            min-width: 300px;
            /* Memberi ruang lebar min agar teks tidak terlalu sempit */
            max-width: 450px;
            /* Membatasi agar tidak merusak keindahan layout tabel */
            text-align: left !important;
            /* Menjaga teks keterangan tetap rapi rata kiri */
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>
        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-clipboard-check"></i> AUDIT FISIK ASET</h4>
                <div class="small fw-medium">
                    <i class="bi bi-person-circle-fill"></i> <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                </div>
            </div>

            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-table"></i> <span>Data Hasil Audit Fisik</span>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="GET" class="d-flex gap-1 align-items-center">
                                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">
                                <input type="text" name="search" class="form-control form-control-sm bg-light text-dark border-0" placeholder="Cari auditor / alat..." style="border-radius: 6px; padding: 6px 12px;" value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-light btn-sm text-dark" style="border-radius: 6px;"><i class="bi bi-search"></i></button>
                            </form>
                            <a href="audit_tambah.php" class="btn btn-light btn-sm" style="border-radius: 6px;"><i class="bi bi-plus-lg"></i> Tambah</a>
                            <a href="audit_cetak.php?kategori=<?= $kategori_filter ?>&search=<?= urlencode($search) ?>" class="btn btn-light btn-sm" style="border-radius: 6px;"><i class="bi bi-file-earmark-pdf"></i> Cetak PDF</a>
                        </div>
                    </div>

                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'medis') ? 'active-medis' : '' ?>" href="?kategori=medis<?= $search ? '&search=' . urlencode($search) : '' ?>">
                                🏥 Aset Medis (Alkes)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'non-medis') ? 'active-nonmedis' : '' ?>" href="?kategori=non-medis<?= $search ? '&search=' . urlencode($search) : '' ?>">
                                🪑 Aset Non-Medis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($kategori_filter == 'semua') ? 'active-semua' : '' ?>" href="?kategori=semua<?= $search ? '&search=' . urlencode($search) : '' ?>">
                                📋 Semua Aset
                            </a>
                        </li>
                    </ul>

                    <div class="card-body p-0 pt-2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover text-center align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">No</th>
                                        <th>Nama Aset / Alat</th>
                                        <th>Kategori</th>
                                        <th>Tanggal Audit</th>
                                        <th>Auditor Pemeriksa</th>
                                        <th>Kondisi Fisik</th>
                                        <th style="min-width: 300px;">Keterangan Temuan</th>
                                        <th style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($query) == 0): ?>
                                        <tr>
                                            <td colspan="8" class="text-muted py-5 text-center"><i class="bi bi-inboxes text-secondary d-block mb-2" style="font-size: 2rem;"></i>Data audit untuk kategori ini belum tersedia.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = $offset + 1; ?>
                                        <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                            <tr>
                                                <td class="text-secondary"><?= $no++ ?></td>
                                                <td class="fw-bold text-dark text-start"><?= htmlspecialchars($row['nama_aset']) ?></td>

                                                <td>
                                                    <?php if (isset($row['kategori_aset']) && $row['kategori_aset'] == 'Medis'): ?>
                                                        <span class="badge-medis">Medis</span>
                                                    <?php else: ?>
                                                        <span class="badge-nonmedis">Non-Medis</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    $tgl = isset($row['tanggal_audit']) ? $row['tanggal_audit'] : (isset($row['tanggal']) ? $row['tanggal'] : '');
                                                    echo !empty($tgl) ? date('d-m-Y', strtotime($tgl)) : '-';
                                                    ?>
                                                </td>
                                                <td class="text-start ps-3"><?= htmlspecialchars($row['auditor']) ?></td>

                                                <td>
                                                    <?php
                                                    $kondisi = isset($row['kondisi_fisik']) ? $row['kondisi_fisik'] : (isset($row['kondisi']) ? $row['kondisi'] : 'Baik');
                                                    ?>
                                                    <span class="badge <?= $kondisi == 'Baik' ? 'bg-success' : ($kondisi == 'Rusak' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                                        <?= htmlspecialchars($kondisi) ?>
                                                    </span>
                                                </td>

                                                <td class="kolom-keterangan"><?= htmlspecialchars($row['keterangan']) ?></td>

                                                <td>
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        <a href="audit_edit.php?id=<?= $row['id_audit'] ?>" class="btn btn-warning btn-sm text-white" style="border-radius: 6px;" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="audit_hapus.php?id=<?= $row['id_audit'] ?>" class="btn btn-danger btn-sm" style="border-radius: 6px;" title="Hapus" onclick="return confirm('Hapus data audit ini?')">
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

                        <div class="card-footer bg-white d-flex justify-content-end py-3 border-top-0">
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" style="border-radius: 6px 0 0 6px;" href="?page=<?= $page - 1 ?>&kategori=<?= $kategori_filter ?>&search=<?= urlencode($search) ?>">Prev</a>
                                    </li>
                                    <li class="page-item disabled">
                                        <span class="page-link bg-light text-dark fw-bold"><?= $page ?> / <?= max(1, $total_page) ?></span>
                                    </li>
                                    <li class="page-item <?= ($page >= $total_page) ? 'disabled' : '' ?>">
                                        <a class="page-link" style="border-radius: 0 6px 6px 0;" href="?page=<?= $page + 1 ?>&kategori=<?= $kategori_filter ?>&search=<?= urlencode($search) ?>">Next</a>
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