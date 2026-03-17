<?php
session_start();
include(__DIR__ . '/../config/koneksi.php');

$id = intval($_GET['id']);
$data = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_pinjam = $id");
$row = mysqli_fetch_assoc($data);
$aset_query = mysqli_query($koneksi, "SELECT * FROM aset WHERE kondisi = 'Baik' ORDER BY nama_aset ASC");

if (isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_peminjam']);
    $tgl = $_POST['tanggal_pinjam'];
    $id_aset = $_POST['id_aset'];

    mysqli_query($koneksi, "UPDATE peminjaman SET nama_peminjam='$nama', tanggal_pinjam='$tgl', id_aset='$id_aset' WHERE id_pinjam=$id");
    echo "<script>alert('Data berhasil diupdate');window.location='peminjaman.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Peminjaman | SIMARIS</title>
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
            width: 420px;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        /* WARNA HIJAU DISAMAKAN DENGAN FORM TAMBAH */
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
        <div class="card-header text-center text-uppercase">Edit Data Peminjaman</div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nama Peminjam</label>
                    <input type="text" name="nama_peminjam" class="form-control" value="<?= htmlspecialchars($row['nama_peminjam']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Pilih Alat/Aset</label>
                    <select name="id_aset" class="form-select" required>
                        <?php while ($a = mysqli_fetch_assoc($aset_query)): ?>
                            <option value="<?= $a['id_aset'] ?>" <?= $a['id_aset'] == $row['id_aset'] ? 'selected' : '' ?>>
                                <?= $a['nama_aset'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal Pinjam</label>
                    <input type="date" name="tanggal_pinjam" class="form-control" value="<?= $row['tanggal_pinjam'] ?>" required>
                </div>
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" name="update" class="btn btn-success">Update Simpan</button>
                    <a href="peminjaman.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>