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
    <h3 class="mb-0">Laporan Data Aset Rumah Sakit</h3>
</div>

<div class="content">
    <?php
    // ambil parameter filter
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $kondisi = isset($_GET['kondisi']) ? mysqli_real_escape_string($koneksi, $_GET['kondisi']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = [];
    if ($search !== '') $where[] = "(nama_aset LIKE '%$search%' OR jenis LIKE '%$search%' OR tipe_aset LIKE '%$search%' OR lokasi LIKE '%$search%')";
    if ($kondisi !== '') $where[] = "kondisi = '$kondisi'";
    if ($dari !== '') $where[] = "tanggal_masuk >= '$dari'";
    if ($sampai !== '') $where[] = "tanggal_masuk <= '$sampai'";
    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // statistik kondisi
    $statQ = mysqli_query($koneksi, "SELECT 
        COUNT(*) as total_all,
        SUM(kondisi='Baik') as total_baik,
        SUM(kondisi='Rusak') as total_rusak,
        SUM(kondisi='Perlu Perawatan') as total_perawatan
        FROM aset $whereSQL");
    $stat = mysqli_fetch_assoc($statQ);

    // ambil data aset
    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM aset $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "SELECT * FROM aset $whereSQL ORDER BY id_aset DESC LIMIT $offset, $perPage");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET" action="">
            <input type="text" name="search" placeholder="Cari aset..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="kondisi">
                <option value="">Semua Kondisi</option>
                <option value="Baik" <?php if ($kondisi == 'Baik') echo 'selected'; ?>>Baik</option>
                <option value="Rusak" <?php if ($kondisi == 'Rusak') echo 'selected'; ?>>Rusak</option>
                <option value="Perlu Perawatan" <?php if ($kondisi == 'Perlu Perawatan') echo 'selected'; ?>>Perlu Perawatan</option>
            </select>
            <input type="date" name="dari" value="<?php echo $dari; ?>">
            <input type="date" name="sampai" value="<?php echo $sampai; ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="export_aset_excel.php?<?php echo http_build_query($_GET); ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_aset.php?<?php echo http_build_query($_GET); ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <!-- Statistik ringkas -->
    <div class="stats mb-3">
        <div class="stat">Total: <strong><?php echo intval($stat['total_all']); ?></strong></div>
        <div class="stat">Baik: <strong><?php echo intval($stat['total_baik']); ?></strong></div>
        <div class="stat">Rusak: <strong><?php echo intval($stat['total_rusak']); ?></strong></div>
        <div class="stat">Perlu Perawatan: <strong><?php echo intval($stat['total_perawatan']); ?></strong></div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-header">Data Aset Rumah Sakit</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Nama Aset</th>
                        <th>Jenis</th>
                        <th>Tipe Aset</th>
                        <th>Lokasi</th>
                        <th>Kondisi</th>
                        <th>Tanggal Masuk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($dataQ) == 0) {
                        echo '<tr><td colspan="7" class="text-center">Tidak ada data aset ditemukan</td></tr>';
                    } else {
                        $no = $offset + 1;
                        while ($r = mysqli_fetch_assoc($dataQ)) {
                            echo "<tr>
                                <td>{$no}</td>
                                <td>{$r['nama_aset']}</td>
                                <td>{$r['jenis']}</td>
                                <td>{$r['tipe_aset']}</td>
                                <td>{$r['lokasi']}</td>
                                <td>{$r['kondisi']}</td>
                                <td>{$r['tanggal_masuk']}</td>
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
            $url = 'laporan_aset.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>