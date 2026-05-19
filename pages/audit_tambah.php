<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Ambil semua aset untuk keperluan pemeriksaan audit (semua kondisi boleh diaudit)
$aset_query = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY nama_aset ASC");

if (isset($_POST['simpan'])) {
    $id_aset = $_POST['id_aset'];
    $auditor = mysqli_real_escape_string($koneksi, $_POST['auditor']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $tanggal_input = $_POST['tanggal_audit'];
    $kondisi_input = $_POST['kondisi_fisik'];

    // OPSI 1: Mencoba insert dengan nama kolom standar skripsi
    $query1 = "INSERT INTO audit_fisik (id_aset, tanggal_audit, auditor, kondisi_fisik, keterangan) 
               VALUES ('$id_aset', '$tanggal_input', '$auditor', '$kondisi_input', '$keterangan')";

    if (@mysqli_query($koneksi, $query1)) {
        echo "<script>alert('Data audit fisik berhasil ditambahkan!'); window.location='audit_fisik.php';</script>";
    } else {
        // OPSI 2: Jika Opsi 1 gagal karena nama kolom berbeda, coba gunakan nama kolom alternatif ini
        $query2 = "INSERT INTO audit_fisik (id_aset, tanggal, auditor, kondisi, keterangan) 
                   VALUES ('$id_aset', '$tanggal_input', '$auditor', '$kondisi_input', '$keterangan')";

        if (mysqli_query($koneksi, $query2)) {
            echo "<script>alert('Data audit fisik berhasil ditambahkan!'); window.location='audit_fisik.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan data! Periksa kembali kesesuaian nama kolom tabel audit_fisik Anda.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Audit Fisik | SIMARIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
            color: #333;
            font-family: 'Poppins', sans-serif;
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
            background: #fff;
        }

        .card-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 15px 20px;
            border-top-left-radius: 12px !important;
            border-top-right-radius: 12px !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #2c7a7b;
            box-shadow: 0 0 0 0.2rem rgba(44, 122, 123, 0.25);
        }

        .highlight-audit {
            background-color: #fffdf5;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-clipboard-check"></i> PEMERIKSAAN FISIK ASET</h4>
                <div class="small fw-medium">
                    <i class="bi bi-person-circle-fill"></i> <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                </div>
            </div>

            <div class="content">
                <div class="card" style="max-width: 750px; margin: 0 auto;">
                    <div class="card-header">
                        <i class="bi bi-file-earmark-plus-fill"></i>
                        <span>Form Rekam Hasil Audit Lapangan</span>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST">

                            <div class="mb-4">
                                <label class="form-label">Pilih Aset / Alat yang Diperiksa</label>
                                <select name="id_aset" class="form-select" required>
                                    <option value="">-- Pilih Alat Kesehatan / Infrastruktur RS --</option>
                                    <?php while ($a = mysqli_fetch_assoc($aset_query)) : ?>
                                        <option value="<?= $a['id_aset'] ?>">
                                            <?= htmlspecialchars($a['nama_aset']) ?> — (📍 Ruangan: <?= htmlspecialchars($a['lokasi']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="form-text text-muted">Pilih item inventaris rumah sakit yang sedang dilakukan pengecekan kondisi riil.</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Tanggal Pemeriksaan</label>
                                    <input type="date" name="tanggal_audit" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Nama Auditor / Pemeriksa</label>
                                    <input type="text" name="auditor" class="form-control" placeholder="Contoh: Tim Mutu RS / Nama Petugas" required>
                                </div>
                            </div>

                            <div class="highlight-audit">
                                <label class="form-label text-warning-dark fw-bold"><i class="bi bi-shield-exclamation"></i> Kondisi Fisik Riil Saat Ini</label>
                                <select name="kondisi_fisik" class="form-select border-warning mb-1" required>
                                    <option value="Baik">Baik (Berfungsi Normal)</option>
                                    <option value="Perlu Perawatan">Perlu Perawatan / Kalibrasi</option>
                                    <option value="Rusak">Rusak (Tidak Operasional)</option>
                                </select>
                                <small class="text-secondary">Pencatatan ini digunakan untuk membandingkan kecocokan status sistem dengan kondisi asli di lapangan.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Keterangan / Temuan Detail Lapangan</label>
                                <textarea name="text" class="form-control" rows="4" placeholder="Contoh: Alat menyala normal namun roda penyangga sebelah kanan agak longgar, perlu pengetatan sekrup." required></textarea>
                            </div>

                            <hr>

                            <div class="row g-2 mt-2">
                                <div class="col-sm-8">
                                    <button type="submit" name="simpan" class="btn btn-success w-100 py-2 fw-bold" style="border-radius: 8px;">
                                        <i class="bi bi-save-fill"></i> Simpan Catatan Audit Fisik
                                    </button>
                                </div>
                                <div class="col-sm-4">
                                    <a href="audit_fisik.php" class="btn btn-secondary w-100 py-2" style="border-radius: 8px;">
                                        Batal Kembali
                                    </a>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>