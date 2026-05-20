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

    .card-header {
        background: linear-gradient(90deg, #2c7a7b, #1cc88a);
        color: #fff;
        font-weight: 600;
    }

    .stats {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
    }

    .stat {
        background: #fff;
        padding: 10px 14px;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
    }

    .status-sedang-proses {
        background-color: #fff3cd;
        color: #856404;
        font-weight: 600;
        border-radius: 4px;
    }

    .status-belum-dimulai {
        background-color: #e2e3e5;
        color: #6c757d;
        font-weight: 600;
        border-radius: 4px;
    }

    .status-selesai {
        background-color: #d4edda;
        color: #155724;
        font-weight: 600;
        border-radius: 4px;
    }

    .anim-blink {
        animation: blinker 1.5s linear infinite;
    }

    @keyframes blinker {
        50% {
            opacity: 0.3;
        }
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Jadwal Perawatan Aset</h3>
</div>

<div class="content">
    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $statusFilter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
    $kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = [];
    if ($search !== '') $where[] = "(perawatan.nama_aset LIKE '%$search%' OR perawatan.teknisi LIKE '%$search%' OR aset.lokasi LIKE '%$search%')";
    if ($statusFilter !== '') $where[] = "perawatan.status='$statusFilter'";
    if ($kategoriFilter !== '') $where[] = "aset.kategori_aset='$kategoriFilter'";
    if ($dari !== '') $where[] = "perawatan.tanggal >= '$dari'";
    if ($sampai !== '') $where[] = "perawatan.tanggal <= '$sampai'";
    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $statQ = mysqli_query($koneksi, "
        SELECT 
        COUNT(*) as total_all,
        SUM(perawatan.status='Belum Dimulai') as total_belum,
        SUM(perawatan.status='Sedang Proses') as total_proses,
        SUM(perawatan.status='Selesai') as total_selesai
        FROM perawatan 
        LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        $whereSQL
    ");
    $stat = mysqli_fetch_assoc($statQ);

    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "
        SELECT perawatan.*, aset.lokasi, aset.kategori_aset 
        FROM perawatan 
        LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        $whereSQL 
        ORDER BY perawatan.tanggal DESC, perawatan.id DESC LIMIT $offset,$perPage
    ");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET" action="">
            <input type="text" name="search" placeholder="Cari perawatan/lokasi..." value="<?= htmlspecialchars($search) ?>">

            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Medis" <?= $kategoriFilter == 'Medis' ? 'selected' : '' ?>>Medis</option>
                <option value="Non-Medis" <?= $kategoriFilter == 'Non-Medis' ? 'selected' : '' ?>>Non-Medis</option>
            </select>

            <select name="status">
                <option value="">Semua Status</option>
                <option value="Belum Dimulai" <?= $statusFilter == 'Belum Dimulai' ? 'selected' : '' ?>>Belum Dimulai</option>
                <option value="Sedang Proses" <?= $statusFilter == 'Sedang Proses' ? 'selected' : '' ?>>Sedang Proses</option>
                <option value="Selesai" <?= $statusFilter == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
            </select>
            <input type="date" name="dari" value="<?= $dari ?>">
            <input type="date" name="sampai" value="<?= $sampai ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="export_perawatan_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_perawatan.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <div class="stats mb-3">
        <div class="stat">Total: <strong><?= intval($stat['total_all']) ?></strong></div>
        <div class="stat">Belum Dimulai: <strong><?= intval($stat['total_belum']) ?></strong></div>
        <div class="stat">Sedang Proses: <strong><?= intval($stat['total_proses']) ?></strong></div>
        <div class="stat">Selesai: <strong><?= intval($stat['total_selesai']) ?></strong></div>
    </div>

    <div class="card">
        <div class="card-header">Data Perawatan / Pemeliharaan</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0 text-center align-middle">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th class="text-start">Nama Aset</th>
                        <th>Kategori</th>
                        <th>Lokasi Ruang</th>
                        <th class="text-start">Teknisi Bertugas</th>
                        <th>Tgl Perawatan</th>
                        <th>Jadwal Kalibrasi Berikutnya</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($dataQ) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">Tidak ada data perawatan ditemukan</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1;
                        while ($r = mysqli_fetch_assoc($dataQ)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="text-start fw-bold"><?= htmlspecialchars($r['nama_aset']); ?></td>
                                <td>
                                    <?php if (($r['kategori_aset'] ?? '') == 'Medis'): ?>
                                        <span class="badge bg-danger">Medis</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Non-Medis</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($r['lokasi'] ?? '-'); ?></td>
                                <td class="text-start"><?= htmlspecialchars($r['teknisi']); ?></td>
                                <td><?= formatTanggal($r['tanggal']); ?></td>

                                <td>
                                    <?php
                                    $tgl_berikutnya = $r['tanggal_kalibrasi_berikutnya'] ?? '';
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
                                    <?php
                                    $statusClass = '';
                                    if (strtolower($r['status']) == 'sedang proses') $statusClass = 'status-sedang-proses';
                                    elseif (strtolower($r['status']) == 'belum dimulai') $statusClass = 'status-belum-dimulai';
                                    elseif (strtolower($r['status']) == 'selesai') $statusClass = 'status-selesai';
                                    ?>
                                    <div class="<?= $statusClass ?> py-1"><?= htmlspecialchars($r['status']); ?></div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    $totalPages = ceil($totalRow / $perPage);
    if ($totalPages > 1) {
        echo '<nav style="margin-top:12px;"><ul class="pagination">';
        $queryParams = $_GET;
        for ($p = 1; $p <= $totalPages; $p++) {
            $queryParams['page'] = $p;
            $url = 'laporan_perawatan.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>