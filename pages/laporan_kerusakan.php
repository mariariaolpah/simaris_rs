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

    .table th {
        font-size: 0.85rem;
    }

    .table td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Kerusakan Aset</h3>
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
    if ($search !== '') $where[] = "(kerusakan.nama_aset LIKE '%$search%' OR kerusakan.keterangan LIKE '%$search%' OR kerusakan.pelapor LIKE '%$search%' OR kerusakan.teknisi LIKE '%$search%' OR aset.lokasi LIKE '%$search%')";
    if ($statusFilter !== '') $where[] = "kerusakan.status = '$statusFilter'";
    if ($kategoriFilter !== '') $where[] = "aset.kategori_aset = '$kategoriFilter'";
    if ($dari !== '') $where[] = "kerusakan.tanggal >= '$dari'";
    if ($sampai !== '') $where[] = "kerusakan.tanggal <= '$sampai'";

    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Hitung statistik dengan filter kategori yang ikut sinkron
    $statQ = mysqli_query($koneksi, "
        SELECT 
            COUNT(*) as total_all,
            SUM(kerusakan.status='Rusak') as total_rusak,
            SUM(kerusakan.status='Perlu Perawatan') as total_perawatan,
            SUM(kerusakan.status='Dalam Perbaikan') as total_diperbaiki,
            SUM(kerusakan.status='Selesai Diperbaiki') as total_selesai
        FROM kerusakan 
        LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        $whereSQL
    ");
    $stat = mysqli_fetch_assoc($statQ);

    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kerusakan LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    // Perbaikan: Tambahkan aset.stok_tersedia dan aset.stok_rusak ke dalam query
    $dataQ = mysqli_query($koneksi, "
        SELECT kerusakan.*, aset.lokasi, aset.kategori_aset, aset.stok_tersedia, aset.stok_rusak 
        FROM kerusakan 
        LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        $whereSQL 
        ORDER BY kerusakan.tanggal DESC, kerusakan.id DESC 
        LIMIT $offset, $perPage
    ");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET" action="">
            <input type="text" name="search" placeholder="Cari kerusakan/teknisi..." value="<?= htmlspecialchars($search); ?>">

            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Medis" <?= ($kategoriFilter == 'Medis') ? 'selected' : ''; ?>>Medis</option>
                <option value="Non-Medis" <?= ($kategoriFilter == 'Non-Medis') ? 'selected' : ''; ?>>Non-Medis</option>
            </select>

            <select name="status">
                <option value="">Semua Status</option>
                <option value="Rusak" <?= ($statusFilter == 'Rusak') ? 'selected' : ''; ?>>Rusak</option>
                <option value="Perlu Perawatan" <?= ($statusFilter == 'Perlu Perawatan') ? 'selected' : ''; ?>>Perlu Perawatan</option>
                <option value="Dalam Perbaikan" <?= ($statusFilter == 'Dalam Perbaikan') ? 'selected' : ''; ?>>Dalam Perbaikan</option>
                <option value="Selesai Diperbaiki" <?= ($statusFilter == 'Selesai Diperbaiki') ? 'selected' : ''; ?>>Selesai Diperbaiki</option>
            </select>
            <input type="date" name="dari" value="<?= $dari; ?>" title="Dari Tanggal">
            <input type="date" name="sampai" value="<?= $sampai; ?>" title="Sampai Tanggal">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="export_kerusakan_excel.php?<?= http_build_query($_GET); ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_kerusakan.php?<?= http_build_query($_GET); ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <div class="stats mb-3">
        <div class="stat">Total: <strong><?= intval($stat['total_all']); ?></strong></div>
        <div class="stat text-danger">Rusak: <strong><?= intval($stat['total_rusak']); ?></strong></div>
        <div class="stat text-warning">Perawatan: <strong><?= intval($stat['total_perawatan']); ?></strong></div>
        <div class="stat text-primary">Diperbaiki: <strong><?= intval($stat['total_diperbaiki']); ?></strong></div>
        <div class="stat text-success">Selesai: <strong><?= intval($stat['total_selesai']); ?></strong></div>
    </div>

    <div class="card">
        <div class="card-header">Data Rekapitulasi Kerusakan</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th class="text-start">Nama Aset & Info Stok</th>
                            <th>Kategori</th>
                            <th>Lokasi Ruangan</th>
                            <th>Pelapor</th>
                            <th>Teknisi</th>
                            <th>Tanggal Lapor</th>
                            <th>Status</th>
                            <th class="text-start">Rincian Kerusakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($dataQ) == 0): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">Tidak ada data kerusakan ditemukan</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $offset + 1;
                            while ($r = mysqli_fetch_assoc($dataQ)): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="text-start">
                                        <span class="fw-bold d-block mb-1 text-dark">
                                            <?= htmlspecialchars($r['nama_aset']) ?>
                                        </span>
                                        <div class="d-flex gap-1" style="font-size: 0.75rem;">
                                            <span class="badge bg-danger rounded-pill fw-normal" title="Total stok yang tercatat rusak">
                                                Rusak: <?= isset($r['stok_rusak']) ? htmlspecialchars($r['stok_rusak']) : '0' ?>
                                            </span>
                                            <span class="badge bg-success rounded-pill fw-normal" title="Sisa stok yang masih bisa dipakai/dipinjam">
                                                Tersedia: <?= isset($r['stok_tersedia']) ? htmlspecialchars($r['stok_tersedia']) : '0' ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (($r['kategori_aset'] ?? '') == 'Medis'): ?>
                                            <span class="badge bg-danger">Medis</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Non-Medis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($r['lokasi'] ?? '-'); ?></td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['pelapor'] ?? '-'); ?></div>
                                        <?php if (isset($r['sumber']) && $r['sumber'] == 'App User'): ?>
                                            <span class="badge bg-primary mt-1" style="font-size:0.65rem;">[ App User ]</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mt-1" style="font-size:0.65rem;">[ Admin ]</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-medium text-dark"><?= htmlspecialchars($r['teknisi'] ?? '-'); ?></td>
                                    <td><?= formatTanggal($r['tanggal']); ?></td>
                                    <td>
                                        <?php
                                        $bg = 'bg-danger';
                                        if ($r['status'] == 'Dalam Perbaikan' || $r['status'] == 'Perlu Perawatan') $bg = 'bg-warning text-dark';
                                        if ($r['status'] == 'Selesai Diperbaiki') $bg = 'bg-success';
                                        ?>
                                        <span class="badge <?= $bg; ?>"><?= $r['status']; ?></span>
                                    </td>
                                    <td class="text-start" style="font-size: 0.9rem;"><?= htmlspecialchars($r['keterangan']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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