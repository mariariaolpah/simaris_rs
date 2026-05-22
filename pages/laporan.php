<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : "";

// =======================
// FUNGSI HITUNG DATA
// =======================
function getCount($koneksi, $jenis, $tahun)
{
    $table = '';
    $where = [];

    switch ($jenis) {
        case 'aset':
            $table = 'aset';
            break;
        case 'kerusakan':
            $table = 'kerusakan';
            break;
        case 'perawatan':
            $table = 'perawatan';
            break;
        case 'perbaikan':
            $table = 'kerusakan';
            $where[] = "status IN ('Dalam Perbaikan','Selesai Diperbaiki')";
            break;
        case 'perawatan_berjalan':
            $table = 'perawatan';
            $where[] = "status IN ('Belum Dimulai','Sedang Proses')";
            break;
        case 'peminjaman':
            $table = 'peminjaman';
            break;
        case 'audit_fisik':
            $table = 'audit_fisik';
            break;
        case 'nilai_aset':
            $table = 'aset';
            $where[] = "harga > 0";
            break;
        case 'kalibrasi':
            $table = 'perawatan';
            break;
        case 'pelacakan_lokasi':
            $table = 'aset';
            break;
        default:
            return 0;
    }

    if (!empty($tahun)) {
        // Cek nama kolom tanggal di tabel secara dinamis
        $col_date = 'tanggal'; // default
        if ($table == 'aset') $col_date = 'tanggal_masuk';
        elseif ($table == 'audit_fisik') $col_date = 'tanggal_audit';

        // Pengecekan aman, jika kolom tanggal beda nama, cari yg ada unsur 'tanggal'
        $check_col = @mysqli_query($koneksi, "SHOW COLUMNS FROM $table LIKE '$col_date'");
        if (mysqli_num_rows($check_col) == 0) {
            $res_cols = @mysqli_query($koneksi, "SHOW COLUMNS FROM $table LIKE '%tanggal%'");
            if ($row_col = mysqli_fetch_assoc($res_cols)) {
                $col_date = $row_col['Field'];
            }
        }

        $where[] = "YEAR($col_date) = '$tahun'";
    }

    $whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT COUNT(*) as total FROM $table $whereClause";

    $res = @mysqli_query($koneksi, $sql);

    // Jika query gagal (misal karena tabel tidak ada kolom tahun), hitung total tanpa filter tahun
    if (!$res) {
        $where_no_year = array_filter($where, function ($w) {
            return strpos($w, 'YEAR(') === false;
        });
        $whereClause2 = count($where_no_year) > 0 ? "WHERE " . implode(" AND ", $where_no_year) : "";
        $res = @mysqli_query($koneksi, "SELECT COUNT(*) as total FROM $table $whereClause2");
    }

    $data = $res ? mysqli_fetch_assoc($res) : ['total' => 0];

    return (int)($data['total'] ?? 0);
}

// =======================
// DATA LAPORAN
// =======================
$periode_text = !empty($tahun_filter) ? $tahun_filter : 'Semua Tahun';

$laporan_list = [
    ['Laporan Aset', getCount($koneksi, 'aset', $tahun_filter), $periode_text],
    ['Laporan Kerusakan', getCount($koneksi, 'kerusakan', $tahun_filter), $periode_text],
    ['Laporan Perawatan', getCount($koneksi, 'perawatan', $tahun_filter), $periode_text],
    ['Laporan Perbaikan', getCount($koneksi, 'perbaikan', $tahun_filter), $periode_text],
    ['Laporan Perawatan Berjalan', getCount($koneksi, 'perawatan_berjalan', $tahun_filter), $periode_text],
    ['Laporan Peminjaman Aset', getCount($koneksi, 'peminjaman', $tahun_filter), $periode_text],
    ['Laporan Hasil Audit Fisik', getCount($koneksi, 'audit_fisik', $tahun_filter), $periode_text],
    ['Laporan Rekapitulasi Nilai Aset', getCount($koneksi, 'nilai_aset', $tahun_filter), $periode_text],

    // FITUR BARU
    ['Laporan Kalibrasi', getCount($koneksi, 'kalibrasi', $tahun_filter), $periode_text],

    // FITUR BARU
    ['Laporan Pelacakan Lokasi Aset', getCount($koneksi, 'pelacakan_lokasi', $tahun_filter), $periode_text]
];

function getReportLink($jenis)
{
    switch ($jenis) {

        case 'Laporan Aset':
            return 'laporan_aset.php';

        case 'Laporan Kerusakan':
            return 'laporan_kerusakan.php';

        case 'Laporan Perawatan':
            return 'laporan_perawatan.php';

        case 'Laporan Perbaikan':
            return 'laporan_perbaikan.php';

        case 'Laporan Perawatan Berjalan':
            return 'laporan_perawatan_berjalan.php';

        case 'Laporan Peminjaman Aset':
            return 'laporan_peminjaman.php';

        case 'Laporan Hasil Audit Fisik':
            return 'laporan_audit.php';

        case 'Laporan Rekapitulasi Nilai Aset':
            return 'laporan_nilai.php';

            // LINK BARU
        case 'Laporan Kalibrasi':
            return 'laporan_perawatan.php';

            // LINK BARU
        case 'Laporan Pelacakan Lokasi Aset':
            return 'laporan_aset.php';

        default:
            return '#';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan | SIMARIS RS Bhayangkara</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            margin: 0;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #page-content-wrapper {
            flex: 1;
            padding: 0;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #fff;
        }

        .content {
            padding: 40px 30px;
        }

        .card-header {
            font-weight: 600;
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
        }

        .search-box {
            max-width: 250px;
        }

        .table tbody tr:hover {
            background: #f1fdfb;
            transition: 0.3s;
        }
    </style>
</head>

<body>

    <div id="wrapper">

        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">

            <div class="dashboard-header">
                <h3>LAPORAN</h3>

                <div class="admin-info">
                    <span>
                        <i class="bi bi-person-circle"></i>
                        <?= $_SESSION['nama_pengguna']; ?>
                    </span>
                </div>
            </div>

            <div class="content">

                <div class="card shadow-sm">

                    <div class="card-header d-flex justify-content-between align-items-center">

                        <span>Data Laporan</span>

                        <div class="d-flex gap-2 align-items-center">
                            <form method="GET" class="mb-0">
                                <select name="tahun" class="form-select form-select-sm border-0 text-dark" style="border-radius: 6px; min-width: 130px; cursor:pointer;" onchange="this.form.submit()">
                                    <option value="">Semua Tahun</option>
                                    <?php
                                    $thn_skrg = date('Y');
                                    for ($thn = $thn_skrg; $thn >= ($thn_skrg - 10); $thn--) {
                                        $selected = ($tahun_filter == $thn) ? 'selected' : '';
                                        echo "<option value='$thn' $selected>$thn</option>";
                                    }
                                    ?>
                                </select>
                            </form>

                            <input type="text"
                                id="searchInput"
                                class="form-control form-control-sm search-box border-0"
                                placeholder="Cari laporan..."
                                style="border-radius: 6px;">

                            <a href="cetak_semua_laporan_pdf.php<?= !empty($tahun_filter) ? '?tahun=' . $tahun_filter : '' ?>" target="_blank" class="btn btn-light btn-sm text-dark" style="border-radius: 6px; font-weight: 600; white-space: nowrap;">
                                <i class="bi bi-printer-fill text-danger"></i> Cetak Semua
                            </a>
                        </div>

                    </div>

                    <div class="card-body table-responsive">

                        <table class="table table-bordered table-hover align-middle text-center"
                            id="laporanTable">

                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Jenis Laporan</th>
                                    <th>Jumlah Data</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($laporan_list as $i => $l): ?>

                                    <?php
                                    if ($l[0] === 'Laporan Perawatan Berjalan') {
                                        $status = 'Sedang Proses';
                                    } elseif (
                                        $l[0] === 'Laporan Kalibrasi' ||
                                        $l[0] === 'Laporan Pelacakan Lokasi Aset'
                                    ) {
                                        $status = 'Monitoring';
                                    } else {
                                        $status = 'Selesai';
                                    }
                                    ?>

                                    <tr>

                                        <td><?= $i + 1 ?></td>

                                        <td class="text-start fw-bold text-dark">
                                            <?= $l[0] ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-secondary rounded-pill fs-6 px-3"><?= $l[1] ?></span>
                                        </td>

                                        <td><?= $l[2] ?></td>

                                        <td>

                                            <span class="badge bg-<?=
                                                                    $status === 'Selesai'
                                                                        ? 'success'
                                                                        : ($status === 'Monitoring'
                                                                            ? 'primary'
                                                                            : 'warning')
                                                                    ?>">

                                                <?= $status ?>

                                            </span>

                                        </td>

                                        <td>

                                            <a href="<?= getReportLink($l[0]) ?><?= !empty($tahun_filter) ? '?tahun=' . $tahun_filter : '' ?>"
                                                target="_blank"
                                                class="btn btn-sm btn-success" style="border-radius: 6px;">

                                                <i class="bi bi-eye"></i> Lihat

                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');

        searchInput.addEventListener('keyup', function() {

            let filter = searchInput.value.toLowerCase();

            let rows = document.querySelectorAll('#laporanTable tbody tr');

            rows.forEach(function(row) {

                let text = row.innerText.toLowerCase();

                row.style.display = text.includes(filter) ? '' : 'none';

            });

        });
    </script>

</body>

</html>