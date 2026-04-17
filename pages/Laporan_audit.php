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
    <h3 class="mb-0">Laporan Audit Fisik</h3>
</div>

<div class="content">
    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;

    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = [];

    if ($search !== '') {
        $where[] = "(aset.nama_aset LIKE '%$search%' OR audit_fisik.keterangan LIKE '%$search%' OR audit_fisik.auditor LIKE '%$search%')";
    }
    if ($dari !== '') $where[] = "tanggal_audit >= '$dari'";
    if ($sampai !== '') $where[] = "tanggal_audit <= '$sampai'";

    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // =======================
    // STATISTIK
    // =======================
    $statQ = mysqli_query($koneksi, "
    SELECT 
    COUNT(*) as total_all,
    SUM(kondisi_fisik='Baik') as total_baik,
    SUM(kondisi_fisik='Rusak') as total_rusak,
    SUM(kondisi_fisik='Perlu Perbaikan') as total_perbaikan
    FROM audit_fisik
    JOIN aset ON audit_fisik.id_aset = aset.id_aset
    $whereSQL
");
    $stat = mysqli_fetch_assoc($statQ);

    // =======================
    // DATA
    // =======================
    $countQ = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM audit_fisik
    JOIN aset ON audit_fisik.id_aset = aset.id_aset
    $whereSQL
");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];

    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "
    SELECT audit_fisik.*, aset.nama_aset, aset.lokasi
    FROM audit_fisik
    JOIN aset ON audit_fisik.id_aset = aset.id_aset
    $whereSQL
    ORDER BY audit_fisik.id_audit DESC
    LIMIT $offset,$perPage
");
    ?>

    <!-- FILTER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari audit..." value="<?= htmlspecialchars($search); ?>">
            <input type="date" name="dari" value="<?= $dari; ?>">
            <input type="date" name="sampai" value="<?= $sampai; ?>">
            <button class="btn btn-success btn-sm">🔍 Filter</button>
        </form>

        <div>
            <a href="export_audit_excel.php?<?= http_build_query($_GET); ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_audit.php?<?= http_build_query($_GET); ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="stats mb-3">
        <div class="stat">Total: <strong><?= intval($stat['total_all']); ?></strong></div>
        <div class="stat">Baik: <strong><?= intval($stat['total_baik']); ?></strong></div>
        <div class="stat">Rusak: <strong><?= intval($stat['total_rusak']); ?></strong></div>
        <div class="stat">Perlu Perbaikan: <strong><?= intval($stat['total_perbaikan']); ?></strong></div>
    </div>

    <!-- TABEL -->
    <div class="card">
        <div class="card-header">Data Audit Fisik</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Aset</th>
                        <th>Lokasi</th>
                        <th>Tanggal Audit</th>
                        <th>Kondisi</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($dataQ) == 0) {
                        echo '<tr><td colspan="6" class="text-center">Tidak ada data audit ditemukan</td></tr>';
                    } else {
                        $no = $offset + 1;
                        while ($r = mysqli_fetch_assoc($dataQ)) {
                            echo "<tr>
                            <td>{$no}</td>
                            <td>{$r['nama_aset']}</td>
                            <td>{$r['lokasi']}</td>
                            <td>{$r['tanggal_audit']}</td>
                            <td>{$r['kondisi_fisik']}</td>
                            <td>{$r['keterangan']} (Oleh: {$r['auditor']})</td>
                        </tr>";
                            $no++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGINATION -->
    <?php
    $totalPages = ceil($totalRow / $perPage);
    if ($totalPages > 1) {
        echo '<nav style="margin-top:12px;"><ul class="pagination">';
        $queryParams = $_GET;
        for ($p = 1; $p <= $totalPages; $p++) {
            $queryParams['page'] = $p;
            $url = 'laporan_audit.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>

</div>

<?php include(__DIR__ . '/../footer.php'); ?>