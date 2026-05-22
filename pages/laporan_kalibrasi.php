<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}
include(__DIR__ . '/../config/koneksi.php');
include(__DIR__ . '/../header.php');

function formatTanggal($tanggal)
{
    if (!$tanggal || $tanggal == '0000-00-00') return '-';
    return date('d-m-Y', strtotime($tanggal));
}
?>

<style>
    .filter-form input,
    .filter-form select {
        border-radius: 8px;
        padding: 6px 10px;
        border: 1px solid #ccc;
    }

    .card-header-custom {
        background: linear-gradient(90deg, #2c7a7b, #1cc88a);
        color: #fff;
        font-weight: 600;
    }

    .stats {
        display: flex;
        gap: 12px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .stat {
        background: #fff;
        padding: 12px 18px;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
        font-size: 0.95rem;
        border-left: 4px solid #ccc;
    }

    .stat-total {
        border-left-color: #0d6efd;
    }

    .stat-aman {
        border-left-color: #198754;
    }

    .stat-warning {
        border-left-color: #ffc107;
    }

    .stat-danger {
        border-left-color: #dc3545;
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Jadwal Kalibrasi Alat Medis & Aset</h3>
</div>

<div class="content">
    <?php
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
    $statusFilter = isset($_GET['status_kalibrasi']) ? mysqli_real_escape_string($koneksi, $_GET['status_kalibrasi']) : '';

    $where = ["perawatan.tanggal_kalibrasi_berikutnya IS NOT NULL AND perawatan.tanggal_kalibrasi_berikutnya >= '2000-01-01'"];

    if ($search !== '') {
        $where[] = "(perawatan.nama_aset LIKE '%$search%' OR aset.lokasi LIKE '%$search%' OR aset.jenis LIKE '%$search%')";
    }
    if ($kategoriFilter !== '') {
        $where[] = "aset.kategori_aset = '$kategoriFilter'";
    }
    if ($statusFilter === 'aman') {
        $where[] = "DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) > 7";
    } elseif ($statusFilter === 'mendekati') {
        $where[] = "DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) BETWEEN 0 AND 7";
    } elseif ($statusFilter === 'terlewat') {
        $where[] = "DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) < 0";
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    $dataQ = mysqli_query($koneksi, "
        SELECT perawatan.*, aset.lokasi, aset.kategori_aset, aset.jenis, aset.tipe_aset 
        FROM perawatan 
        LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        $whereSQL 
        ORDER BY perawatan.tanggal_kalibrasi_berikutnya ASC
    ");

    $statQ = mysqli_query($koneksi, "
        SELECT 
            COUNT(*) as total_all,
            SUM(DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) > 7) as total_aman,
            SUM(DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) BETWEEN 0 AND 7) as total_mendekati,
            SUM(DATEDIFF(perawatan.tanggal_kalibrasi_berikutnya, CURDATE()) < 0) as total_terlewat
        FROM perawatan 
        LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        WHERE perawatan.tanggal_kalibrasi_berikutnya IS NOT NULL AND perawatan.tanggal_kalibrasi_berikutnya >= '2000-01-01'
    ");
    $stat = mysqli_fetch_assoc($statQ);
    ?>

    <div class="stats">
        <div class="stat stat-total">Total Terjadwal: <strong><?= intval($stat['total_all']); ?> Aset</strong></div>
        <div class="stat stat-aman text-success">Status Aman: <strong><?= intval($stat['total_aman']); ?></strong></div>
        <div class="stat stat-warning text-warning">Mendekati H-7: <strong><?= intval($stat['total_mendekati']); ?></strong></div>
        <div class="stat stat-danger text-danger">Terlewat Kalibrasi: <strong><?= intval($stat['total_terlewat']); ?></strong></div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <form class="d-flex gap-2 filter-form align-items-center" method="GET" action="">
            <input type="text" name="search" class="form-control" style="width: 220px;" placeholder="Cari nama aset / lokasi..." value="<?= htmlspecialchars($search) ?>">

            <select name="kategori" class="form-select" style="width: 150px;">
                <option value="">Kategori Aset</option>
                <option value="Medis" <?= $kategoriFilter == 'Medis' ? 'selected' : '' ?>>Medis</option>
                <option value="Non-Medis" <?= $kategoriFilter == 'Non-Medis' ? 'selected' : '' ?>>Non-Medis</option>
            </select>

            <select name="status_kalibrasi" class="form-select" style="width: 160px;">
                <option value="">Status Kalibrasi</option>
                <option value="aman" <?= $statusFilter == 'aman' ? 'selected' : '' ?>>Aman (> 7 Hari)</option>
                <option value="mendekati" <?= $statusFilter == 'mendekati' ? 'selected' : '' ?>>Mendekati H-7</option>
                <option value="terlewat" <?= $statusFilter == 'terlewat' ? 'selected' : '' ?>>Sudah Terlewat</option>
            </select>

            <button class="btn btn-success btn-sm px-3 py-2" type="submit">🔍 Filter</button>
            <?php if (!empty($_GET)): ?>
                <a href="laporan_kalibrasi.php" class="btn btn-outline-secondary btn-sm px-3 py-2">Reset</a>
            <?php endif; ?>
        </form>

        <div class="mt-2 mt-md-0">
            <a href="cetak_laporan_kalibrasi.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm px-3 py-2" target="_blank">
                <i class="bi bi-printer"></i> Cetak PDF
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header card-header-custom">Daftar Aset Wajib Kalibrasi</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0 text-center align-middle">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th class="text-start">Nama Aset</th>
                        <th>Merk / Tipe</th>
                        <th>Lokasi Ruang</th>
                        <th>Kategori</th>
                        <th>Tgl Terakhir Rawat</th>
                        <th>Jadwal Kalibrasi</th>
                        <th>Status / Sisa Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$dataQ || mysqli_num_rows($dataQ) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">Data kalibrasi tidak ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1;
                        while ($r = mysqli_fetch_assoc($dataQ)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="text-start fw-bold"><?= htmlspecialchars($r['nama_aset']); ?></td>

                                <td><?= htmlspecialchars($r['jenis'] ?? '-') . " / " . htmlspecialchars($r['tipe_aset'] ?? '-'); ?></td>

                                <td><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($r['lokasi'] ?? '-'); ?></td>

                                <td>
                                    <?php if (($r['kategori_aset'] ?? '') == 'Medis'): ?>
                                        <span class="badge bg-danger">Medis</span>
                                    <?php elseif (($r['kategori_aset'] ?? '') == 'Non-Medis'): ?>
                                        <span class="badge bg-primary">Non-Medis</span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td><?= formatTanggal($r['tanggal']); ?></td>
                                <td class="fw-bold text-success"><?= formatTanggal($r['tanggal_kalibrasi_berikutnya']); ?></td>

                                <td>
                                    <?php
                                    $tgl_berikutnya = $r['tanggal_kalibrasi_berikutnya'];
                                    $selisih_detik = strtotime($tgl_berikutnya) - strtotime('today');
                                    $selisih_hari = floor($selisih_detik / (60 * 60 * 24));

                                    if ($selisih_hari <= 7 && $selisih_hari >= 0) {
                                        echo "<span class='badge bg-warning text-dark'>Mendekati H-$selisih_hari</span>";
                                    } elseif ($selisih_hari < 0) {
                                        echo "<span class='badge bg-danger'>Terlewat " . abs($selisih_hari) . " Hari</span>";
                                    } else {
                                        echo "<span class='badge bg-success'>Aman</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include(__DIR__ . '/../footer.php'); ?>