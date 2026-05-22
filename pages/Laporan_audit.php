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

    .img-kerusakan {
        width: 55px;
        height: 55px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        padding: 2px;
        background-color: #fff;
    }
</style>

<div class="dashboard-header">
    <h3 class="mb-0">Laporan Audit Fisik Aset</h3>
</div>

<div class="content">
    <?php
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    $search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
    $kondisi = isset($_GET['kondisi']) ? mysqli_real_escape_string($koneksi, $_GET['kondisi']) : '';
    $kategoriFilter = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';
    $dari = isset($_GET['dari']) ? mysqli_real_escape_string($koneksi, $_GET['dari']) : '';
    $sampai = isset($_GET['sampai']) ? mysqli_real_escape_string($koneksi, $_GET['sampai']) : '';

    $where = [];
    if ($search !== '') $where[] = "(a.nama_aset LIKE '%$search%' OR a.lokasi LIKE '%$search%' OR ad.auditor LIKE '%$search%' OR ad.keterangan LIKE '%$search%')";
    if ($kondisi !== '') $where[] = "ad.kondisi_fisik = '$kondisi'";
    if ($kategoriFilter !== '') $where[] = "a.kategori_aset = '$kategoriFilter'";
    if ($dari !== '') $where[] = "ad.tanggal_audit >= '$dari'";
    if ($sampai !== '') $where[] = "ad.tanggal_audit <= '$sampai'";

    $whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // MENGGUNAKAN JOIN (INNER JOIN) AGAR HANYA MENAMPILKAN ASET YANG MASIH ADA
    $totalQ = mysqli_query($koneksi, "
        SELECT COUNT(*) as total 
        FROM audit_fisik ad
        JOIN aset a ON ad.id_aset = a.id_aset
        $whereSQL
    ");
    $totalRow = mysqli_fetch_assoc($totalQ)['total'];

    // MENGGUNAKAN JOIN (INNER JOIN) AGAR SINKRON DENGAN AUDIT_FISIK.PHP
    $dataQ = mysqli_query($koneksi, "
        SELECT ad.*, a.nama_aset, a.lokasi, a.kategori_aset
        FROM audit_fisik ad
        JOIN aset a ON ad.id_aset = a.id_aset
        $whereSQL
        ORDER BY ad.tanggal_audit DESC, ad.id_audit DESC
        LIMIT $offset, $perPage
    ");
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <form class="d-flex gap-2 filter-form" method="GET">
            <input type="text" name="search" placeholder="Cari aset/auditor..." value="<?= htmlspecialchars($search) ?>">

            <select name="kategori">
                <option value="">Semua Kategori</option>
                <option value="Medis" <?= $kategoriFilter == 'Medis' ? 'selected' : '' ?>>Medis</option>
                <option value="Non-Medis" <?= $kategoriFilter == 'Non-Medis' ? 'selected' : '' ?>>Non-Medis</option>
            </select>

            <select name="kondisi">
                <option value="">Semua Kondisi</option>
                <option value="Baik" <?= $kondisi == 'Baik' ? 'selected' : '' ?>>Baik</option>
                <option value="Perlu Perawatan" <?= $kondisi == 'Perlu Perawatan' ? 'selected' : '' ?>>Perlu Perawatan</option>
                <option value="Rusak" <?= $kondisi == 'Rusak' ? 'selected' : '' ?>>Rusak</option>
                <option value="Hilang" <?= $kondisi == 'Hilang' ? 'selected' : '' ?>>Hilang</option>
            </select>

            <input type="date" name="dari" value="<?= $dari ?>">
            <input type="date" name="sampai" value="<?= $sampai ?>">
            <button class="btn btn-success btn-sm" type="submit">🔍 Filter</button>
        </form>

        <div>
            <a href="cetak_laporan_audit.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm" target="_blank">🖨 Cetak PDF</a>
        </div>
    </div>

    <div class="stats mb-3">
        <div class="stat">Total Riwayat Audit: <strong><?= intval($totalRow) ?></strong></div>
    </div>

    <div class="card">
        <div class="card-header">Data Rekapitulasi Audit Fisik</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 text-center align-middle">
                    <thead>
                        <tr>
                            <th style="width:60px;">No</th>
                            <th class="text-start">Nama Aset</th>
                            <th>Kategori</th>
                            <th>Lokasi Ruang</th>
                            <th>Auditor</th>
                            <th>Tanggal Audit</th>
                            <th>Kondisi Fisik</th>
                            <th>Bukti Fisik</th>
                            <th class="text-start">Keterangan Tambahan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($dataQ) == 0) {
                            echo '<tr><td colspan="9" class="text-center py-4">Tidak ada riwayat audit ditemukan</td></tr>';
                        } else {
                            $no = $offset + 1;
                            while ($r = mysqli_fetch_assoc($dataQ)) {
                                $kondisi_db = $r['kondisi_fisik'] ?? '';
                                $badgeKondisi = '<span class="badge bg-secondary">' . htmlspecialchars($kondisi_db) . '</span>';
                                if ($kondisi_db == 'Baik') $badgeKondisi = '<span class="badge bg-success">Baik</span>';
                                elseif ($kondisi_db == 'Perlu Perawatan') $badgeKondisi = '<span class="badge bg-warning text-dark">Perlu Perawatan</span>';
                                elseif ($kondisi_db == 'Rusak') $badgeKondisi = '<span class="badge bg-danger">Rusak</span>';
                                elseif ($kondisi_db == 'Hilang') $badgeKondisi = '<span class="badge bg-dark">Hilang</span>';

                                $nama_aset = htmlspecialchars($r['nama_aset'] ?? '-');
                                $kategori  = htmlspecialchars($r['kategori_aset'] ?? '-');
                                $lokasi    = htmlspecialchars($r['lokasi'] ?? '-');
                                $auditor   = htmlspecialchars($r['auditor'] ?? '-');
                                $tanggal   = htmlspecialchars($r['tanggal_audit'] ?? '-');
                                $keterangan = htmlspecialchars($r['keterangan'] ?? '-');

                                $gambar = $r['gambar_rusak'] ?? '';
                                $td_gambar = !empty($gambar) ? '<img src="../assets/img/' . htmlspecialchars($gambar) . '" class="img-kerusakan">' : '<span class="text-muted">-</span>';

                                $badgeKategori = ($kategori == 'Medis') ? '<span class="badge bg-danger">Medis</span>' : '<span class="badge bg-primary">Non-Medis</span>';
                                if ($kategori == '-') $badgeKategori = '-';

                                echo "<tr>
                                    <td>{$no}</td>
                                    <td class='text-start fw-bold'>{$nama_aset}</td>
                                    <td>{$badgeKategori}</td>
                                    <td><i class='bi bi-geo-alt text-danger me-1'></i>{$lokasi}</td>
                                    <td>{$auditor}</td>
                                    <td>" . date('d-m-Y', strtotime($tanggal)) . "</td>
                                    <td>{$badgeKondisi}</td>
                                    <td>{$td_gambar}</td>
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
    </div>

    <?php
    $totalPages = ceil($totalRow / $perPage);
    if ($totalPages > 1) {
        echo '<nav aria-label="Page navigation" style="margin-top:12px;"><ul class="pagination">';
        $queryParams = $_GET;
        for ($p = 1; $p <= $totalPages; $p++) {
            $queryParams['page'] = $p;
            $url = 'Laporan_audit.php?' . http_build_query($queryParams);
            $active = $p == $page ? 'active' : '';
            echo "<li class='page-item $active'><a class='page-link' href='$url'>$p</a></li>";
        }
        echo '</ul></nav>';
    }
    ?>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>