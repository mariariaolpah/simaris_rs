<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

if (isset($_POST['simpan'])) {
    $nama_aset = mysqli_real_escape_string($koneksi, $_POST['nama_aset']);
    $kategori_aset = mysqli_real_escape_string($koneksi, $_POST['kategori_aset']);
    $jenis = mysqli_real_escape_string($koneksi, $_POST['jenis']);
    $tipe_aset = mysqli_real_escape_string($koneksi, $_POST['tipe_aset']);
    $lokasi = mysqli_real_escape_string($koneksi, $_POST['lokasi']);
    $asal_usul = mysqli_real_escape_string($koneksi, $_POST['asal_usul']);
    $harga = (int)$_POST['harga'];
    $umur_ekonomis = (int)$_POST['umur_ekonomis'];
    $tanggal_masuk = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk']);

    // Tangkap data rincian stok
    $stok_tersedia = (int)$_POST['stok_tersedia'];
    $stok_rusak = (int)$_POST['stok_rusak'];
    $stok_perawatan = (int)$_POST['stok_perawatan'];

    // Hitung total stok otomatis
    $total_stok = $stok_tersedia + $stok_rusak + $stok_perawatan;

    // Upload Dokumen
    $dokumen = "";
    if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] == 0) {
        $file_name = time() . '_' . $_FILES['dokumen']['name'];
        $tmp_name = $_FILES['dokumen']['tmp_name'];
        $folder = __DIR__ . '/../assets/dokumen/';
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        move_uploaded_file($tmp_name, $folder . $file_name);
        $dokumen = $file_name;
    }

    // Perhatikan: Kolom 'kondisi' tidak kita insert lagi, diganti dengan rincian stok
    $query = "INSERT INTO aset (nama_aset, kategori_aset, jenis, tipe_aset, lokasi, asal_usul, harga, umur_ekonomis, tanggal_masuk, dokumen, total_stok, stok_tersedia, stok_rusak, stok_perawatan) 
              VALUES ('$nama_aset', '$kategori_aset', '$jenis', '$tipe_aset', '$lokasi', '$asal_usul', '$harga', '$umur_ekonomis', '$tanggal_masuk', '$dokumen', '$total_stok', '$stok_tersedia', '$stok_rusak', '$stok_perawatan')";

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Data aset berhasil ditambahkan!'); window.location='aset.php';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan data! Pastikan kolom stok_tersedia dll sudah ada di database.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Aset | SIMARIS RS</title>
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
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
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

        .stok-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>
        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-plus-circle"></i> TAMBAH ASET BARU</h4>
            </div>
            <div class="content">
                <div class="card">
                    <div class="card-header"><i class="bi bi-file-earmark-plus"></i> Form Input Data Aset</div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nama Aset / Alat</label>
                                    <input type="text" name="nama_aset" class="form-control" required placeholder="Contoh: Mesin EKG">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Kategori</label>
                                    <select name="kategori_aset" class="form-select" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <option value="Medis">Medis (Alat Kesehatan)</option>
                                        <option value="Non-Medis">Non-Medis (Infrastruktur Umum)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Jenis Aset</label>
                                    <input type="text" name="jenis" class="form-control" required placeholder="Contoh: Alat Diagnostik">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tipe / Merek / Spesifikasi</label>
                                    <input type="text" name="tipe_aset" class="form-control" required placeholder="Contoh: Philips PageWriter TC20">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Lokasi Ruangan</label>
                                    <select name="lokasi" class="form-select" required>
                                        <option value="">-- Pilih Ruangan --</option>
                                        <?php
                                        $q_lokasi = mysqli_query($koneksi, "SELECT * FROM lokasi_aset ORDER BY nama_lokasi ASC");
                                        while ($lok = mysqli_fetch_assoc($q_lokasi)):
                                        ?>
                                            <option value="<?= htmlspecialchars($lok['nama_lokasi']) ?>"><?= htmlspecialchars($lok['nama_lokasi']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Asal Usul Perolehan</label>
                                    <select name="asal_usul" class="form-select" required>
                                        <option value="Pembelian">Pembelian (Dana RS)</option>
                                        <option value="Hibah">Hibah / Bantuan</option>
                                        <option value="Sewa">Sewa / Pinjam Pakai</option>
                                    </select>
                                </div>

                                <div class="col-12 mb-4">
                                    <div class="stok-box">
                                        <p class="mb-2 fw-bold text-dark"><i class="bi bi-box-seam"></i> Rincian Ketersediaan Barang (Stok)</p>
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label text-success fw-bold">Tersedia (Bisa Dipinjam)</label>
                                                <input type="number" name="stok_tersedia" class="form-control border-success" required value="1" min="0">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label text-danger fw-bold">Rusak</label>
                                                <input type="number" name="stok_rusak" class="form-control border-danger" required value="0" min="0">
                                            </div>
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label text-warning text-dark fw-bold">Sedang Perawatan</label>
                                                <input type="number" name="stok_perawatan" class="form-control border-warning" required value="0" min="0">
                                            </div>
                                            <div class="col-12 mt-1">
                                                <small class="text-muted">*Sistem akan menjumlahkan angka di atas menjadi Total Keseluruhan Stok.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Harga (Rp)</label>
                                    <input type="number" name="harga" class="form-control" required placeholder="Contoh: 15000000">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Umur (Tahun)</label>
                                    <input type="number" name="umur_ekonomis" class="form-control" required placeholder="Contoh: 5">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tanggal Masuk / Pembelian</label>
                                    <input type="date" name="tanggal_masuk" class="form-control" required>
                                </div>

                                <div class="col-12 mb-4">
                                    <label class="form-label">Upload Dokumen (Opsional - PDF/JPG)</label>
                                    <input type="file" name="dokumen" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <a href="aset.php" class="btn btn-secondary px-4">Batal</a>
                                <button type="submit" name="simpan" class="btn btn-success px-4 fw-bold">Simpan Aset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>