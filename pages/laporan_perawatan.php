<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');
include(__DIR__ . '/../header.php');
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

    /* Status colors */
    .status-sedang-proses {
        background-color: #fff3cd;
        color: #856404;
        font-weight: 600;
        text-align: center;
        border-radius: 4px;
    }

    .status-belum-dimulai {
        background-color: #e2e3e5;
        color: #6c757d;
        font-weight: 600;
        text-align: center;
        border-radius: 4px;
    }

    .status-selesai {
        background-color: #d4edda;
        color: #155724;
        font-weight: 600;
        text-align: center;
        border-radius: 4px;
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Perawatan </h3>
</div>

<div class="content">
    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $statusFilter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = [];
    if ($search !== '') $where[] = "(nama_aset LIKE '%$search%' OR teknisi LIKE '%$search%')";
    if ($statusFilter !== '') $where[] = "status='$statusFilter'";
    if ($dari !== '') $where[] = "tanggal >= '$dari'";
    if ($sampai !== '') $where[] = "tanggal <= '$sampai'";
    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Statistik
    $statQ = mysqli_query($koneksi, "SELECT 
        COUNT(*) as total_all,
        SUM(status='Belum Dimulai') as total_belum,
        SUM(status='Sedang Proses') as total_proses,
        SUM(status='Selesai') as total_selesai
        FROM perawatan $whereSQL");
    $stat = mysqli_fetch_assoc($statQ);

    // Data perawatan
    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "SELECT * FROM perawatan $whereSQL ORDER BY tanggal DESC, id DESC LIMIT $offset,$perPage");
    ?>

    <!-- Filter -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET" action="">
            <input type="text" name="search" placeholder="Cari perawatan..." value="<?= htmlspecialchars($search) ?>">
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

    <!-- Statistik ringkas -->
    <div class="stats mb-3">
        <div class="stat">Total: <strong><?= intval($stat['total_all']) ?></strong></div>
        <div class="stat">Belum Dimulai: <strong><?= intval($stat['total_belum']) ?></strong></div>
        <div class="stat">Sedang Proses: <strong><?= intval($stat['total_proses']) ?></strong></div>
        <div class="stat">Selesai: <strong><?= intval($stat['total_selesai']) ?></strong></div>
    </div>

    <!-- Tabel laporan -->
    <div class="card">
        <div class="card-header">Data Perawatan / Pemeliharaan</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Nama Aset</th>
                        <th>Teknisi</th>
                        <th>Tanggal Perawatan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($dataQ) == 0) {
                        echo '<tr><td colspan="5" class="text-center">Tidak ada data perawatan ditemukan</td></tr>';
                    } else {
                        $no = $offset + 1;
                        while ($r = mysqli_fetch_assoc($dataQ)) {
                            $statusClass = '';
                            if (strtolower($r['status']) == 'sedang proses') $statusClass = 'status-sedang-proses';
                            elseif (strtolower($r['status']) == 'belum dimulai') $statusClass = 'status-belum-dimulai';
                            elseif (strtolower($r['status']) == 'selesai') $statusClass = 'status-selesai';

                            echo "<tr>
                                <td>{$no}</td>
                                <td>" . htmlspecialchars($r['nama_aset']) . "</td>
                                <td>" . htmlspecialchars($r['teknisi']) . "</td>
                                <td>" . htmlspecialchars($r['tanggal']) . "</td>
                                <td class='{$statusClass}'>" . htmlspecialchars($r['status']) . "</td>
                            </tr>";
                            $no++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php
    $totalPages = ceil($totalRow / $perPage);
    if ($totalPages > 1) {
        echo '<nav aria-label="Page navigation" style="margin-top:12px;"><ul class="pagination">';
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