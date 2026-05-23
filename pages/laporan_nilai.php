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
        flex: 1;
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Nilai & Depresiasi Aset</h3>
</div>

<div class="content">

    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;

    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = ["harga > 0"];
    if ($search != '') $where[] = "(nama_aset LIKE '%$search%' OR asal_usul LIKE '%$search%')";
    if ($kategoriFilter != '') $where[] = "kategori_aset = '$kategoriFilter'";
    if ($dari != '') $where[] = "tanggal_masuk >= '$dari'";
    if ($sampai != '') $where[] = "tanggal_masuk <= '$sampai'";

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    // =========================================================
    // MENGHITUNG STATISTIK TOTAL DENGAN RUMUS DEPRESIASI (DIKALIKAN TOTAL STOK)
    // =========================================================
    // PERBAIKAN: Mengubah 'stok' menjadi 'total_stok'
    $statSumQ = mysqli_query($koneksi, "SELECT harga, total_stok, tanggal_masuk, umur_ekonomis FROM aset $whereSQL");
    $total_jenis_aset = 0;
    $total_unit_aset = 0;
    $total_harga_awal = 0;
    $total_nilai_saat_ini = 0;

    $tahun_sekarang = date('Y');

    while ($rowStat = mysqli_fetch_assoc($statSumQ)) {
        $total_jenis_aset++;
        // PERBAIKAN: Mengubah 'stok' menjadi 'total_stok'
        $stok = (int)($rowStat['total_stok'] ?? 0);
        $total_unit_aset += $stok;

        $harga_awal = $rowStat['harga'] * $stok; // Harga total berdasarkan stok
        $umur_eko = isset($rowStat['umur_ekonomis']) ? (int)$rowStat['umur_ekonomis'] : 0;
        $tahun_masuk = date('Y', strtotime($rowStat['tanggal_masuk']));

        $total_harga_awal += $harga_awal;

        $nilai_buku = $harga_awal;
        if ($umur_eko > 0) {
            $selisih_tahun = $tahun_sekarang - $tahun_masuk;
            if ($selisih_tahun < 0) $selisih_tahun = 0;
            if ($selisih_tahun > $umur_eko) $selisih_tahun = $umur_eko;

            $penyusutan_per_tahun = $harga_awal / $umur_eko;
            $akumulasi = $selisih_tahun * $penyusutan_per_tahun;
            $nilai_buku = $harga_awal - $akumulasi;
        }
        $total_nilai_saat_ini += $nilai_buku;
    }

    $countQ = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM aset $whereSQL");
    $totalRow = mysqli_fetch_assoc($countQ)['total'];
    $offset = ($page - 1) * $perPage;

    $dataQ = mysqli_query($koneksi, "SELECT * FROM aset $whereSQL ORDER BY tanggal_masuk DESC LIMIT $offset, $perPage");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari aset/asal usul..." value="<?= htmlspecialchars($search) ?>">
            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Medis" <?= $kategoriFilter == 'Medis' ? 'selected' : '' ?>>Medis</option>
                <option value="Non-Medis" <?= $kategoriFilter == 'Non-Medis' ? 'selected' : '' ?>>Non-Medis</option>
            </select>
            <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">
            <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <a href="laporan_nilai_cetak.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
    </div>

    <div class="stats">
        <div class="stat border-start border-primary border-4">
            <small class="text-muted d-block">Jumlah Aset (Berdasarkan Unit)</small>
            <span class="fs-5 fw-bold"><?= $total_unit_aset ?> Unit (Dari <?= $total_jenis_aset ?> Jenis)</span>
        </div>
        <div class="stat border-start border-warning border-4">
            <small class="text-muted d-block">Total Nilai Investasi (Awal)</small>
            <span class="fs-5 fw-bold">Rp <?= number_format($total_harga_awal, 0, ',', '.') ?></span>
        </div>
        <div class="stat border-start border-success border-4">
            <small class="text-muted d-block">Total Nilai Buku (Saat Ini)</small>
            <span class="fs-5 fw-bold text-success">Rp <?= number_format($total_nilai_saat_ini, 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header">Rincian Penyusutan Nilai Aset (Harga x Stok)</div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-bordered table-hover text-center mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle">No</th>
                        <th rowspan="2" class="align-middle text-start">Nama Aset</th>
                        <th rowspan="2" class="align-middle">Kategori</th>
                        <th rowspan="2" class="align-middle">Tahun Masuk</th>
                        <th rowspan="2" class="align-middle">Umur</th>
                        <th rowspan="2" class="align-middle">Stok</th>
                        <th colspan="3">Data Keuangan & Depresiasi Total</th>
                    </tr>
                    <tr>
                        <th>Harga Beli (Total)</th>
                        <th>Susut / Tahun</th>
                        <th>Nilai Buku (Saat Ini)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = $offset + 1;
                    if (mysqli_num_rows($dataQ) == 0) {
                        echo "<tr><td colspan='9' class='py-4'>Data aset dengan nilai tidak ditemukan</td></tr>";
                    }

                    while ($r = mysqli_fetch_assoc($dataQ)) {
                        // PERBAIKAN: Mengubah 'stok' menjadi 'total_stok'
                        $stok = (int)($r['total_stok'] ?? 0);
                        $harga_total = $r['harga'] * $stok;

                        $umur = isset($r['umur_ekonomis']) ? (int)$r['umur_ekonomis'] : 0;
                        $tgl_masuk = $r['tanggal_masuk'];
                        $thn_masuk = date('Y', strtotime($tgl_masuk));
                        $kategori = isset($r['kategori_aset']) ? $r['kategori_aset'] : '-';

                        $susut_per_tahun = 0;
                        $nilai_sekarang = $harga_total;

                        if ($umur > 0) {
                            $susut_per_tahun = $harga_total / $umur;

                            $pakai = $tahun_sekarang - $thn_masuk;
                            if ($pakai < 0) $pakai = 0;
                            if ($pakai > $umur) $pakai = $umur;

                            $akumulasi = $pakai * $susut_per_tahun;
                            $nilai_sekarang = $harga_total - $akumulasi;
                        }

                        $badge = ($kategori == 'Medis') ? '<span class="badge bg-danger">Medis</span>' : '<span class="badge bg-primary">Non-Medis</span>';
                        if ($kategori == '-' || empty($kategori)) $badge = '<span class="badge bg-secondary">-</span>';

                        echo "<tr>
                            <td>$no</td>
                            <td class='fw-bold text-start'>{$r['nama_aset']}</td>
                            <td>$badge</td>
                            <td>$thn_masuk</td>
                            <td>" . ($umur > 0 ? "$umur Thn" : "-") . "</td>
                            <td class='fw-bold text-primary'>$stok</td>
                            <td class='text-end'>Rp " . number_format($harga_total, 0, ',', '.') . "</td>
                            <td class='text-end text-danger'>- Rp " . number_format($susut_per_tahun, 0, ',', '.') . "</td>
                            <td class='text-end fw-bold text-success'>Rp " . number_format($nilai_sekarang, 0, ',', '.') . "</td>
                        </tr>";
                        $no++;
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
            $url = 'laporan_nilai.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>