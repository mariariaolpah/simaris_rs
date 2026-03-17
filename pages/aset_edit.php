<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Ambil ID aset
$id = intval($_GET['id']);

// Ambil data aset (TANPA JOIN karena kolom lokasi = nama lokasi)
$data = mysqli_query($koneksi, "SELECT * FROM aset WHERE id_aset = $id");
$d = mysqli_fetch_assoc($data);

if (!$d) {
    echo "<script>alert('Data aset tidak ditemukan');window.location='aset.php';</script>";
    exit;
}

// Ambil daftar lokasi dari tabel lokasi_aset
$lokasi_list = [];
$lokasi_query = mysqli_query($koneksi, "SELECT * FROM lokasi_aset ORDER BY nama_lokasi ASC");
while ($row = mysqli_fetch_assoc($lokasi_query)) {
    $lokasi_list[] = $row;
}

// Simpan perubahan
if (isset($_POST['simpan'])) {

    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis']);
    $tipe_aset = mysqli_real_escape_string($koneksi, $_POST['tipe_aset']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']); // pakai NAMA lokasi, bukan ID
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi']);
    $tanggal = $_POST['tanggal_masuk'];

    mysqli_query($koneksi, "
        UPDATE aset SET 
            nama_aset = '$nama',
            jenis = '$jenis',
            tipe_aset = '$tipe_aset',
            lokasi = '$lokasi',
            kondisi = '$kondisi',
            tanggal_masuk = '$tanggal'
        WHERE id_aset = $id
    ");

    echo "<script>alert('Aset berhasil diubah');window.location='aset.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Aset | SIMARIS RS Bhayangkara</title>
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

        .container-form {
            width: 420px;
            max-width: 90%;
        }

        .form-control,
        .form-select {
            height: 42px;
            font-size: 0.9rem;
            border-radius: 8px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 16px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.15rem;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>

<body>

    <div class="container-form" style="margin-top: 30px;">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil-square"></i> Edit Aset / Infrastruktur
            </div>

            <div class="card-body">
                <form method="post">

                    <div class="mb-3">
                        <label class="form-label">Nama Aset</label>
                        <input type="text" name="nama_aset" class="form-control"
                            value="<?= htmlspecialchars($d['nama_aset']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <input type="text" name="jenis" class="form-control"
                            value="<?= htmlspecialchars($d['jenis']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipe Aset</label>
                        <input type="text" name="tipe_aset" class="form-control"
                            value="<?= htmlspecialchars($d['tipe_aset']) ?>" required>
                    </div>

                    <!-- LOKASI DROPDOWN -->
                    <div class="mb-3">
                        <label class="form-label">Lokasi</label>
                        <select name="lokasi" class="form-control" required>
                            <option value="">-- Pilih Lokasi --</option>
                            <?php foreach ($lokasi_list as $lok) {
                                $selected = ($d['lokasi'] == $lok['nama_lokasi']) ? "selected" : "";
                            ?>
                                <option value="<?= $lok['nama_lokasi'] ?>" <?= $selected ?>>
                                    <?= $lok['nama_lokasi'] ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kondisi</label>
                        <select name="kondisi" class="form-control" required>
                            <?php
                            $options = ['Baik', 'Perlu Perawatan', 'Rusak'];
                            foreach ($options as $opt) {
                                $selected = ($d['kondisi'] == $opt) ? 'selected' : '';
                                echo "<option value='$opt' $selected>$opt</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" class="form-control"
                            value="<?= date('Y-m-d', strtotime($d['tanggal_masuk'])) ?>" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" name="simpan" class="btn btn-success px-4">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                        <a href="aset.php" class="btn btn-secondary px-4">
                            <i class="bi bi-x-circle"></i> Batal
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

</body>

</html>