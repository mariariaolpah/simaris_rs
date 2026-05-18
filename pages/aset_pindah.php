<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// ================= SIMPAN DATA PINDAH =================
if (isset($_POST['simpan_pindah'])) {
    $id_aset = (int)$_POST['id_aset'];
    $lokasi_baru = mysqli_real_escape_string($koneksi, $_POST['lokasi_baru']);
    $tanggal_pindah = mysqli_real_escape_string($koneksi, $_POST['tanggal_pindah']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // Cari lokasi sebelumnya dari tabel aset
    $queryAset = mysqli_query($koneksi, "SELECT lokasi FROM aset WHERE id_aset = $id_aset");
    $dataAset = mysqli_fetch_assoc($queryAset);
    $lokasi_sebelumnya = $dataAset['lokasi'];

    if ($lokasi_sebelumnya == $lokasi_baru) {
        echo "<script>alert('Lokasi baru tidak boleh sama dengan lokasi saat ini!');</script>";
    } else {
        // 1. Masukkan ke tabel riwayat_lokasi
        mysqli_query($koneksi, "INSERT INTO riwayat_lokasi (id_aset, lokasi_sebelumnya, lokasi_baru, tanggal_pindah, keterangan) 
                                VALUES ('$id_aset', '$lokasi_sebelumnya', '$lokasi_baru', '$tanggal_pindah', '$keterangan')");

        // 2. Update lokasi terkini di tabel aset
        mysqli_query($koneksi, "UPDATE aset SET lokasi = '$lokasi_baru' WHERE id_aset = $id_aset");

        echo "<script>alert('Berhasil! Lokasi aset telah dipindah dan riwayat dicatat.');window.location='aset_pindah.php';</script>";
    }
}

// ================= AMBIL DATA MASTER =================
// Ambil daftar aset
$aset_list = [];
$aset_query = mysqli_query($koneksi, "SELECT id_aset, nama_aset, lokasi FROM aset ORDER BY nama_aset ASC");
while ($row = mysqli_fetch_assoc($aset_query)) {
    $aset_list[] = $row;
}

// Ambil daftar lokasi
$lokasi_list = [];
$lokasi_query = mysqli_query($koneksi, "SELECT * FROM lokasi_aset ORDER BY nama_lokasi ASC");
while ($row = mysqli_fetch_assoc($lokasi_query)) {
    $lokasi_list[] = $row;
}

// Ambil data riwayat (Join dengan tabel aset)
$riwayat_list = [];
$riwayat_query = mysqli_query($koneksi, "SELECT r.*, a.nama_aset, a.kategori_aset 
                                         FROM riwayat_lokasi r 
                                         JOIN aset a ON r.id_aset = a.id_aset 
                                         ORDER BY r.id_riwayat DESC LIMIT 20");
while ($row = mysqli_fetch_assoc($riwayat_query)) {
    $riwayat_list[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Mutasi & Pelacakan Lokasi | SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #page-content-wrapper {
            flex: 1;
            overflow-x: hidden;
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
            padding: 30px;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
        }

        table td,
        table th {
            vertical-align: middle !important;
        }
    </style>
</head>

<body>

    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h3 class="fw-bold">PELACAKAN LOKASI ASET (MOBILITAS)</h3>
                <div><i class="bi bi-person-circle"></i> <?= $_SESSION['nama_pengguna']; ?></div>
            </div>

            <div class="content row">

                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header"><i class="bi bi-arrow-left-right"></i> Form Pindah Ruangan</div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Pilih Aset (Barang)</label>
                                    <select name="id_aset" class="form-select" required>
                                        <option value="">-- Pilih Barang --</option>
                                        <?php foreach ($aset_list as $a): ?>
                                            <option value="<?= $a['id_aset'] ?>">
                                                <?= htmlspecialchars($a['nama_aset']) ?> (Skrg: <?= htmlspecialchars($a['lokasi']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Pindah Ke Lokasi Baru</label>
                                    <select name="lokasi_baru" class="form-select" required>
                                        <option value="">-- Pilih Ruangan / Lokasi --</option>
                                        <?php foreach ($lokasi_list as $lok): ?>
                                            <option value="<?= $lok['nama_lokasi'] ?>"><?= $lok['nama_lokasi'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tanggal Pindah</label>
                                    <input type="date" name="tanggal_pindah" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Keterangan / Keperluan</label>
                                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Dipinjam darurat untuk pasien kamar 302..." required></textarea>
                                </div>

                                <button type="submit" name="simpan_pindah" class="btn btn-success w-100 fw-bold">
                                    <i class="bi bi-cursor-fill"></i> Pindahkan Aset
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header"><i class="bi bi-clock-history"></i> Riwayat Pergerakan Barang (Log)</div>
                        <div class="card-body p-0 table-responsive">
                            <table class="table table-bordered table-hover text-center mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nama Aset</th>
                                        <th>Kategori</th>
                                        <th>Lokasi Awal <i class="bi bi-arrow-right"></i> Baru</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($riwayat_list)): ?>
                                        <tr>
                                            <td colspan="5">Belum ada riwayat pergerakan aset.</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($riwayat_list as $r): ?>
                                        <tr>
                                            <td><?= date('d-m-Y', strtotime($r['tanggal_pindah'])) ?></td>
                                            <td class="fw-bold text-start"><?= htmlspecialchars($r['nama_aset']) ?></td>
                                            <td>
                                                <?= ($r['kategori_aset'] == 'Medis') ? '<span class="badge bg-danger">Medis</span>' : '<span class="badge bg-primary">Non-Medis</span>' ?>
                                            </td>
                                            <td>
                                                <span class="text-danger text-decoration-line-through"><?= htmlspecialchars($r['lokasi_sebelumnya']) ?></span>
                                                <br> <i class="bi bi-arrow-down"></i> <br>
                                                <span class="text-success fw-bold"><?= htmlspecialchars($r['lokasi_baru']) ?></span>
                                            </td>
                                            <td class="text-start small"><?= htmlspecialchars($r['keterangan']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>