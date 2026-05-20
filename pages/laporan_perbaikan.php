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

    .status-sedang {
        background-color: #fff3cd;
        color: #856404;
        font-weight: 600;
        border-radius: 6px;
        padding: 3px 8px;
    }

    .status-selesai {
        background-color: #d4edda;
        color: #155724;
        font-weight: 600;
        border-radius: 6px;
        padding: 3px 8px;
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Perbaikan Aset</h3>
</div>

<div class="content">
    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';

    $where = ["(kerusakan.status = 'Dalam Perbaikan' OR kerusakan.status = 'Selesai Diperbaiki')"];
    if ($search !== '') $where[] = "(kerusakan.nama_aset LIKE '%$search%' OR kerusakan.keterangan LIKE '%$search%' OR kerusakan.teknisi LIKE '%$search%' OR kerusakan.pelapor LIKE '%$search%' OR aset.lokasi LIKE '%$search%')";
    if ($kategoriFilter !== '') $where[] = "aset.kategori_aset = '$kategoriFilter'";

    $whereSQL = implode(" AND ", $where);

    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM kerusakan LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci WHERE $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    // Perubahan Query: Menambahkan aset.lokasi
    $dataQ = mysqli_query($koneksi, "
        SELECT kerusakan.*, aset.kategori_aset, aset.lokasi 
        FROM kerusakan 
        LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset COLLATE utf8mb4_general_ci
        WHERE $whereSQL 
        ORDER BY kerusakan.tanggal DESC LIMIT $offset, $perPage
    ");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari aset/ruang/teknisi..." value="<?= htmlspecialchars($search) ?>">
            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Medis" <?= $kategoriFilter == 'Medis' ? 'selected' : '' ?>>Medis</option>
                <option value="Non-Medis" <?= $kategoriFilter == 'Non-Medis' ? 'selected' : '' ?>>Non-Medis</option>
            </select>
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>
        <a href="cetak_laporan_perbaikan.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
    </div>

    <div class="card">
        <div class="card-header">Data Perbaikan Aset</div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover m-0 align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px;">No</th>
                        <th class="text-start">Nama Aset</th>
                        <th>Lokasi Ruangan</th>
                        <th>Kategori</th>
                        <th>Pelapor</th>
                        <th>Teknisi</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th class="text-start">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($dataQ) == 0) {
                        echo "<tr><td colspan='9' class='text-center py-4'>Belum ada data perbaikan</td></tr>";
                    } else {
                        $no = $offset + 1;
                        while ($row = mysqli_fetch_assoc($dataQ)) {
                            $statusClass = ($row['status'] == 'Selesai Diperbaiki') ? 'status-selesai' : 'status-sedang';

                            $nama_aset  = htmlspecialchars($row['nama_aset'] ?? '-');
                            $lokasi     = htmlspecialchars($row['lokasi'] ?? '-');
                            $pelapor    = htmlspecialchars($row['pelapor'] ?? '-');
                            $teknisi    = htmlspecialchars($row['teknisi'] ?? '-');
                            $keterangan = htmlspecialchars($row['keterangan'] ?? '-');
                            $status     = htmlspecialchars($row['status'] ?? '-');

                            echo "<tr>
                                <td>{$no}</td>
                                <td class='text-start fw-bold'>{$nama_aset}</td>
                                <td><i class='bi bi-geo-alt text-danger me-1'></i> {$lokasi}</td>
                                <td>" . (($row['kategori_aset'] ?? '') == 'Medis' ? '<span class="badge bg-danger">Medis</span>' : '<span class="badge bg-primary">Non-Medis</span>') . "</td>
                                <td>{$pelapor}</td>
                                <td class='fw-medium'>{$teknisi}</td>
                                <td><span class='{$statusClass}'>{$status}</span></td>
                                <td>" . formatTanggal($row['tanggal']) . "</td>
                                <td class='text-start'>{$keterangan}</td>
                            </tr>";
                            $no++;
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    $totalPages = ceil($totalRow / $perPage);
    if ($totalPages > 1) {
        echo '<nav aria-label="Page navigation" style="margin-top:12px;"><ul class="pagination">';
        $queryParams = $_GET;
        for ($p = 1; $p <= $totalPages; $p++) {
            $queryParams['page'] = $p;
            $url = 'laporan_perbaikan.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>