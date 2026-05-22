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
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .card-header {
        background: linear-gradient(90deg, #2c7a7b, #1cc88a) !important;
        color: #fff !important;
        font-weight: 600 !important;
        border-top-left-radius: 12px !important;
        border-top-right-radius: 12px !important;
        padding: 15px 20px;
    }

    .dashboard-header {
        background: linear-gradient(90deg, #2c7a7b, #1cc88a) !important;
        color: #fff !important;
    }

    table th {
        background-color: #f1f5f9 !important;
        color: #334155;
        font-weight: 600;
    }

    .btn-theme {
        background: linear-gradient(90deg, #2c7a7b, #1cc88a) !important;
        color: white !important;
        border: none;
        transition: opacity 0.2s;
    }

    .btn-theme:hover {
        color: white;
        opacity: 0.9;
    }

    .text-theme-green {
        color: #1cc88a !important;
        /* Warna hijau senada untuk teks tabel */
    }
</style>

<div class="dashboard-header mb-4 p-3 rounded">
    <h3 class="mb-0">LAPORAN PELACAKAN & MUTASI LOKASI ASET</h3>
</div>

<div class="content">
    <?php
    // Tangkap parameter filter
    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
    $bulan = isset($_GET['bulan']) ? mysqli_real_escape_string($koneksi, $_GET['bulan']) : '';

    // Susun kondisi WHERE
    $where = [];
    if ($search !== '') {
        $where[] = "(a.nama_aset LIKE '%$search%' OR r.penanggung_jawab LIKE '%$search%' OR r.lokasi_sebelumnya LIKE '%$search%' OR r.lokasi_baru LIKE '%$search%')";
    }
    if ($kategori !== '') {
        $where[] = "a.kategori_aset = '$kategori'";
    }
    if ($bulan !== '') {
        $where[] = "DATE_FORMAT(r.tanggal_pindah, '%Y-%m') = '$bulan'";
    }

    $whereSQL = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

    // Query mutasi data dari riwayat_lokasi
    $sql = "SELECT r.*, a.nama_aset, a.kategori_aset 
            FROM riwayat_lokasi r 
            JOIN aset a ON r.id_aset = a.id_aset 
            $whereSQL 
            ORDER BY r.tanggal_pindah DESC, r.id_riwayat DESC";
    $dataQ = mysqli_query($koneksi, $sql);
    ?>

    <div class="card mb-3">
        <div class="card-body p-3">
            <form class="row g-2 align-items-center" method="GET" action="">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama aset, P. Jawab, Ruangan..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="kategori" class="form-select form-select-sm">
                        <option value="">-- Semua Kategori --</option>
                        <option value="Medis" <?= $kategori == 'Medis' ? 'selected' : '' ?>>Medis (Alkes)</option>
                        <option value="Non-Medis" <?= $kategori == 'Non-Medis' ? 'selected' : '' ?>>Non-Medis</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="month" name="bulan" class="form-control form-control-sm" value="<?= htmlspecialchars($bulan) ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-theme btn-sm w-100" type="submit"><i class="bi bi-search"></i> Filter</button>
                    <a href="laporan_pelacakan.php" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                    <a href="cetak_laporan_pelacakan.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm w-100" target="_blank"><i class="bi bi-printer"></i> Cetak</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="bi bi-clock-history"></i> Status & Riwayat Pergerakan Lokasi Aset
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover border mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 10%;">Tanggal</th>
                            <th style="width: 22%;" class="text-start">Nama Aset</th>
                            <th style="width: 13%;">Kategori</th>
                            <th style="width: 15%;">P. Jawab</th>
                            <th style="width: 15%;">Lok. Awal</th>
                            <th style="width: 15%;">Lok. Baru</th>
                            <th style="width: 20%;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($dataQ) == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Data pelacakan aset tidak ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1;
                            while ($r = mysqli_fetch_assoc($dataQ)): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <small class="text-secondary"><?= date('d-m-Y', strtotime($r['tanggal_pindah'])); ?></small>
                                    </td>
                                    <td class="text-start fw-bold text-dark"><?= htmlspecialchars($r['nama_aset']); ?></td>
                                    <td>
                                        <small class="<?= ($r['kategori_aset'] == 'Medis') ? 'text-danger' : 'text-primary' ?>">
                                            <?= htmlspecialchars($r['kategori_aset'] == 'Medis' ? 'Medis' : 'Non-Medis'); ?>
                                        </small>
                                    </td>
                                    <td class="fw-bold text-primary"><?= htmlspecialchars($r['penanggung_jawab'] ?? '-'); ?></td>
                                    <td class="text-danger text-decoration-line-through small"><?= htmlspecialchars($r['lokasi_sebelumnya']); ?></td>
                                    <td class="fw-bold text-theme-green"><?= htmlspecialchars($r['lokasi_baru']); ?></td>
                                    <td class="text-start small text-muted"><?= htmlspecialchars($r['keterangan']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include(__DIR__ . '/../footer.php'); ?>