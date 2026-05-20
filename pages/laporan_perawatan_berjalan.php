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
    <h3 class="mb-0">Laporan Perawatan Berjalan</h3>
</div>

<div class="content">
    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $teknisi = isset($_GET['teknisi']) ? mysqli_real_escape_string($koneksi, $_GET['teknisi']) : '';
    $kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = ["perawatan.status IN ('Belum Dimulai','Sedang Proses')"];
    if ($search !== '') $where[] = "(perawatan.nama_aset LIKE '%$search%' OR aset.lokasi LIKE '%$search%')";
    if ($teknisi !== '') $where[] = "perawatan.teknisi LIKE '%$teknisi%'";
    if ($kategoriFilter !== '') $where[] = "aset.kategori_aset = '$kategoriFilter'";
    if ($dari !== '') $where[] = "perawatan.tanggal >= '$dari'";
    if ($sampai !== '') $where[] = "perawatan.tanggal <= '$sampai'";

    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $totalQ = mysqli_query($koneksi, "
        SELECT COUNT(*) as total 
        FROM perawatan 
        LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        $whereSQL
    ");
    $totalRow = mysqli_fetch_assoc($totalQ)['total'];

    $dataQ = mysqli_query($koneksi, "
        SELECT perawatan.*, aset.lokasi, aset.kategori_aset
        FROM perawatan
        LEFT JOIN aset ON perawatan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        $whereSQL
        ORDER BY perawatan.tanggal DESC, perawatan.nama_aset ASC, perawatan.id DESC
        LIMIT $offset, $perPage
    ");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari aset/lokasi..." value="<?= htmlspecialchars($search) ?>">
            <input type="text" name="teknisi" placeholder="Nama teknisi" value="<?= htmlspecialchars($teknisi) ?>">

            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Medis" <?= $kategoriFilter == 'Medis' ? 'selected' : '' ?>>Medis</option>
                <option value="Non-Medis" <?= $kategoriFilter == 'Non-Medis' ? 'selected' : '' ?>>Non-Medis</option>
            </select>

            <input type="date" name="dari" value="<?= $dari ?>">
            <input type="date" name="sampai" value="<?= $sampai ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="export_perawatan_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_perawatan_berjalan.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <div class="stats mb-3">
        <div class="stat">Total Perawatan Berjalan: <strong><?= intval($totalRow) ?></strong></div>
    </div>

    <div class="card">
        <div class="card-header">Data Perawatan Aset Berjalan</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th style="width:60px;">No</th>
                            <th class="text-start">Nama Aset</th>
                            <th>Kategori</th>
                            <th>Lokasi Ruang</th>
                            <th>Teknisi Bertugas</th>
                            <th>Tanggal Perawatan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($dataQ) == 0) {
                            echo '<tr><td colspan="7" class="text-center py-4">Belum ada data perawatan berjalan ditemukan</td></tr>';
                        } else {
                            $no = $offset + 1;
                            while ($r = mysqli_fetch_assoc($dataQ)) {

                                // ================== SINKRONISASI LOGIKA STATUS ================== //
                                $status_db = $r['status'] ?? '';
                                if ($status_db == 'Sedang Proses') {
                                    $tampil_status = '<span class="badge bg-warning text-dark">Proses</span>';
                                } else {
                                    $tampil_status = '<span class="badge bg-secondary">Belum Dimulai</span>';
                                }

                                $nama_aset = htmlspecialchars($r['nama_aset'] ?? '-');
                                $kategori  = htmlspecialchars($r['kategori_aset'] ?? '-');
                                $lokasi    = htmlspecialchars($r['lokasi'] ?? '-');
                                $teknisi   = htmlspecialchars($r['teknisi'] ?? '-');
                                $tanggal   = htmlspecialchars($r['tanggal'] ?? '-');

                                $badgeKategori = ($kategori == 'Medis') ? '<span class="badge bg-danger">Medis</span>' : '<span class="badge bg-primary">Non-Medis</span>';
                                if ($kategori == '-') $badgeKategori = '-';

                                echo "<tr>
                                    <td>{$no}</td>
                                    <td class='text-start fw-bold'>{$nama_aset}</td>
                                    <td>{$badgeKategori}</td>
                                    <td><i class='bi bi-geo-alt text-danger me-1'></i>{$lokasi}</td>
                                    <td>{$teknisi}</td>
                                    <td>" . date('d-m-Y', strtotime($tanggal)) . "</td>
                                    <td>{$tampil_status}</td> </tr>";
                                $no++;
                            }
                        }
                        ?>
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
            $url = 'laporan_perawatan_berjalan.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>