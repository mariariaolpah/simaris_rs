<?php
session_start();
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
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
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Nilai Aset</h3>
</div>

<div class="content">

    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;

    $search = $_GET['search'] ?? '';
    $dari = $_GET['dari'] ?? '';
    $sampai = $_GET['sampai'] ?? '';

    $where = ["harga > 0"];

    if ($search != '') $where[] = "(nama_aset LIKE '%$search%' OR asal_usul LIKE '%$search%')";
    if ($dari != '') $where[] = "tanggal_masuk >= '$dari'";
    if ($sampai != '') $where[] = "tanggal_masuk <= '$sampai'";

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    // statistik
    $statQ = mysqli_query($koneksi, "SELECT COUNT(*) as total, SUM(harga) as total_nilai FROM aset $whereSQL");
    $stat = mysqli_fetch_assoc($statQ);

    // data
    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM aset $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];

    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "SELECT * FROM aset $whereSQL ORDER BY tanggal_masuk DESC LIMIT $offset,$perPage");
    ?>

    <!-- FILTER -->
    <div class="d-flex justify-content-between mb-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari aset..." value="<?= $search ?>">
            <input type="date" name="dari" value="<?= $dari ?>">
            <input type="date" name="sampai" value="<?= $sampai ?>">
            <button class="btn btn-success btn-sm">🔍 Filter</button>
        </form>

        <a href="laporan_nilai_cetak.php?<?= http_build_query($_GET) ?>"
            class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
    </div>

    <!-- STAT -->
    <div class="stats">
        <div class="stat">Jumlah: <b><?= $stat['total'] ?></b></div>
        <div class="stat">Total Nilai: <b>Rp <?= number_format($stat['total_nilai'], 0, ',', '.') ?></b></div>
    </div>

    <!-- TABEL -->
    <div class="card">
        <div class="card-header">Data Nilai Aset</div>
        <div class="card-body p-0">
            <table class="table table-bordered">
                <tr>
                    <th>No</th>
                    <th>Nama Aset</th>
                    <th>Asal</th>
                    <th>Tanggal</th>
                    <th>Harga</th>
                    <th>Kondisi</th>
                </tr>

                <?php
                $no = $offset + 1;
                while ($r = mysqli_fetch_assoc($dataQ)) {
                    echo "<tr>
                    <td>$no</td>
                    <td>{$r['nama_aset']}</td>
                    <td>{$r['asal_usul']}</td>
                    <td>{$r['tanggal_masuk']}</td>
                    <td>Rp " . number_format($r['harga'], 0, ',', '.') . "</td>
                    <td>{$r['kondisi']}</td>
                </tr>";
                    $no++;
                }
                ?>
            </table>
        </div>
    </div>

</div>

<?php include(__DIR__ . '/../footer.php'); ?>