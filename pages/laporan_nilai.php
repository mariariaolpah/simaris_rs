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

    $search = $_GET['search'] ?? '';
    $dari = $_GET['dari'] ?? '';
    $sampai = $_GET['sampai'] ?? '';

    $where = ["harga > 0"];

    if ($search != '') $where[] = "(nama_aset LIKE '%$search%' OR asal_usul LIKE '%$search%')";
    if ($dari != '') $where[] = "tanggal_masuk >= '$dari'";
    if ($sampai != '') $where[] = "tanggal_masuk <= '$sampai'";

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    // =========================================================
    // MENGHITUNG STATISTIK TOTAL DENGAN RUMUS DEPRESIASI
    // =========================================================
    $statSumQ = mysqli_query($koneksi, "SELECT harga, tanggal_masuk, umur_ekonomis FROM aset $whereSQL");
    $total_aset = 0;
    $total_harga_awal = 0;
    $total_nilai_saat_ini = 0;

    $tahun_sekarang = date('Y');

    while ($rowStat = mysqli_fetch_assoc($statSumQ)) {
        $total_aset++;
        $harga_awal = $rowStat['harga'];
        // Pastikan kolom umur_ekonomis ada isinya, jika kosong anggap 0
        $umur_eko = isset($rowStat['umur_ekonomis']) ? (int)$rowStat['umur_ekonomis'] : 0;
        $tahun_masuk = date('Y', strtotime($rowStat['tanggal_masuk']));

        $total_harga_awal += $harga_awal;

        // Logika Depresiasi untuk kotak Total
        $nilai_buku = $harga_awal;
        if ($umur_eko > 0) {
            $selisih_tahun = $tahun_sekarang - $tahun_masuk;
            if ($selisih_tahun < 0) $selisih_tahun = 0;
            if ($selisih_tahun > $umur_eko) $selisih_tahun = $umur_eko; // Jika aset sudah lewat umur, nilainya habis jadi 0

            $penyusutan_per_tahun = $harga_awal / $umur_eko;
            $akumulasi = $selisih_tahun * $penyusutan_per_tahun;
            $nilai_buku = $harga_awal - $akumulasi;
        }
        $total_nilai_saat_ini += $nilai_buku;
    }

    // data untuk tabel (dengan pagination)
    $offset = ($page - 1) * $perPage;
    $dataQ = mysqli_query($koneksi, "SELECT * FROM aset $whereSQL ORDER BY tanggal_masuk DESC LIMIT $offset,$perPage");
    ?>

    <div class="d-flex justify-content-between mb-3 mt-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari aset..." value="<?= htmlspecialchars($search) ?>">
            <input type="date" name="dari" value="<?= htmlspecialchars($dari) ?>">
            <input type="date" name="sampai" value="<?= htmlspecialchars($sampai) ?>">
            <button class="btn btn-success btn-sm">🔍 Filter</button>
        </form>

        <a href="laporan_nilai_cetak.php?<?= http_build_query($_GET) ?>"
            class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
    </div>

    <div class="stats">
        <div class="stat border-start border-primary border-4">
            <small class="text-muted d-block">Jumlah Aset</small>
            <span class="fs-5 fw-bold"><?= $total_aset ?> Unit</span>
        </div>
        <div class="stat border-start border-warning border-4">
            <small class="text-muted d-block">Total Harga Beli (Awal)</small>
            <span class="fs-5 fw-bold">Rp <?= number_format($total_harga_awal, 0, ',', '.') ?></span>
        </div>
        <div class="stat border-start border-success border-4">
            <small class="text-muted d-block">Total Nilai Buku (Setelah Susut)</small>
            <span class="fs-5 fw-bold text-success">Rp <?= number_format($total_nilai_saat_ini, 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-header">Rincian Penyusutan Aset (Metode Garis Lurus)</div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-bordered table-hover text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle">No</th>
                        <th rowspan="2" class="align-middle">Nama Aset</th>
                        <th rowspan="2" class="align-middle">Kategori</th>
                        <th rowspan="2" class="align-middle">Tahun Masuk</th>
                        <th rowspan="2" class="align-middle">Umur Eko.</th>
                        <th colspan="3">Data Keuangan</th>
                    </tr>
                    <tr>
                        <th>Harga Beli</th>
                        <th>Susut / Tahun</th>
                        <th>Nilai Saat Ini</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = $offset + 1;
                    if (mysqli_num_rows($dataQ) == 0) {
                        echo "<tr><td colspan='8'>Data tidak ditemukan</td></tr>";
                    }

                    while ($r = mysqli_fetch_assoc($dataQ)) {
                        $harga = $r['harga'];
                        $umur = isset($r['umur_ekonomis']) ? (int)$r['umur_ekonomis'] : 0;
                        $tgl_masuk = $r['tanggal_masuk'];
                        $thn_masuk = date('Y', strtotime($tgl_masuk));
                        $kategori = isset($r['kategori_aset']) ? $r['kategori_aset'] : '-';

                        // PERHITUNGAN DEPRESIASI PER BARIS
                        $susut_per_tahun = 0;
                        $nilai_sekarang = $harga;

                        if ($umur > 0) {
                            $susut_per_tahun = $harga / $umur;

                            $pakai = $tahun_sekarang - $thn_masuk;
                            if ($pakai < 0) $pakai = 0;
                            if ($pakai > $umur) $pakai = $umur; // Jika lewat umur, nilai susut maksimal

                            $akumulasi = $pakai * $susut_per_tahun;
                            $nilai_sekarang = $harga - $akumulasi;
                        }

                        // Label Kategori
                        $badge = ($kategori == 'Medis') ? '<span class="badge bg-danger">Medis</span>' : '<span class="badge bg-primary">Non-Medis</span>';
                        if ($kategori == '-' || empty($kategori)) $badge = '<span class="badge bg-secondary">-</span>';

                        echo "<tr>
                            <td>$no</td>
                            <td class='fw-bold text-start'>{$r['nama_aset']}</td>
                            <td>$badge</td>
                            <td>$thn_masuk</td>
                            <td>" . ($umur > 0 ? "$umur Thn" : "-") . "</td>
                            <td class='text-end'>Rp " . number_format($harga, 0, ',', '.') . "</td>
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

</div>

<?php include(__DIR__ . '/../footer.php'); ?>