<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$id = intval($_GET['id']);
// Ambil data audit yang lama
$data = mysqli_query($koneksi, "SELECT * FROM audit_fisik WHERE id_audit = $id");
$row = mysqli_fetch_assoc($data);

// Ambil daftar aset untuk pilihan dropdown
$aset_query = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY nama_aset ASC");

if (isset($_POST['update'])) {
    $id_aset = $_POST['id_aset'];
    $tgl = $_POST['tanggal_audit'];
    $kondisi = $_POST['kondisi_fisik'];
    $ket = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    $update = mysqli_query($koneksi, "UPDATE audit_fisik SET 
                                      id_aset='$id_aset', 
                                      tanggal_audit='$tgl', 
                                      kondisi_fisik='$kondisi', 
                                      keterangan='$ket' 
                                      WHERE id_audit=$id");

    if ($update) {
        echo "<script>alert('Data audit berhasil diperbarui!');window.location='audit_fisik.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Audit | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
        }

        .card {
            width: 450px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            padding: 15px;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
    </style>
</head>

<body>
    <div class="card shadow">
        <div class="card-header text-center text-uppercase">Edit Hasil Audit Fisik</div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Aset</label>
                    <select name="id_aset" class="form-select" required>
                        <?php while ($a = mysqli_fetch_assoc($aset_query)): ?>
                            <option value="<?= $a['id_aset'] ?>" <?= $a['id_aset'] == $row['id_aset'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nama_aset']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Kondisi Fisik Saat Ini</label>
                    <select name="kondisi_fisik" class="form-select">
                        <option value="Sesuai" <?= $row['kondisi_fisik'] == 'Sesuai' ? 'selected' : '' ?>>Sesuai (Baik)</option>
                        <option value="Rusak Ringan" <?= $row['kondisi_fisik'] == 'Rusak Ringan' ? 'selected' : '' ?>>Rusak Ringan</option>
                        <option value="Rusak Berat" <?= $row['kondisi_fisik'] == 'Rusak Berat' ? 'selected' : '' ?>>Rusak Berat</option>
                        <option value="Tidak Ditemukan" <?= $row['kondisi_fisik'] == 'Tidak Ditemukan' ? 'selected' : '' ?>>Tidak Ditemukan</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tanggal Audit</label>
                    <input type="date" name="tanggal_audit" class="form-control" value="<?= $row['tanggal_audit'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Keterangan Tambahan</label>
                    <textarea name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($row['keterangan']) ?></textarea>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="update" class="btn btn-success fw-bold">Update Simpan</button>
                    <a href="audit_fisik.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>