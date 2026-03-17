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
    .filter-form input {
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

    .status-sedang {
        background-color: #fff3cd;
        color: #856404;
        font-weight: 600;
        text-align: center;
        border-radius: 6px;
        padding: 3px 8px;
    }

    .status-selesai {
        background-color: #d4edda;
        color: #155724;
        font-weight: 600;
        text-align: center;
        border-radius: 6px;
        padding: 3px 8px;
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Perbaikan Aset</h3>
</div>

<div class="content">
    <?php
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    // Filter
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    // WHERE clause
    $where = ["1=1"];
    if ($search !== '') $where[] = "nama_aset LIKE '%$search%'";
    if ($dari !== '') $where[] = "tanggal >= '$dari'";
    if ($sampai !== '') $where[] = "tanggal <= '$sampai'";
    $whereSQL = implode(" AND ", $where);

    // Total data
    $totalQ = mysqli_query($koneksi, "
        SELECT COUNT(*) AS total 
        FROM kerusakan 
        WHERE $whereSQL 
        AND (status = 'Dalam Perbaikan' OR status = 'Selesai Diperbaiki')
    ");
    $totalRow = mysqli_fetch_assoc($totalQ)['total'];

    // Ambil data dari tabel kerusakan
    $dataQ = mysqli_query($koneksi, "
        SELECT id, nama_aset, status, tanggal, keterangan
        FROM kerusakan
        WHERE $whereSQL 
        AND (status = 'Dalam Perbaikan' OR status = 'Selesai Diperbaiki')
        ORDER BY tanggal DESC, nama_aset ASC
        LIMIT $offset, $perPage
    ");
    ?>

    <!-- Filter -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari aset..." value="<?= htmlspecialchars($search) ?>">
            <input type="date" name="dari" value="<?= $dari ?>">
            <input type="date" name="sampai" value="<?= $sampai ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="export_perbaikan_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-primary btn-sm">📥 Excel</a>
            <a href="cetak_laporan_perbaikan.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <!-- Statistik -->
    <div class="stats mb-3">
        <div class="stat">Total Data: <strong><?= intval($totalRow) ?></strong></div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-header">Data Perbaikan Aset</div>
        <table class="table table-bordered table-striped" style="border:1px solid #ccc;">
            <thead style="background-color:#f8f9fa;">
                <tr>
                    <th style="border:1px solid #ccc;">#</th>
                    <th style="border:1px solid #ccc;">Nama Aset</th>
                    <th style="border:1px solid #ccc;">Status</th>
                    <th style="border:1px solid #ccc;">Tanggal</th>
                    <th style="border:1px solid #ccc;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = $offset + 1;
                if (mysqli_num_rows($dataQ) == 0) {
                    echo "<tr><td colspan='5' class='text-center' style='border:1px solid #ccc;'>Belum ada data perbaikan</td></tr>";
                } else {
                    while ($row = mysqli_fetch_assoc($dataQ)) {
                        // Tentukan warna status otomatis
                        $statusClass = '';
                        if ($row['status'] == 'Selesai Diperbaiki') {
                            $statusClass = 'status-selesai';
                        } elseif ($row['status'] == 'Dalam Perbaikan') {
                            $statusClass = 'status-sedang';
                        }

                        echo "
                            <tr>
                                <td style='border:1px solid #ccc;'>{$no}</td>
                                <td style='border:1px solid #ccc;'>{$row['nama_aset']}</td>
                                <td style='border:1px solid #ccc;'><span class='{$statusClass}'>{$row['status']}</span></td>
                                <td style='border:1px solid #ccc;'>{$row['tanggal']}</td>
                                <td style='border:1px solid #ccc;'>{$row['keterangan']}</td>
                            </tr>
                        ";
                        $no++;
                    }
                }
                ?>
            </tbody>
        </table>

        <!-- Pagination -->
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
</div>

<?php include(__DIR__ . '/../footer.php'); ?>