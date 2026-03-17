<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

if (isset($_POST['simpan'])) {
    $id_aset     = intval($_POST['id_aset']); // gunakan id_aset
    $status      = mysqli_real_escape_string($koneksi, $_POST['status']);
    $tanggal     = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $keterangan  = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // Ambil nama_aset dari id_aset
    $aset_query = mysqli_query($koneksi, "SELECT nama_aset FROM aset WHERE id_aset=$id_aset LIMIT 1");
    $aset_row   = mysqli_fetch_assoc($aset_query);
    $nama_aset  = $aset_row['nama_aset'];

    // Simpan data kerusakan
    mysqli_query(
        $koneksi,
        "INSERT INTO kerusakan (nama_aset, status, tanggal, keterangan) 
        VALUES ('$nama_aset','$status','$tanggal','$keterangan')"
    );

    // Update kondisi aset berdasarkan status kerusakan
    if ($status == "Rusak") {
        $kondisi = "Rusak";
    } elseif ($status == "Perlu Perawatan" || $status == "Dalam Perbaikan") {
        $kondisi = "Perlu Perawatan";
    } elseif ($status == "Selesai Diperbaiki") {
        $kondisi = "Baik";
    }

    // Update aset berdasarkan id_aset
    mysqli_query($koneksi, "UPDATE aset SET kondisi='$kondisi' WHERE id_aset=$id_aset LIMIT 1");

    echo "<script>alert('Data kerusakan berhasil disimpan & kondisi aset diperbarui');window.location='kerusakan.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Kerusakan | SIMARIS RS Bhayangkara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.2rem;
            padding: 15px 20px;
        }

        .btn-success {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            border: none;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 10px 12px;
        }

        .container-form {
            width: 100%;
            max-width: 550px;
            margin: 30px auto;
        }
    </style>
</head>

<body>
    <div class="container-form">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Tambah Data Kerusakan
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Nama Aset</label>
                        <select name="id_aset" class="form-select" required>
                            <option value="">-- Pilih Aset --</option>
                            <?php
                            $q = mysqli_query($koneksi, "SELECT id_aset, nama_aset FROM aset ORDER BY nama_aset");
                            while ($row = mysqli_fetch_assoc($q)) {
                                echo "<option value='" . $row['id_aset'] . "'>" . htmlspecialchars($row['nama_aset']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="Rusak">Rusak</option>
                            <option value="Perlu Perawatan">Perlu Perawatan</option>
                            <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                            <option value="Selesai Diperbaiki">Selesai Diperbaiki</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" name="simpan" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                        <a href="kerusakan.php" class="btn btn-secondary px-4"><i class="bi bi-x-circle"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>