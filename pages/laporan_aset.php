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

    .foto-thumbnail {
        width: 45px;
        height: 45px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s;
    }

    .foto-thumbnail:hover {
        transform: scale(1.1);
        cursor: pointer;
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Data Aset Rumah Sakit</h3>
</div>

<div class="content">
    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;

    $search   = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
    $dari     = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai   = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = [];
    if ($search !== '')   $where[] = "(nama_aset LIKE '%$search%' OR jenis LIKE '%$search%' OR tipe_aset LIKE '%$search%' OR lokasi LIKE '%$search%')";
    if ($kategori !== '') $where[] = "kategori_aset = '$kategori'";
    if ($dari !== '')     $where[] = "tanggal_masuk >= '$dari'";
    if ($sampai !== '')   $where[] = "tanggal_masuk <= '$sampai'";

    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $statQ = mysqli_query($koneksi, "
        SELECT 
            COUNT(*) as total_all,
            SUM(COALESCE(stok_tersedia, 0)) as total_tersedia,
            SUM(COALESCE(stok_perawatan, 0)) as total_perawatan,
            SUM(COALESCE(stok_rusak, 0)) as total_rusak
        FROM aset $whereSQL
    ");
    $stat = mysqli_fetch_assoc($statQ);

    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM aset $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "SELECT * FROM aset $whereSQL ORDER BY id_aset DESC LIMIT $offset, $perPage");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET" action="">
            <input type="text" name="search" placeholder="Cari aset/lokasi..." value="<?= htmlspecialchars($search); ?>">
            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Medis" <?= ($kategori == 'Medis') ? 'selected' : ''; ?>>Medis</option>
                <option value="Non-Medis" <?= ($kategori == 'Non-Medis') ? 'selected' : ''; ?>>Non-Medis</option>
            </select>
            <input type="date" name="dari" value="<?= $dari; ?>">
            <input type="date" name="sampai" value="<?= $sampai; ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="export_aset_excel.php?<?= http_build_query($_GET); ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_aset.php?<?= http_build_query($_GET); ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <div class="stats mb-3">
        <div class="stat">Total Item: <strong><?= intval($stat['total_all']); ?></strong></div>
        <div class="stat text-success">Total Tersedia: <strong><?= intval($stat['total_tersedia']); ?></strong></div>
        <div class="stat text-warning">Total Perawatan: <strong><?= intval($stat['total_perawatan']); ?></strong></div>
        <div class="stat text-danger">Total Rusak: <strong><?= intval($stat['total_rusak']); ?></strong></div>
    </div>

    <div class="card">
        <div class="card-header">Rekapitulasi Inventaris Utama</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th style="width:40px;">No</th>
                            <th class="text-start">Nama Aset</th>
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
                            <th>Dokumen / Foto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($dataQ) == 0): ?>
                            <tr>
                                <td colspan="13" class="text-center py-4">Data komponen aset tidak ditemukan atau kosong.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $offset + 1;
                            while ($a = mysqli_fetch_assoc($dataQ)): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td class="text-start fw-bold"><?= htmlspecialchars($a['nama_aset']); ?></td>
                                    <td>
                                        <?php if (isset($a['kategori_aset']) && $a['kategori_aset'] == 'Medis'): ?>
                                            <span class="badge bg-danger">Medis</span>
                                        <?php elseif (isset($a['kategori_aset']) && $a['kategori_aset'] == 'Non-Medis'): ?>
                                            <span class="badge bg-primary">Non-Medis</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['jenis']); ?></td>
                                    <td><?= htmlspecialchars($a['tipe_aset']); ?></td>
                                    <td class="text-start"><i class="bi bi-geo-alt text-danger me-1"></i><?= htmlspecialchars($a['lokasi']); ?></td>

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

                                    <td><?= htmlspecialchars($a['asal_usul']); ?></td>
                                    <td class="text-end fw-bold">Rp <?= number_format($a['harga'], 0, ',', '.') ?></td>
                                    <td class="text-success fw-bold"><?= (isset($a['umur_ekonomis']) && $a['umur_ekonomis'] > 0) ? $a['umur_ekonomis'] . ' Thn' : '-' ?></td>
                                    <td><?= formatTanggal($a['tanggal_masuk']); ?></td>

                                    <td>
                                        <?php
                                        $imgPath = __DIR__ . '/../assets/dokumen/' . $a['dokumen'];
                                        if (!empty($a['dokumen']) && file_exists($imgPath)):
                                        ?>
                                            <a href="../assets/dokumen/<?= htmlspecialchars($a['dokumen']) ?>" target="_blank" title="Klik untuk memperbesar">
                                                <img src="../assets/dokumen/<?= htmlspecialchars($a['dokumen']) ?>" class="foto-thumbnail" alt="Foto Aset">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.75rem;">Tidak Ada</span>
                                        <?php endif; ?>
                                    </td>
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
            $url = 'laporan_aset.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>