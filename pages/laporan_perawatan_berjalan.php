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

    /* Highlight warna status */
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
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Perawatan Berjalan</h3>
</div>

<div class="content">
    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $teknisi = isset($_GET['teknisi']) ? mysqli_real_escape_string($koneksi, $_GET['teknisi']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = ["status IN ('Belum Dimulai','Sedang Proses')"]; // filter hanya perawatan berjalan
    if ($search !== '') $where[] = "nama_aset LIKE '%$search%'";
    if ($teknisi !== '') $where[] = "teknisi LIKE '%$teknisi%'";
    if ($dari !== '') $where[] = "tanggal >= '$dari'";
    if ($sampai !== '') $where[] = "tanggal <= '$sampai'";
    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Ambil total data perawatan berjalan
    $totalQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan $whereSQL");
    $totalRow = mysqli_fetch_assoc($totalQ)['total'];

    // Ambil data perawatan berjalan
    $dataQ = mysqli_query($koneksi, "
    SELECT id, nama_aset, teknisi, tanggal, status
    FROM perawatan
    $whereSQL
    ORDER BY tanggal DESC, nama_aset ASC, id DESC
    LIMIT $offset, $perPage
");
    ?>

    <!-- Filter -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari perawatan..." value="<?= htmlspecialchars($search) ?>">
            <input type="text" name="teknisi" placeholder="Nama teknisi" value="<?= htmlspecialchars($teknisi) ?>">
            <input type="date" name="dari" value="<?= $dari ?>">
            <input type="date" name="sampai" value="<?= $sampai ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="export_perawatan_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_perawatan_berjalan.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <!-- Statistik -->
    <?php
    // Ambil jumlah Perawatan Berjalan sesuai filter yang sama dengan tabel
    $totalPerawatanBerjalanQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM perawatan $whereSQL");
    $totalPerawatanBerjalan = mysqli_fetch_assoc($totalPerawatanBerjalanQ)['total'];
    ?>
    <div class="stats mb-3">
        <div class="stat">Total Perawatan Berjalan: <strong><?= intval($totalPerawatanBerjalan) ?></strong></div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-header">Data Perawatan Aset</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Nama Aset</th>
                        <th>Teknisi</th>
                        <th>Tanggal Perawatan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($dataQ) == 0) {
                        echo '<tr><td colspan="5" class="text-center">Belum ada perawatan berjalan</td></tr>';
                    } else {
                        $no = $offset + 1;
                        while ($r = mysqli_fetch_assoc($dataQ)) {
                            $statusClass = '';
                            if (strtolower($r['status']) == 'sedang proses') $statusClass = 'status-sedang-proses';
                            elseif (strtolower($r['status']) == 'belum dimulai') $statusClass = 'status-belum-dimulai';

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
            $url = 'laporan_perawatan_berjalan.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>