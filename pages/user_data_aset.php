<?php
session_start();
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['level'] != 'user') {
    header("Location: dashboard.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

$aset = mysqli_query($koneksi, "SELECT * FROM aset ORDER BY id_aset DESC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Aset | User</title>
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

        .badge-status {
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: bold;
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
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/sidebar_user.php'; ?>

    <div id="page-content-wrapper">

        <h4 class="fw-bold text-dark">Data Aset User</h4>
        <p class="text-muted">Berikut daftar aset yang tersedia di sistem.</p>

        <!-- SEARCH + FILTER + PDF - SUDAH DIPERBAIKI -->
        <!-- FILTER -->
        <div class="d-flex gap-2 mb-3">
            <!-- Input Pencarian -->
            <input type="text" id="searchInput" class="form-control"
                placeholder="Cari aset..." style="flex: 2; padding:10px; font-size:14px;">

            <!-- Dropdown Kondisi -->
            <select id="filterKondisi" class="form-select"
                style="flex: 1.2; padding:10px; font-size:14px;">
                <option value="">Semua Kondisi</option>
                <option value="Baik">Baik</option>
                <option value="Rusak">Rusak</option>
                <option value="Perlu Perawatan">Perlu Perawatan</option>
            </select>

            <!-- Dropdown Jenis -->
            <select id="filterJenis" class="form-select"
                style="flex: 1.2; padding:10px; font-size:14px;">
                <option value="">Semua Jenis</option>
                <option value="elektronik">Elektronik</option>
                <option value="furniture">Furniture</option>
            </select>

            <!-- Tombol Reset -->
            <button class="btn btn-success"
                onclick="resetFilter()"
                style="flex: 0.5; padding:10px 14px; font-size:14px;">
                <i class="bi bi-arrow-clockwise"></i>
            </button>

            <!-- Tombol PDF -->
            <a href="cetak_user.php" target="_blank"
                class="btn btn-danger"
                style="flex: 0.8; padding:10px 14px; font-size:14px;">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </a>
        </div>

        <!-- TABLE -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-bordered mb-0" id="tabelAset">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Aset</th>
                            <th>Jenis</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $no = 1;
                        while ($row = mysqli_fetch_assoc($aset)):
                            $badgeClass = ($row['kondisi'] == "Baik" ? "baik" : ($row['kondisi'] == "Rusak" ? "rusak" : "rawat"));
                        ?>
                            <tr onclick="showDetail('<?= $row['nama_aset'] ?>','<?= $row['jenis'] ?>','<?= $row['lokasi'] ?>','<?= $row['kondisi'] ?>')">
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_aset']); ?></td>
                                <td><?= htmlspecialchars($row['jenis']); ?></td>
                                <td><?= htmlspecialchars($row['lokasi']); ?></td>
                                <td><span class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($row['kondisi']); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    <!-- MODAL DETAIL -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Aset</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent"></div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function showDetail(nama, jenis, lokasi, kondisi) {
            document.getElementById("modalContent").innerHTML =
                `<b>Nama:</b> ${nama}<br>
         <b>Jenis:</b> ${jenis}<br>
         <b>Lokasi:</b> ${lokasi}<br>
         <b>Kondisi:</b> ${kondisi}`;
            new bootstrap.Modal(document.getElementById("detailModal")).show();
        }

        const searchInput = document.getElementById("searchInput");
        const filterKondisi = document.getElementById("filterKondisi");
        const filterJenis = document.getElementById("filterJenis");
        const table = document.getElementById("tabelAset");
        const trs = table.getElementsByTagName("tr");

        function applyFilter() {
            let search = searchInput.value.toLowerCase();
            let kondisi = filterKondisi.value;
            let jenis = filterJenis.value;

            for (let i = 1; i < trs.length; i++) {
                let tdNama = trs[i].children[1].textContent.toLowerCase();
                let tdJenis = trs[i].children[2].textContent.toLowerCase();
                let tdLokasi = trs[i].children[3].textContent.toLowerCase();
                let tdKondisi = trs[i].children[4].textContent;

                trs[i].style.display = (
                    (tdNama.includes(search) || tdJenis.includes(search) || tdLokasi.includes(search)) &&
                    (kondisi === "" || tdKondisi === kondisi) &&
                    (jenis === "" || tdJenis === jenis)
                ) ? "" : "none";
            }
        }

        searchInput.addEventListener("keyup", applyFilter);
        filterKondisi.addEventListener("change", applyFilter);
        filterJenis.addEventListener("change", applyFilter);

        function resetFilter() {
            searchInput.value = "";
            filterKondisi.value = "";
            filterJenis.value = "";
            applyFilter();
        }
    </script>

</body>

</html>