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
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Kerusakan Aset</h3>
</div>

<div class="content">
    <?php
    // ambil parameter filter
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $statusFilter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = [];
    if ($search !== '') $where[] = "(nama_aset LIKE '%$search%' OR keterangan LIKE '%$search%')";
    if ($statusFilter !== '') $where[] = "status = '$statusFilter'";
    if ($dari !== '') $where[] = "tanggal >= '$dari'";
    if ($sampai !== '') $where[] = "tanggal <= '$sampai'";
    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Statistik ringkas
    $statQ = mysqli_query($koneksi, "SELECT 
        COUNT(*) as total_all,
        SUM(status='Rusak') as total_rusak,
        SUM(status='Perlu Perawatan') as total_perawatan,
        SUM(status='Dalam Perbaikan') as total_diperbaiki,
        SUM(status='Selesai Diperbaiki') as total_selesai
        FROM kerusakan $whereSQL");
    $stat = mysqli_fetch_assoc($statQ);

    // Ambil jumlah total dan data
    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kerusakan $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "SELECT * FROM kerusakan $whereSQL ORDER BY tanggal DESC, id DESC LIMIT $offset, $perPage");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET" action="">
            <input type="text" name="search" placeholder="Cari kerusakan..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="Rusak" <?php if ($statusFilter == 'Rusak') echo 'selected'; ?>>Rusak</option>
                <option value="Perlu Perawatan" <?php if ($statusFilter == 'Perlu Perawatan') echo 'selected'; ?>>Perlu Perawatan</option>
                <option value="Dalam Perbaikan" <?php if ($statusFilter == 'Dalam Perbaikan') echo 'selected'; ?>>Dalam Perbaikan</option>
                <option value="Selesai Diperbaiki" <?php if ($statusFilter == 'Selesai Diperbaiki') echo 'selected'; ?>>Selesai Diperbaiki</option>
            </select>
            <input type="date" name="dari" value="<?php echo $dari; ?>">
            <input type="date" name="sampai" value="<?php echo $sampai; ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="export_kerusakan_excel.php?<?php echo http_build_query($_GET); ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_kerusakan.php?<?php echo http_build_query($_GET); ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <!-- Statistik ringkas -->
    <div class="stats mb-3">
        <div class="stat">Total: <strong><?php echo intval($stat['total_all']); ?></strong></div>
        <div class="stat">Rusak: <strong><?php echo intval($stat['total_rusak']); ?></strong></div>
        <div class="stat">Perlu Perawatan: <strong><?php echo intval($stat['total_perawatan']); ?></strong></div>
        <div class="stat">Dalam Perbaikan: <strong><?php echo intval($stat['total_diperbaiki']); ?></strong></div>
        <div class="stat">Selesai Diperbaiki: <strong><?php echo intval($stat['total_selesai']); ?></strong></div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-header">Data Kerusakan Aset</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Nama Aset</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($dataQ) == 0) {
                        echo '<tr><td colspan="5" class="text-center">Tidak ada data kerusakan ditemukan</td></tr>';
                    } else {
                        $no = $offset + 1;
                        while ($r = mysqli_fetch_assoc($dataQ)) {
                            echo "<tr>
                                <td>{$no}</td>
                                <td>{$r['nama_aset']}</td>
                                <td>{$r['status']}</td>
                                <td>{$r['tanggal']}</td>
                                <td>{$r['keterangan']}</td>
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
            $url = 'laporan_kerusakan.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>