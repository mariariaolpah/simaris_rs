<?php
session_start();

// 1. Cek apakah pengguna sudah login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Cek apakah 'level' sudah di-set dan apakah dia admin
if (isset($_SESSION['level']) && strtolower(trim($_SESSION['level'])) == 'admin') {
    header("Location: dashboard.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// DI SINI PERUBAHANNYA: DESC diganti jadi ASC agar data pertama (Mesin EKG) muncul paling atas
$aset = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY id_aset ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Informasi Data Aset | Pegawai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Poppins', sans-serif;
        }

        #page-content-wrapper {
            margin-left: 230px;
            padding: 25px 35px;
        }

        .dashboard-header {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff;
            padding: 20px 30px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .badge-status {
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: bold;
            display: inline-block;
        }

        .baik {
            background-color: #198754;
            color: white;
        }

        .rusak {
            background-color: #dc3545;
            color: white;
        }

        .rawat {
            background-color: #ffc107;
            color: black;
        }

        tr:hover {
            background-color: #d4f3e4 !important;
            cursor: pointer;
        }

        table.table thead tr th {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a) !important;
            color: #fff !important;
            border-color: #1cc88a !important;
            vertical-align: middle;
            white-space: nowrap;
        }

        table.table tbody tr td {
            vertical-align: middle;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/sidebar_user.php'; ?>

    <div id="page-content-wrapper">

        <div class="dashboard-header">
            <div>
                <h4 class="fw-bold m-0"><i class="bi bi-box-seam"></i> INFORMASI DATA ASET</h4>
                <small class="text-light">Berikut daftar rincian aset yang tersedia di sistem saat ini.</small>
            </div>
            <div>
                <i class="bi bi-person-badge"></i> Pegawai
            </div>
        </div>

        <div class="d-flex gap-2 mb-3">
            <input type="text" id="searchInput" class="form-control"
                placeholder="Cari nama, kategori, tipe, atau lokasi..." style="flex: 2; padding:10px; font-size:14px;">

            <select id="filterKondisi" class="form-select"
                style="flex: 1.2; padding:10px; font-size:14px;">
                <option value="">Semua Kondisi</option>
                <option value="Baik">Baik</option>
                <option value="Rusak">Rusak</option>
                <option value="Perlu Perawatan">Perlu Perawatan</option>
            </select>

            <button class="btn btn-success"
                onclick="resetFilter()"
                style="flex: 0.5; padding:10px 14px; font-size:14px;">
                <i class="bi bi-arrow-clockwise"></i>
            </button>

            <a href="cetak_user.php" target="_blank"
                class="btn btn-danger"
                style="flex: 0.8; padding:10px 14px; font-size:14px;">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <table class="table table-bordered mb-0 text-center" id="tabelAset">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">Nama Aset</th>
                            <th width="15%">Kategori</th>
                            <th width="15%">Tipe / Spesifikasi</th>
                            <th width="20%">Lokasi Ruangan</th>
                            <th width="10%">Total Stok</th>
                            <th width="15%">Rincian Ketersediaan</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        // Nomor urut normal dimulai dari 1
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($aset)):
                            $kondisi = $row['kondisi'] ?? '';
                            $badgeClass = ($kondisi == "Baik" ? "baik" : ($kondisi == "Rusak" ? "rusak" : "rawat"));
                        ?>
                            <tr onclick="showDetail('<?= htmlspecialchars($row['nama_aset']) ?>','<?= htmlspecialchars($row['kategori_aset'] ?? '-') ?>','<?= htmlspecialchars($row['tipe_aset'] ?? '-') ?>','<?= htmlspecialchars($row['lokasi'] ?? '-') ?>','<?= htmlspecialchars($row['total_stok'] ?? '0') ?>','<?= htmlspecialchars($kondisi) ?>')">
                                <td><?= $no++; ?></td>
                                <td class="text-start fw-bold text-dark"><?= htmlspecialchars($row['nama_aset']); ?></td>
                                <td><?= htmlspecialchars($row['kategori_aset'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($row['tipe_aset'] ?? '-'); ?></td>
                                <td class="text-start"><i class="bi bi-geo-alt text-danger"></i> <?= htmlspecialchars($row['lokasi'] ?? '-'); ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['total_stok'] ?? '0'); ?></td>
                                <td><span class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($kondisi != '' ? $kondisi : '-'); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header" style="background: linear-gradient(90deg, #2c7a7b, #1cc88a); color: white; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-info-circle"></i> Detail Informasi Aset</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent" style="font-size: 15px; line-height: 1.8;"></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function showDetail(nama, kategori, tipe, lokasi, total_stok, kondisi) {
            document.getElementById("modalContent").innerHTML =
                `<table class="table table-sm table-borderless mb-0">
                    <tr><td width="38%"><b>Nama Aset</b></td><td width="5%">:</td><td class="fw-bold text-dark">${nama}</td></tr>
                    <tr><td><b>Kategori</b></td><td>:</td><td>${kategori}</td></tr>
                    <tr><td><b>Tipe / Spesifikasi</b></td><td>:</td><td>${tipe}</td></tr>
                    <tr><td><b>Lokasi Ruangan</b></td><td>:</td><td>${lokasi}</td></tr>
                    <tr><td><b>Total Stok</b></td><td>:</td><td class="fw-bold">${total_stok}</td></tr>
                    <tr><td><b>Rincian Kondisi</b></td><td>:</td><td><b>${kondisi}</b></td></tr>
                 </table>`;
            new bootstrap.Modal(document.getElementById("detailModal")).show();
        }

        const searchInput = document.getElementById("searchInput");
        const filterKondisi = document.getElementById("filterKondisi");
        const table = document.getElementById("tabelAset");
        const trs = table.getElementsByTagName("tr");

        function applyFilter() {
            let search = searchInput.value.toLowerCase();
            let kondisi = filterKondisi.value;

            for (let i = 1; i < trs.length; i++) {
                let tdNama = trs[i].children[1].textContent.toLowerCase();
                let tdKategori = trs[i].children[2].textContent.toLowerCase();
                let tdTipe = trs[i].children[3].textContent.toLowerCase();
                let tdLokasi = trs[i].children[4].textContent.toLowerCase();
                let tdKondisi = trs[i].children[6].textContent;

                let matchesSearch = tdNama.includes(search) ||
                    tdKategori.includes(search) ||
                    tdTipe.includes(search) ||
                    tdLokasi.includes(search);

                let matchesKondisi = (kondisi === "" || tdKondisi === kondisi);

                trs[i].style.display = (matchesSearch && matchesKondisi) ? "" : "none";
            }
        }

        searchInput.addEventListener("keyup", applyFilter);
        filterKondisi.addEventListener("change", applyFilter);

        function resetFilter() {
            searchInput.value = "";
            filterKondisi.value = "";
            applyFilter();
        }
    </script>

</body>

</html>