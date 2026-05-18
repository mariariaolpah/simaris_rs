<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = mysqli_query($koneksi, "SELECT * FROM aset WHERE id_aset = $id");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='aset.php';</script>";
    exit;
}

if (isset($_POST['update'])) {
    $nama_aset = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $kategori_aset = mysqli_real_escape_string($koneksi, $_POST['kategori_aset']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis']);
    $tipe_aset = mysqli_real_escape_string($koneksi, $_POST['tipe_aset']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi']);
    $asal_usul = mysqli_real_escape_string($koneksi, $_POST['asal_usul']);
    $harga = (int)$_POST['harga'];
    $umur_ekonomis = (int)$_POST['umur_ekonomis'];
    $tanggal_masuk = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk']);

    // Cek jika ada upload dokumen baru
    $dokumen_query = "";
    if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] == 0) {
        $file_name = time() . '_' . $_FILES['dokumen']['name'];
        $tmp_name = $_FILES['dokumen']['tmp_name'];
        $folder = __DIR__ . '/../assets/dokumen/';
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        move_uploaded_file($tmp_name, $folder . $file_name);

        // Hapus file lama jika ada
        if (!empty($data['dokumen']) && file_exists($folder . $data['dokumen'])) {
            unlink($folder . $data['dokumen']);
        }
        $dokumen_query = ", dokumen = '$file_name'";
    }

    $q_update = "UPDATE aset SET 
                nama_aset = '$nama_aset', kategori_aset = '$kategori_aset', 
                jenis = '$jenis', tipe_aset = '$tipe_aset', 
                lokasi = '$lokasi', kondisi = '$kondisi', 
                asal_usul = '$asal_usul', harga = '$harga', 
                umur_ekonomis = '$umur_ekonomis', tanggal_masuk = '$tanggal_masuk'
                $dokumen_query
                WHERE id_aset = $id";

    if (mysqli_query($koneksi, $q_update)) {
        echo "<script>alert('Data aset berhasil diperbarui!'); window.location='aset.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Aset | SIMARIS RS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #page-content-wrapper {
            flex: 1;
            max-width: 100%;
            overflow-x: hidden;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .content {
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: linear-gradient(90deg, #f59e0b, #d97706);
            color: #fff;
            font-weight: 600;
            padding: 15px 20px;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
        }

        .form-label {
            font-weight: 500;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 14px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>
        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-pencil-square"></i> EDIT DATA ASET</h4>
            </div>
            <div class="content">
                <div class="card">
                    <div class="card-header"><i class="bi bi-tools"></i> Form Perbarui Data Aset</div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Aset / Alat</label>
                                    <input type="text" name="nama_aset" class="form-control" required value="<?= htmlspecialchars($data['nama_aset']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select name="kategori_aset" class="form-select" required>
                                        <option value="Medis" <?= ($data['kategori_aset'] == 'Medis') ? 'selected' : '' ?>>Medis (Alat Kesehatan)</option>
                                        <option value="Non-Medis" <?= ($data['kategori_aset'] == 'Non-Medis') ? 'selected' : '' ?>>Non-Medis (Infrastruktur Umum)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jenis Aset</label>
                                    <input type="text" name="jenis" class="form-control" required value="<?= htmlspecialchars($data['jenis']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tipe / Merek / Spesifikasi</label>
                                    <input type="text" name="tipe_aset" class="form-control" required value="<?= htmlspecialchars($data['tipe_aset']) ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Lokasi Ruangan</label>
                                    <select name="lokasi" class="form-select" required>
                                        <option value="">-- Pilih Ruangan --</option>
                                        <?php
                                        $q_lokasi = mysqli_query($koneksi, "SELECT * FROM lokasi_aset ORDER BY nama_lokasi ASC");
                                        while ($lok = mysqli_fetch_assoc($q_lokasi)):
                                            $selected = ($data['lokasi'] == $lok['nama_lokasi']) ? 'selected' : '';
                                        ?>
                                            <option value="<?= htmlspecialchars($lok['nama_lokasi']) ?>" <?= $selected ?>><?= htmlspecialchars($lok['nama_lokasi']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kondisi</label>
                                    <select name="kondisi" class="form-select" required>
                                        <option value="Baik" <?= ($data['kondisi'] == 'Baik') ? 'selected' : '' ?>>Baik</option>
                                        <option value="Perlu Perawatan" <?= ($data['kondisi'] == 'Perlu Perawatan') ? 'selected' : '' ?>>Perlu Perawatan</option>
                                        <option value="Rusak" <?= ($data['kondisi'] == 'Rusak') ? 'selected' : '' ?>>Rusak</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Asal Usul Perolehan</label>
                                    <select name="asal_usul" class="form-select" required>
                                        <option value="Pembelian" <?= ($data['asal_usul'] == 'Pembelian') ? 'selected' : '' ?>>Pembelian (Dana RS)</option>
                                        <option value="Hibah" <?= ($data['asal_usul'] == 'Hibah') ? 'selected' : '' ?>>Hibah / Bantuan</option>
                                        <option value="Sewa" <?= ($data['asal_usul'] == 'Sewa') ? 'selected' : '' ?>>Sewa / Pinjam Pakai</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Harga Perolehan (Rp)</label>
                                    <input type="number" name="harga" class="form-control" required value="<?= $data['harga'] ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Umur Ekonomis (Tahun)</label>
                                    <input type="number" name="umur_ekonomis" class="form-control" required value="<?= $data['umur_ekonomis'] ?>">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Tanggal Masuk / Pembelian</label>
                                    <input type="date" name="tanggal_masuk" class="form-control" required value="<?= $data['tanggal_masuk'] ?>">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Upload Dokumen Baru (Abaikan jika tidak diganti)</label>
                                    <input type="file" name="dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                    <?php if (!empty($data['dokumen'])): ?>
                                        <small class="text-success mt-1 d-block"><i class="bi bi-check-circle"></i> File saat ini: <?= htmlspecialchars($data['dokumen']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="aset.php" class="btn btn-secondary px-4">Batal</a>
                                <button type="submit" name="update" class="btn btn-warning px-4 fw-bold text-white">Update Aset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>