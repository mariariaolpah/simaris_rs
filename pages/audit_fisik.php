<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$query = mysqli_query($koneksi, "SELECT audit_fisik.*, aset.nama_aset FROM audit_fisik 
                                 JOIN aset ON audit_fisik.id_aset = aset.id_aset 
                                 ORDER BY id_audit DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Audit Fisik | SIMARIS RS Bhayangkara</title>
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
            width: 100%;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content {
            padding: 40px 30px;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h3 class="fw-bold">AUDIT FISIK ASET</h3>
                <div class="admin-info"><i class="bi bi-person-circle"></i> <span class="fw-bold"><?= $_SESSION['nama_pengguna']; ?></span></div>
            </div>
            <div class="content">
                <div class="card shadow-sm">
                    <div class="card-header py-3">
                        <span>Riwayat Audit Fisik (Stock Opname)</span>
                        <div class="d-flex gap-2">
                            <a href="audit_tambah.php" class="btn btn-light btn-sm fw-bold"><i class="bi bi-plus-circle"></i> Tambah Audit</a>
                            <a href="audit_cetak.php" class="btn btn-light btn-sm fw-bold"><i class="bi bi-printer"></i> Cetak Laporan</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Aset</th>
                                    <th>Tgl Audit</th>
                                    <th>Kondisi Fisik</th>
                                    <th>Auditor</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                while ($row = mysqli_fetch_assoc($query)): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td class="text-start"><?= htmlspecialchars($row['nama_aset']); ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal_audit'])); ?></td>
                                        <td><span class="badge bg-info"><?= $row['kondisi_fisik']; ?></span></td>
                                        <td><?= htmlspecialchars($row['auditor']); ?></td>
                                        <td><?= htmlspecialchars($row['keterangan']); ?></td>
                                        <td>
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="audit_edit.php?id=<?= $row['id_audit']; ?>" class="btn btn-warning btn-sm text-white"><i class="bi bi-pencil-square"></i></a>
                                                <a href="audit_hapus.php?id=<?= $row['id_audit']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus?')"><i class="bi bi-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>