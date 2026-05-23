<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Pengecekan Role
$role_pengguna = isset($_SESSION['level']) ? strtolower($_SESSION['level']) : 'user';
$is_admin = ($role_pengguna == 'admin');

// Ambil seluruh daftar aset untuk pilihan pelaporan kerusakan
$aset_query = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY nama_aset ASC");

if (isset($_POST['simpan'])) {
    $id_aset     = intval($_POST['id_aset']);
    $status      = mysqli_real_escape_string($koneksi, $_POST['status']);
    $tanggal     = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $keterangan  = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $pelapor     = mysqli_real_escape_string($koneksi, $_POST['pelapor']);

    // Logika pengisian teknisi: Jika admin, ambil dari form. Jika user, otomatis '-'
    $teknisi = '-';
    if ($is_admin && isset($_POST['teknisi']) && trim($_POST['teknisi']) !== '') {
        $teknisi = mysqli_real_escape_string($koneksi, $_POST['teknisi']);
    }

    // Ambil nama_aset berdasarkan id_aset pilihan user
    $aset_find  = mysqli_query($koneksi, "SELECT nama_aset FROM aset WHERE id_aset=$id_aset LIMIT 1");
    $aset_row   = mysqli_fetch_assoc($aset_find);
    $nama_aset  = $aset_row['nama_aset'];

    // Simpan data ke tabel kerusakan (Termasuk kolom teknisi)
    $insert = mysqli_query($koneksi, "INSERT INTO kerusakan (nama_aset, status, tanggal, keterangan, pelapor, teknisi) 
                            VALUES ('$nama_aset','$status','$tanggal','$keterangan', '$pelapor', '$teknisi')");

    // [MODIFIKASI] Mengupdate rincian stok ketersediaan di master data secara otomatis
    if ($insert) {
        if ($status == "Rusak") {
            // Kurangi stok tersedia, dan tambah 1 ke stok rusak
            mysqli_query($koneksi, "UPDATE aset SET stok_tersedia = stok_tersedia - 1, stok_rusak = stok_rusak + 1 WHERE id_aset = $id_aset");
        } elseif ($status == "Perlu Perawatan" || $status == "Dalam Perbaikan") {
            // Kurangi stok tersedia, dan tambah 1 ke stok perawatan
            mysqli_query($koneksi, "UPDATE aset SET stok_tersedia = stok_tersedia - 1, stok_perawatan = stok_perawatan + 1 WHERE id_aset = $id_aset");
        }

        // Jika statusnya langsung "Selesai Diperbaiki" (walau jarang di awal), stok dibiarkan utuh

        echo "<script>alert('Laporan kerusakan aset berhasil dicatat & rincian stok diperbarui!');window.location='kerusakan.php';</script>";
    } else {
        echo "<script>alert('Gagal menyimpan laporan kerusakan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Pelaporan Kerusakan | SIMARIS</title>
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
            padding: 30px 30px 120px 30px;
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

        .highlight-danger {
            background-color: #fff5f5;
            border-left: 4px solid #e53e3e;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include(__DIR__ . '/../sidebar.php'); ?>

        <div id="page-content-wrapper">
            <div class="dashboard-header">
                <h4 class="fw-bold m-0"><i class="bi bi-exclamation-triangle"></i> LAPOR KERUSAKAN</h4>
                <div class="small fw-medium">
                    <i class="bi bi-person-circle-fill"></i> <?= htmlspecialchars($_SESSION['nama_pengguna']); ?>
                    <span class="badge bg-light text-dark ms-1"><?= strtoupper($role_pengguna); ?></span>
                </div>
            </div>

            <div class="content">
                <div class="card" style="max-width: 750px; margin: 0 auto;">
                    <div class="card-header">
                        <i class="bi bi-file-earmark-medical-fill"></i>
                        <span>Form Input Pengaduan Kerusakan Aset</span>
                    </div>

                    <div class="card-body p-4">
                        <form method="POST">

                            <div class="mb-4">
                                <label class="form-label">Pilih Aset / Alat yang Bermasalah</label>
                                <select name="id_aset" class="form-select" required>
                                    <option value="">-- Pilih Komponen Aset Rumah Sakit --</option>
                                    <?php while ($a = mysqli_fetch_assoc($aset_query)) : ?>
                                        <option value="<?= $a['id_aset'] ?>">
                                            <?= htmlspecialchars($a['nama_aset']) ?> — [ Kategori: <?= htmlspecialchars($a['kategori_aset']) ?> | 📍 Ruang: <?= htmlspecialchars($a['lokasi']) ?> ]
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Nama Pelapor</label>
                                <input type="text"
                                    name="pelapor"
                                    class="form-control <?= !$is_admin ? 'bg-light' : '' ?>"
                                    value="<?= htmlspecialchars($_SESSION['nama_pengguna']); ?>"
                                    <?= !$is_admin ? 'readonly' : '' ?>
                                    required>

                                <?php if ($is_admin): ?>
                                    <div class="form-text text-primary"><i class="bi bi-info-circle"></i> Anda login sebagai Admin. Anda bisa mengubah nama ini jika melaporkan atas nama orang lain.</div>
                                <?php else: ?>
                                    <div class="form-text text-muted"><i class="bi bi-lock-fill"></i> Nama pelapor otomatis dikunci sesuai akun Anda.</div>
                                <?php endif; ?>
                            </div>

                            <?php if ($is_admin): ?>
                                <div class="mb-4">
                                    <label class="form-label">Langsung Tugaskan Teknisi (Opsional)</label>
                                    <select name="teknisi" class="form-select border-primary">
                                        <option value="">-- Pilih Teknisi (Abaikan jika belum ada) --</option>
                                        <?php
                                        // Mengambil nama pengguna yang level/role-nya teknisi
                                        $query_teknisi = mysqli_query($koneksi, "SELECT nama_pengguna FROM pengguna WHERE level = 'teknisi' AND status = 'aktif'");
                                        while ($t = mysqli_fetch_assoc($query_teknisi)) {
                                            echo "<option value='" . htmlspecialchars($t['nama_pengguna']) . "'>" . htmlspecialchars($t['nama_pengguna']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text text-success"><i class="bi bi-check-circle"></i> Fitur khusus Admin: Nama teknisi otomatis diambil dari Data Pengguna.</div>
                                </div>
                            <?php endif; ?>

                            <div class="highlight-danger">
                                <h6 class="fw-bold text-danger mb-1"><i class="bi bi-shield-fill-exclamation"></i> Peringatan Respon Cepat RS</h6>
                                <small class="text-secondary d-block">Pelaporan kerusakan pada **Aset Medis (Alkes Vital)** akan langsung menarik ketersediaan stok fisik untuk mencegah peminjaman alat yang tidak operasional.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Status Kondisi Laporan</label>
                                    <select name="status" class="form-select" required>
                                        <option value="">-- Pilih Tindakan --</option>
                                        <option value="Rusak">Rusak (Mati Total / Berat)</option>
                                        <option value="Perlu Perawatan">Perlu Perawatan / Malfungsi Ringan</option>

                                        <?php if ($is_admin): ?>
                                            <option value="Dalam Perbaikan">Dalam Perbaikan (Sedang Ditangani)</option>
                                            <option value="Selesai Diperbaiki">Selesai Diperbaiki</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label">Tanggal Pelaporan</label>
                                    <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Rincian Keluhan / Detail Kronologi Kerusakan</label>
                                <textarea name="keterangan" class="form-control" rows="4" placeholder="Contoh: Lampu indikator USR berkedip merah terus menerus dan tidak bisa mengeluarkan gambar sensor..." required></textarea>
                            </div>

                            <hr>

                            <div class="row g-2 mt-2">
                                <div class="col-sm-8">
                                    <button type="submit" name="simpan" class="btn btn-success w-100 py-2 fw-bold" style="border-radius: 8px;">
                                        <i class="bi bi-save-fill"></i> Daftarkan Laporan Kerusakan
                                    </button>
                                </div>
                                <div class="col-sm-4">
                                    <a href="kerusakan.php" class="btn btn-secondary w-100 py-2" style="border-radius: 8px;">
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