<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// =======================
// FUNGSI HITUNG DATA
// =======================
function getCount($koneksi, $jenis)
{
    switch ($jenis) {

        case 'aset':
            $sql = "SELECT COUNT(*) as total FROM aset";
            break;

        case 'kerusakan':
            $sql = "SELECT COUNT(*) as total FROM kerusakan";
            break;

        case 'perawatan':
            $sql = "SELECT COUNT(*) as total FROM perawatan";
            break;

        case 'perbaikan':
            $sql = "SELECT COUNT(*) as total 
                    FROM kerusakan 
                    WHERE status IN ('Dalam Perbaikan','Selesai Diperbaiki')";
            break;

        case 'perawatan_berjalan':
            $sql = "SELECT COUNT(*) as total 
                    FROM perawatan 
                    WHERE status IN ('Belum Dimulai','Sedang Proses')";
            break;

        case 'peminjaman':
            $sql = "SELECT COUNT(*) as total FROM peminjaman";
            break;

        case 'audit_fisik':
            $sql = "SELECT COUNT(*) as total FROM audit_fisik";
            break;

        case 'nilai_aset':
            $sql = "SELECT COUNT(*) as total FROM aset WHERE harga > 0";
            break;

        // FITUR BARU
        case 'kalibrasi':
            $sql = "SELECT COUNT(*) as total FROM perawatan";
            break;

        // FITUR BARU
        case 'pelacakan_lokasi':
            $sql = "SELECT COUNT(*) as total FROM aset";
            break;

        default:
            return 0;
    }

    $res = mysqli_query($koneksi, $sql);
    $data = mysqli_fetch_assoc($res);

    return (int)($data['total'] ?? 0);
}

// =======================
// DATA LAPORAN
// =======================
$tahunSekarang = date('Y');

$laporan_list = [
    ['Laporan Aset', getCount($koneksi, 'aset'), $tahunSekarang],
    ['Laporan Kerusakan', getCount($koneksi, 'kerusakan'), $tahunSekarang],
    ['Laporan Perawatan', getCount($koneksi, 'perawatan'), $tahunSekarang],
    ['Laporan Perbaikan', getCount($koneksi, 'perbaikan'), $tahunSekarang],
    ['Laporan Perawatan Berjalan', getCount($koneksi, 'perawatan_berjalan'), $tahunSekarang],
    ['Laporan Peminjaman Aset', getCount($koneksi, 'peminjaman'), $tahunSekarang],
    ['Laporan Hasil Audit Fisik', getCount($koneksi, 'audit_fisik'), $tahunSekarang],
    ['Laporan Rekapitulasi Nilai Aset', getCount($koneksi, 'nilai_aset'), 'Semua'],

    // FITUR BARU
    ['Laporan Kalibrasi', getCount($koneksi, 'kalibrasi'), $tahunSekarang],

    // FITUR BARU
    ['Laporan Pelacakan Lokasi Aset', getCount($koneksi, 'pelacakan_lokasi'), $tahunSekarang]
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
            max-width: 300px;
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

                        <!-- SEARCH -->
                        <input type="text"
                            id="searchInput"
                            class="form-control search-box"
                            placeholder="Cari laporan...">

                    </div>

                    <div class="card-body table-responsive">

                        <table class="table table-bordered table-hover align-middle text-center"
                            id="laporanTable">

                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Jenis Laporan</th>
                                    <th>Jumlah</th>
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

                                        <td class="text-start">
                                            <?= $l[0] ?>
                                        </td>

                                        <td><?= $l[1] ?></td>

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

                                            <a href="<?= getReportLink($l[0]) ?>"
                                                target="_blank"
                                                class="btn btn-sm btn-success">

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

    <!-- SEARCH -->
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