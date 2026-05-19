<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ================= PROSES SIMPAN DATA MUTASI =================
if (isset($_POST['simpan_pindah'])) {
    $id_aset = (int)$_POST['id_aset'];
    $lokasi_baru = mysqli_real_escape_string($koneksi, $_POST['lokasi_baru']);
    $tanggal_pindah = mysqli_real_escape_string($koneksi, $_POST['tanggal_pindah']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    // [UPDATE] Mengambil nilai penanggung jawab dari form
    $penanggung_jawab = mysqli_real_escape_string($koneksi, $_POST['penanggung_jawab']);

    // Ambil lokasi saat ini sebagai lokasi_sebelumnya
    $queryAset = mysqli_query($koneksi, "SELECT lokasi FROM aset WHERE id_aset = $id_aset");
    $dataAset = mysqli_fetch_assoc($queryAset);
    $lokasi_sebelumnya = $dataAset['lokasi'];

    if ($lokasi_sebelumnya == $lokasi_baru) {
        echo "<script>alert('Lokasi baru tidak boleh sama dengan lokasi saat ini!');window.location='aset_pindah.php';</script>";
    } else {
        // 1. Masukkan ke tabel log riwayat_lokasi dengan kolom penanggung_jawab
        $insert = mysqli_query($koneksi, "INSERT INTO riwayat_lokasi (id_aset, lokasi_sebelumnya, lokasi_baru, tanggal_pindah, keterangan, penanggung_jawab) 
                                VALUES ($id_aset, '$lokasi_sebelumnya', '$lokasi_baru', '$tanggal_pindah', '$keterangan', '$penanggung_jawab')");

        // 2. Perbarui posisi ruangan terkini di tabel induk aset
        mysqli_query($koneksi, "UPDATE aset SET lokasi = '$lokasi_baru' WHERE id_aset = $id_aset");

        if ($insert) {
            echo "<script>alert('Aset berhasil dipindahkan dan riwayat tercatat!');window.location='aset_pindah.php';</script>";
        }
    }
}

// Ambil list aset untuk form dropdown selection
$list_aset = mysqli_query($koneksi, "SELECT id_aset, nama_aset, lokasi FROM aset ORDER BY nama_aset ASC");

// ================= FITUR: PENCARIAN & FILTER KATEGORI =================
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : "";
$filter_kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : "";

$sql_riwayat = "SELECT r.*, a.nama_aset, a.kategori_aset 
                FROM riwayat_lokasi r 
                JOIN aset a ON r.id_aset = a.id_aset";

$conditions = [];
if ($search != '') {
    // [UPDATE] Pencarian juga mencari nama penanggung jawab
    $conditions[] = "(a.nama_aset LIKE '%$search%' OR r.lokasi_sebelumnya LIKE '%$search%' OR r.lokasi_baru LIKE '%$search%' OR r.keterangan LIKE '%$search%' OR r.penanggung_jawab LIKE '%$search%')";
}
if ($filter_kategori != '') {
    $conditions[] = "a.kategori_aset = '$filter_kategori'";
}

if (count($conditions) > 0) {
    $sql_riwayat .= " WHERE " . implode(" AND ", $conditions);
}
$sql_riwayat .= " ORDER BY r.id_riwayat DESC";

$query_riwayat = mysqli_query($koneksi, $sql_riwayat);
$riwayat_pindah = [];
while ($row = mysqli_fetch_assoc($query_riwayat)) {
    $riwayat_pindah[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pelacakan Lokasi Aset | SIMARIS</title>
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
            padding: 40px 30px 50px 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            font-weight: 600;
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            padding: 15px 20px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
        }

        table th {
            background-color: #f1f5f9 !important;
            color: #334155;
            font-weight: 600;
        }

        table td {
            vertical-align: middle !important;
        }

        .btn-gradient {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: white;
            border: none;
            transition: opacity 0.2s;
        }

        .btn-gradient:hover {
            color: white;
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h3>PELACAKAN & MUTASI LOKASI ASET</h3>
                <div>
                    <i class="bi bi-person-circle"></i> <?= $_SESSION['nama_pengguna']; ?>
                </div>
            </div>

            <div class="content">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-box-arrow-right"></i> Form Pindah Lokasi Aset
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Pilih Aset / Alkes</label>
                                        <select name="id_aset" class="form-select" required>
                                            <option value="">-- Pilih Barang --</option>
                                            <?php while ($a = mysqli_fetch_assoc($list_aset)): ?>
                                                <option value="<?= $a['id_aset'] ?>">
                                                    <?= htmlspecialchars($a['nama_aset']) ?> (Posisi: <?= htmlspecialchars($a['lokasi']) ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Lokasi Baru / Ruangan Baru</label>
                                        <select name="lokasi_baru" class="form-select" required>
                                            <option value="">-- Pilih Ruangan Tujuan --</option>
                                            <?php
                                            $q_lokasi = mysqli_query($koneksi, "SELECT * FROM lokasi_aset ORDER BY nama_lokasi ASC");
                                            while ($lok = mysqli_fetch_assoc($q_lokasi)):
                                            ?>
                                                <option value="<?= htmlspecialchars($lok['nama_lokasi']) ?>">
                                                    <?= htmlspecialchars($lok['nama_lokasi']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Tanggal Pindah</label>
                                        <input type="date" name="tanggal_pindah" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-primary"><i class="bi bi-person-badge"></i> Penanggung Jawab</label>
                                        <input type="text" name="penanggung_jawab" class="form-control" placeholder="Cth: Perawat Siti (UGD)" required>
                                        <small class="text-muted">Nama peminta atau petugas yang memindahkan.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Keterangan / Alasan</label>
                                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Alasan pemindahan barang..." required></textarea>
                                    </div>

                                    <button type="submit" name="simpan_pindah" class="btn btn-gradient w-100 py-2 fw-semibold">
                                        <i class="bi bi-save"></i> Proses Perpindahan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-clock-history"></i> Log Riwayat Posisi & Mutasi Aset
                            </div>
                            <div class="card-body">

                                <form method="GET" class="row g-2 mb-4 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold text-secondary">Kata Kunci Pencarian</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, P.Jawab, atau ruangan...">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-secondary">Filter Jenis</label>
                                        <select name="kategori" class="form-select form-select-sm">
                                            <option value="">-- Semua Kategori --</option>
                                            <option value="Medis" <?= $filter_kategori == 'Medis' ? 'selected' : '' ?>>Medis (Alkes)</option>
                                            <option value="Non-Medis" <?= $filter_kategori == 'Non-Medis' ? 'selected' : '' ?>>Non-Medis</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex gap-1">
                                        <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                                            <i class="bi bi-filter"></i> Filter
                                        </button>
                                        <?php if ($search != '' || $filter_kategori != ''): ?>
                                            <a href="aset_pindah.php" class="btn btn-outline-danger btn-sm">Reset</a>
                                        <?php endif; ?>

                                        <a href="aset_pindah_cetak.php?search=<?= urlencode($search) ?>&kategori=<?= urlencode($filter_kategori) ?>" class="btn btn-danger btn-sm" target="_blank">
                                            <i class="bi bi-file-pdf"></i> Cetak
                                        </a>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-hover border text-center align-middle">

                                        <thead>
                                            <tr>
                                                <th style="width: 10%;">Tanggal</th>
                                                <th style="width: 22%;">Nama Aset</th>
                                                <th style="width: 15%;">P. Jawab</th>
                                                <th style="width: 23%;">Pergerakan Ruang</th>
                                                <th style="width: 20%;">Keterangan</th>
                                                <th style="width: 10%;">Aksi</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                            <?php if (empty($riwayat_pindah)): ?>

                                                <tr>
                                                    <td colspan="6" class="text-muted py-4">
                                                        Tidak ditemukan riwayat perpindahan lokasi aset yang sesuai.
                                                    </td>
                                                </tr>

                                            <?php else: ?>

                                                <?php foreach ($riwayat_pindah as $r) : ?>

                                                    <tr>

                                                        <td>
                                                            <small class="text-secondary">
                                                                <?= date('d-m-Y', strtotime($r['tanggal_pindah'])) ?>
                                                            </small>
                                                        </td>

                                                        <td class="fw-bold text-start text-dark">

                                                            <?= htmlspecialchars($r['nama_aset']) ?>

                                                            <br>

                                                            <small class="<?= ($r['kategori_aset'] == 'Medis') ? 'text-danger' : 'text-primary' ?>">

                                                                <?= ($r['kategori_aset'] == 'Medis') ? 'Medis (Alkes)' : 'Non-Medis' ?>

                                                            </small>

                                                        </td>

                                                        <td class="fw-bold text-primary">

                                                            <?= htmlspecialchars(isset($r['penanggung_jawab']) ? $r['penanggung_jawab'] : '-') ?>

                                                        </td>

                                                        <td class="p-2 bg-light rounded shadow-sm">

                                                            <span class="text-danger text-decoration-line-through small">
                                                                <?= htmlspecialchars($r['lokasi_sebelumnya']) ?>
                                                            </span>

                                                            <br>

                                                            <i class="bi bi-arrow-down text-warning my-1 d-block fw-bold"></i>

                                                            <span class="text-success fw-bold">
                                                                <?= htmlspecialchars($r['lokasi_baru']) ?>
                                                            </span>

                                                        </td>

                                                        <td class="text-start small text-muted">

                                                            <?= htmlspecialchars($r['keterangan']) ?>

                                                        </td>

                                                        <td>

                                                            <div class="d-flex justify-content-center gap-1">

                                                                <a href="aset_pindah_edit.php?id=<?= $r['id_riwayat'] ?>"
                                                                    class="btn btn-warning btn-sm px-2"
                                                                    title="Edit">

                                                                    <i class="bi bi-pencil-square"></i>

                                                                </a>

                                                                <a href="aset_pindah_hapus.php?id=<?= $r['id_riwayat'] ?>"
                                                                    class="btn btn-danger btn-sm px-2"
                                                                    title="Hapus"
                                                                    onclick="return confirm('Yakin ingin menghapus data ini?')">

                                                                    <i class="bi bi-trash"></i>

                                                                </a>

                                                            </div>

                                                        </td>

                                                    </tr>

                                                <?php endforeach; ?>

                                            <?php endif; ?>

                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>