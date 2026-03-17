<?php
session_start();
include(__DIR__ . '/../config/koneksi.php');

$aset_query = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY nama_aset ASC");

if (isset($_POST['simpan'])) {
    $id_aset = $_POST['id_aset'];
    $tgl = $_POST['tanggal_audit'];
    $kondisi = $_POST['kondisi_fisik'];
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $auditor = $_SESSION['nama_pengguna'];

    mysqli_query($koneksi, "INSERT INTO audit_fisik (id_aset, tanggal_audit, kondisi_fisik, keterangan, auditor) 
                            VALUES ('$id_aset', '$tgl', '$kondisi', '$keterangan', '$auditor')");

    echo "<script>alert('Data audit berhasil disimpan!');window.location='audit_fisik.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Audit | SIMARIS</title>
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
    <div class="card">
        <div class="card-header text-center">FORM AUDIT FISIK BARU</div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Pilih Aset untuk Diaudit</label>
                    <select name="id_aset" class="form-select" required>
                        <option value="">-- Pilih Aset --</option>
                        <?php while ($a = mysqli_fetch_assoc($aset_query)) : ?>
                            <option value="<?= $a['id_aset'] ?>"><?= $a['nama_aset'] ?> (<?= $a['lokasi'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kondisi Fisik Saat Ini</label>
                    <select name="kondisi_fisik" class="form-select" required>
                        <option value="Sesuai">Sesuai (Kondisi Baik)</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                        <option value="Tidak Ditemukan">Alat Tidak Ditemukan</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Pengecekan</label>
                    <input type="date" name="tanggal_audit" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan Tambahan</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Kabel lecet, perlu kalibrasi, dll"></textarea>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="simpan" class="btn btn-success">Simpan Hasil Audit</button>
                    <a href="audit_fisik.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>