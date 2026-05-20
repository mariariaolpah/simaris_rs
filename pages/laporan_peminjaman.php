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
    <h3 class="mb-0">Laporan Rekapitulasi Peminjaman Aset</h3>
</div>

<div class="content">

    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;

    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = [];

    if ($search !== '') {
        $where[] = "(peminjaman.nama_peminjam LIKE '%$search%' OR aset.nama_aset LIKE '%$search%' OR aset.lokasi LIKE '%$search%')";
    }
    if ($status !== '') {
        $where[] = "peminjaman.status_pinjam = '$status'";
    }
    if ($dari !== '') $where[] = "peminjaman.tanggal_pinjam >= '$dari'";
    if ($sampai !== '') $where[] = "peminjaman.tanggal_pinjam <= '$sampai'";

    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // ======================= STATISTIK =======================
    $statQ = mysqli_query($koneksi, "
        SELECT 
        COUNT(*) as total_all,
        SUM(status_pinjam='Dipinjam') as dipinjam,
        SUM(status_pinjam='Dikembalikan') as kembali
        FROM peminjaman
        JOIN aset ON peminjaman.id_aset = aset.id_aset
        $whereSQL
    ");
    $stat = mysqli_fetch_assoc($statQ);

    // ======================= DATA =======================
    $countQ = mysqli_query($koneksi, "
        SELECT COUNT(*) as total 
        FROM peminjaman
        JOIN aset ON peminjaman.id_aset = aset.id_aset
        $whereSQL
    ");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "
        SELECT peminjaman.*, aset.nama_aset, aset.kategori_aset, aset.lokasi
        FROM peminjaman
        JOIN aset ON peminjaman.id_aset = aset.id_aset
        $whereSQL
        ORDER BY peminjaman.id_pinjam DESC
        LIMIT $offset,$perPage
    ");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari peminjaman..." value="<?= htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="Dipinjam" <?= $status == 'Dipinjam' ? 'selected' : '' ?>>Dipinjam</option>
                <option value="Dikembalikan" <?= $status == 'Dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
            </select>
            <input type="date" name="dari" value="<?= $dari; ?>">
            <input type="date" name="sampai" value="<?= $sampai; ?>">
            <button class="btn btn-success btn-sm">🔍 Filter</button>
        </form>

        <div>
            <a href="export_peminjaman_excel.php?<?= http_build_query($_GET); ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="peminjaman_cetak.php?<?= http_build_query($_GET); ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <div class="stats mb-3">
        <div class="stat">Total: <strong><?= intval($stat['total_all']); ?></strong></div>
        <div class="stat text-warning">Sedang Dipinjam: <strong><?= intval($stat['dipinjam']); ?></strong></div>
        <div class="stat text-success">Telah Dikembalikan: <strong><?= intval($stat['kembali']); ?></strong></div>
    </div>

    <div class="card">
        <div class="card-header">Data Peminjaman Aset</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0 text-center align-middle">
                <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th class="text-start">Peminjam</th>
                        <th class="text-start">Nama Alat</th>
                        <th>Kategori</th>
                        <th>Lokasi Asal</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($dataQ) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">Tidak ada data peminjaman</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1;
                        while ($r = mysqli_fetch_assoc($dataQ)): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td class="text-start">
                                    <div class="fw-bold"><?= htmlspecialchars($r['nama_peminjam']); ?></div>
                                    <?php if (isset($r['sumber']) && $r['sumber'] == 'App User'): ?>
                                        <span class="badge bg-primary mt-1" style="font-size:0.65rem;">[ App User ]</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary mt-1" style="font-size:0.65rem;">[ Admin ]</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-start fw-bold"><?= htmlspecialchars($r['nama_aset']); ?></td>
                                <td><?= htmlspecialchars($r['kategori_aset']); ?></td>
                                <td><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($r['lokasi']); ?></td>
                                <td><?= !empty($r['tanggal_pinjam']) ? date('d/m/Y', strtotime($r['tanggal_pinjam'])) : '-'; ?></td>
                                <td><?= (!empty($r['tanggal_kembali']) && $r['tanggal_kembali'] != '0000-00-00') ? date('d/m/Y', strtotime($r['tanggal_kembali'])) : '-'; ?></td>
                                <td>
                                    <?php
                                    $bg = 'bg-secondary';
                                    if ($r['status_pinjam'] == 'Dipinjam') $bg = 'bg-warning text-dark';
                                    if ($r['status_pinjam'] == 'Dikembalikan') $bg = 'bg-success';
                                    ?>
                                    <span class="badge <?= $bg; ?>"><?= htmlspecialchars($r['status_pinjam']); ?></span>
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
            $url = 'laporan_peminjaman.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>