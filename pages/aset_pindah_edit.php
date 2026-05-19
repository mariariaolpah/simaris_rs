<?php
session_start();
include(__DIR__ . '/../config/koneksi.php');

$id = $_GET['id'];

if (isset($_POST['update'])) {

    $lokasi_baru = mysqli_real_escape_string($koneksi, $_POST['lokasi_baru']);
    $tanggal_pindah = mysqli_real_escape_string($koneksi, $_POST['tanggal_pindah']);
    $penanggung_jawab = mysqli_real_escape_string($koneksi, $_POST['penanggung_jawab']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    mysqli_query($koneksi, "
        UPDATE riwayat_lokasi 
        SET
            lokasi_baru='$lokasi_baru',
            tanggal_pindah='$tanggal_pindah',
            penanggung_jawab='$penanggung_jawab',
            keterangan='$keterangan'
        WHERE id_riwayat=$id
    ");

    echo "
    <script>
        alert('Data berhasil diupdate!');
        window.location='aset_pindah.php';
    </script>";
}

$data = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT r.*, a.nama_aset
    FROM riwayat_lokasi r
    JOIN aset a ON r.id_aset = a.id_aset
    WHERE r.id_riwayat=$id
"));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Riwayat Mutasi</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f4f7fa;
            font-family: Poppins, sans-serif;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: white;
            font-weight: bold;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0 !important;
        }

        .btn-save {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            border: none;
            color: white;
        }
    </style>

</head>

<body>

    <div class="container mt-5">

        <div class="card">

            <div class="card-header">
                Edit Riwayat Mutasi Aset
            </div>

            <div class="card-body">

                <form method="POST">

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Nama Aset
                        </label>

                        <input type="text"
                            class="form-control"
                            value="<?= $data['nama_aset'] ?>"
                            readonly>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Lokasi Sebelumnya
                        </label>

                        <input type="text"
                            class="form-control"
                            value="<?= $data['lokasi_sebelumnya'] ?>"
                            readonly>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Lokasi Baru
                        </label>

                        <input type="text"
                            name="lokasi_baru"
                            class="form-control"
                            value="<?= $data['lokasi_baru'] ?>"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Tanggal Pindah
                        </label>

                        <input type="date"
                            name="tanggal_pindah"
                            class="form-control"
                            value="<?= $data['tanggal_pindah'] ?>"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Penanggung Jawab
                        </label>

                        <input type="text"
                            name="penanggung_jawab"
                            class="form-control"
                            value="<?= $data['penanggung_jawab'] ?>"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Keterangan
                        </label>

                        <textarea name="keterangan"
                            class="form-control"
                            rows="4"
                            required><?= $data['keterangan'] ?></textarea>

                    </div>

                    <button type="submit"
                        name="update"
                        class="btn btn-save">

                        Simpan Perubahan

                    </button>

                    <a href="aset_pindah.php"
                        class="btn btn-secondary">

                        Kembali

                    </a>

                </form>

            </div>

        </div>

    </div>

</body>

</html>