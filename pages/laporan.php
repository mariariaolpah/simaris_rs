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
function getCount($koneksi, $table, $tahun = null)
{
    $whereExtra = '';

    switch ($table) {
        case 'aset':
            $tanggalKolom = 'tanggal_masuk';
            break;

        case 'kerusakan':
        case 'perawatan':
            $tanggalKolom = 'tanggal';
            break;

        case 'perbaikan':
            $table = 'kerusakan';
            $tanggalKolom = 'tanggal';
            $whereExtra = " AND status IN ('Dalam Perbaikan','Selesai Diperbaiki')";
            break;

        case 'perawatan_berjalan':
            $table = 'perawatan';
            $tanggalKolom = 'tanggal';
            $whereExtra = " AND status IN ('Belum Dimulai','Sedang Proses')";
            break;

        case 'peminjaman':
            $tanggalKolom = 'tanggal_pinjam';
            break;

        case 'audit_fisik':
            $tanggalKolom = 'tanggal_audit';
            break;

        case 'nilai_aset':
            $table = 'aset';
            $tanggalKolom = 'tanggal_masuk';
            $whereExtra = " AND harga > 0";
            break;

        default:
            $tanggalKolom = 'tanggal';
    }

    if ($tahun) {
        $sql = "SELECT COUNT(*) AS total 
                FROM $table 
                WHERE YEAR($tanggalKolom) = '$tahun' $whereExtra";
    } else {
        $sql = "SELECT COUNT(*) AS total 
                FROM $table 
                WHERE 1=1 $whereExtra";
    }

    $res = mysqli_query($koneksi, $sql);
    $data = mysqli_fetch_assoc($res);
    return (int)($data['total'] ?? 0);
}

/// =======================
// AMBIL DATA LAPORAN (GENAP 8)
// =======================
$tahunSekarang = date('Y');

$laporan_list = [
    ['Laporan Aset', getCount($koneksi, 'aset', $tahunSekarang), $tahunSekarang],
    ['Laporan Kerusakan', getCount($koneksi, 'kerusakan', $tahunSekarang), $tahunSekarang],
    ['Laporan Perawatan', getCount($koneksi, 'perawatan', $tahunSekarang), $tahunSekarang],
    ['Laporan Perbaikan', getCount($koneksi, 'perbaikan', $tahunSekarang), $tahunSekarang],
    ['Laporan Perawatan Berjalan', getCount($koneksi, 'perawatan_berjalan', $tahunSekarang), $tahunSekarang],
    ['Laporan Peminjaman Aset', getCount($koneksi, 'peminjaman', $tahunSekarang), $tahunSekarang],
    ['Laporan Hasil Audit Fisik', getCount($koneksi, 'audit_fisik', $tahunSekarang), $tahunSekarang],
    ['Laporan Rekapitulasi Nilai Aset', getCount($koneksi, 'nilai_aset'), 'Semua']
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>
        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h3>LAPORAN</h3>
                <div class="admin-info">
                    <span><i class="bi bi-person-circle"></i> <?= $_SESSION['nama_pengguna']; ?></span>
                </div>
            </div>
            <div class="content">
                <div class="card shadow-sm">
                    <div class="card-header">Data Laporan</div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover align-middle text-center">
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
                                <?php foreach ($laporan_list as $i => $l):
                                    $status = ($l[0] === 'Laporan Perawatan Berjalan') ? 'Sedang Proses' : 'Selesai';
                                ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td class="text-start"><?= $l[0] ?></td>
                                        <td><?= $l[1] ?></td>
                                        <td><?= $l[2] ?></td>
                                        <td><span class="badge bg-<?= $status === 'Selesai' ? 'success' : 'warning' ?>"><?= $status ?></span></td>
                                        <td>
                                            <a href="<?= getReportLink($l[0]) ?>" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-eye"></i> Lihat</a>
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
</body>

</html>