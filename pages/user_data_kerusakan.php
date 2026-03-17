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

// Ambil data kerusakan
$kerusakan = mysqli_query($koneksi, "SELECT * FROM kerusakan ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Kerusakan | User</title>
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

        table.table thead tr th {
            background: linear-gradient(90deg, #2c7a7b, #1cc88a);
            color: #fff !important;
            border-color: #1cc88a;
        }

        tr:hover {
            background-color: #d4f3e4 !important;
            cursor: pointer;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: bold;
        }

        /* Warna Status Baru */
        .rusak {
            background-color: #dc3545;
            /* Merah */
            color: white;
        }

        .baik {
            background-color: #28a745;
            /* Hijau */
            color: white;
        }

        .rawat,
        .pending,
        .proses {
            background-color: #ffc107;
            /* Kuning */
            color: black;
        }

        .selesai {
            background-color: #198754;
            /* Hijau Gelap */
            color: white;
        }
    </style>

</head>

<body>

    <?php include __DIR__ . '/sidebar_user.php'; ?>

    <div id="page-content-wrapper">

        <!-- JUDUL -->
        <h4 class="fw-bold text-dark">Laporan Kerusakan</h4>
        <p class="text-muted">Berikut daftar kerusakan aset yang anda laporkan.</p>

        <!-- SEARCH + FILTER + PDF -->
        <div class="card p-3 shadow-sm mb-3">
            <div class="row g-3 align-items-center">

                <!-- Kolom Pencarian (ukuran sama persis contoh) -->
                <div class="col-md-4">
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Cari laporan..."
                        style="flex: 2; padding:10px; font-size:14px;">
                </div>

                <!-- Kolom Filter (ukuran sama persis contoh) -->
                <div class="col-md-4">
                    <select id="filterStatus" class="form-select"
                        style="flex: 1.2; padding:10px; font-size:14px;">
                        <option value="">Semua Status</option>
                        <option value="Rusak">Rusak</option>
                        <option value="Baik">Baik</option>
                        <option value="Dalam Perawatan">Dalam Perawatan</option>
                        <option value="Pending">Pending</option>
                        <option value="Proses">Proses</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>

                <!-- Tombol Reset + PDF di kanan pojok -->
                <div class="col-md-4 d-flex justify-content-end gap-2">

                    <!-- Tombol Reset (ukuran sama persis contoh) -->
                    <button class="btn btn-success"
                        onclick="resetFilter()"
                        style="flex: 0.5; padding:10px 14px; font-size:14px;">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>

                    <!-- Tombol PDF (identik dengan contoh) -->
                    <a href="cetak_kerusakan_user.php" target="_blank"
                        class="btn btn-danger"
                        style="flex: 0.8; padding:10px 14px; font-size:14px;">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>


        <!-- TABLE -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-bordered mb-0" id="tabelKerusakan">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Aset</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($kerusakan)):

                            // Mapping Warna Status
                            switch ($row['status']) {
                                case "Rusak":
                                    $badgeClass = "rusak";
                                    break;
                                case "Baik":
                                    $badgeClass = "baik";
                                    break;
                                case "Dalam Perawatan":
                                    $badgeClass = "rawat";
                                    break;
                                case "Pending":
                                    $badgeClass = "pending";
                                    break;
                                case "Proses":
                                    $badgeClass = "proses";
                                    break;
                                case "Selesai":
                                    $badgeClass = "selesai";
                                    break;
                                default:
                                    $badgeClass = "pending";
                            }
                        ?>

                            <tr onclick="showDetail('<?= $row['nama_aset'] ?>', '<?= $row['tanggal'] ?>', '<?= $row['status'] ?>', '<?= $row['keterangan'] ?>')">
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['nama_aset']); ?></td>
                                <td><?= htmlspecialchars($row['tanggal']); ?></td>
                                <td><span class="badge-status <?= $badgeClass ?>"><?= htmlspecialchars($row['status']); ?></span></td>
                                <td><?= htmlspecialchars($row['keterangan']); ?></td>
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
                    <h5 class="modal-title">Detail Laporan</h5>
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
        function showDetail(aset, tanggal, status, ket) {
            document.getElementById("modalContent").innerHTML =
                `<b>Nama Aset:</b> ${aset}<br>
         <b>Tanggal:</b> ${tanggal}<br>
         <b>Status:</b> ${status}<br>
         <b>Keterangan:</b> ${ket}`;

            new bootstrap.Modal(document.getElementById("detailModal")).show();
        }

        const searchInput = document.getElementById("searchInput");
        const filterStatus = document.getElementById("filterStatus");
        const table = document.getElementById("tabelKerusakan");
        const trs = table.getElementsByTagName("tr");

        function applyFilter() {
            let search = searchInput.value.toLowerCase();
            let status = filterStatus.value;

            for (let i = 1; i < trs.length; i++) {
                let tdNama = trs[i].children[1].textContent.toLowerCase();
                let tdStatus = trs[i].children[3].textContent;

                trs[i].style.display =
                    (tdNama.includes(search) && (status === "" || tdStatus === status)) ?
                    "" : "none";
            }
        }

        searchInput.addEventListener("keyup", applyFilter);
        filterStatus.addEventListener("change", applyFilter);

        function resetFilter() {
            searchInput.value = "";
            filterStatus.value = "";
            applyFilter();
        }
    </script>

</body>

</html>