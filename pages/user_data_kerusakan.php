<?php
session_start();

// 1. Cek apakah pengguna sudah login
if (!isset($_SESSION['id_pengguna'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Cek aman dengan isset() agar PHP tidak error saat session kosong
if (isset($_SESSION['level']) && strtolower(trim($_SESSION['level'])) == 'admin') {
    header("Location: dashboard.php");
    exit;
}

include(__DIR__ . '/../config/koneksi.php');

// Ambil data kerusakan dengan melakukan JOIN ke tabel aset untuk mendapatkan Lokasi dan Kategori
$query_kerusakan = "
    SELECT 
        kerusakan.*, 
        aset.kategori_aset, 
        aset.lokasi 
    FROM kerusakan 
    LEFT JOIN aset ON kerusakan.nama_aset = aset.nama_aset 
    ORDER BY kerusakan.id DESC
";
$kerusakan = mysqli_query($koneksi, $query_kerusakan);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Kerusakan | Pegawai</title>
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

        tr:hover {
            background-color: #d4f3e4 !important;
            cursor: pointer;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-medis {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-nonmedis {
            background: #e0f2fe;
            color: #0284c7;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .kolom-keterangan {
            min-width: 200px;
            max-width: 250px;
            white-space: normal !important;
        }
    </style>

</head>

<body>

    <?php include __DIR__ . '/sidebar_user.php'; ?>

    <div id="page-content-wrapper">

        <div class="dashboard-header">
            <div>
                <h4 class="fw-bold m-0"><i class="bi bi-exclamation-octagon"></i> DATA LAPORAN KERUSAKAN</h4>
                <small class="text-light">Pantau status laporan kerusakan aset rumah sakit yang telah diajukan.</small>
            </div>
            <div>
                <i class="bi bi-person-badge"></i> Pegawai
            </div>
        </div>

        <div class="card p-3 shadow-sm border-0 mb-3" style="border-radius: 12px;">
            <div class="row g-3 align-items-center">

                <div class="col-md-5">
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Cari nama aset, lokasi, atau teknisi..."
                        style="padding:10px; font-size:14px;">
                </div>

                <div class="col-md-3">
                    <select id="filterStatus" class="form-select"
                        style="padding:10px; font-size:14px;">
                        <option value="">Semua Status Laporan</option>
                        <option value="Rusak">Rusak</option>
                        <option value="Perlu Perawatan">Perlu Perawatan</option>
                        <option value="Pending">Pending</option>
                        <option value="Diproses">Diproses / Diperbaiki</option>
                        <option value="Selesai">Selesai</option>
                    </select>
                </div>

                <div class="col-md-4 d-flex justify-content-end gap-2">
                    <button class="btn btn-success px-4" onclick="resetFilter()">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>

                    <a href="cetak_kerusakan_user.php" target="_blank" class="btn btn-danger px-4">
                        <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered mb-0 text-center" id="tabelKerusakan">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Nama Aset</th>
                            <th width="15%">Lokasi Ruangan</th>
                            <th width="12%">Kategori</th>
                            <th width="15%">Teknisi Perbaikan</th>
                            <th width="13%">Status Laporan</th>
                            <th width="15%">Rincian Kerusakan</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($kerusakan)):

                            $status = $row['status'] ?? 'Baru';
                            $bgStatus = 'bg-secondary text-white';

                            if (stripos($status, 'Rusak') !== false) {
                                $bgStatus = 'bg-danger text-white';
                            } elseif (stripos($status, 'Perawatan') !== false || stripos($status, 'Pending') !== false) {
                                $bgStatus = 'bg-warning text-dark';
                            } elseif (stripos($status, 'Proses') !== false || stripos($status, 'Perbaikan') !== false) {
                                $bgStatus = 'bg-info text-dark';
                            } elseif (stripos($status, 'Selesai') !== false || stripos($status, 'Baik') !== false) {
                                $bgStatus = 'bg-success text-white';
                            }
                        ?>

                            <tr onclick="showDetail('<?= htmlspecialchars($row['nama_aset']) ?>', '<?= htmlspecialchars($row['lokasi'] ?? '-') ?>', '<?= htmlspecialchars($row['kategori_aset'] ?? '-') ?>', '<?= htmlspecialchars($row['pelapor'] ?? '-') ?>', '<?= !empty($row['tanggal']) ? date('d-m-Y', strtotime($row['tanggal'])) : '-' ?>', '<?= htmlspecialchars($row['teknisi'] ?? '-') ?>', '<?= htmlspecialchars($status) ?>', '<?= htmlspecialchars(str_replace(["\r", "\n"], ' ', $row['keterangan'])) ?>')">

                                <td><?= $no++; ?></td>
                                <td class="text-start fw-bold text-dark"><?= htmlspecialchars($row['nama_aset']); ?></td>
                                <td class="text-start"><i class="bi bi-geo-alt text-danger"></i> <?= htmlspecialchars($row['lokasi'] ?? '-'); ?></td>

                                <td>
                                    <?php if (($row['kategori_aset'] ?? '') == 'Medis'): ?>
                                        <span class="badge-medis">Medis</span>
                                    <?php else: ?>
                                        <span class="badge-nonmedis">Non-Medis</span>
                                    <?php endif; ?>
                                </td>

                                <td class="fw-medium text-primary"><?= htmlspecialchars($row['teknisi'] ?? 'Belum Ditentukan'); ?></td>
                                <td><span class="badge-status <?= $bgStatus ?>"><?= htmlspecialchars($status); ?></span></td>
                                <td class="text-start kolom-keterangan text-muted small"><?= htmlspecialchars($row['keterangan']); ?></td>

                            </tr>

                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header" style="background: linear-gradient(90deg, #2c7a7b, #1cc88a); color: white; border-top-left-radius: 12px; border-top-right-radius: 12px;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-info-circle"></i> Rincian Detail Laporan Kerusakan</h5>
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
        function showDetail(aset, lokasi, kategori, pelapor, tanggal, teknisi, status, ket) {
            document.getElementById("modalContent").innerHTML =
                `<table class="table table-sm table-borderless mb-0">
                    <tr><td width="30%"><b>Nama Aset</b></td><td width="5%">:</td><td class="fw-bold text-dark">${aset}</td></tr>
                    <tr><td><b>Lokasi Ruangan</b></td><td>:</td><td>${lokasi}</td></tr>
                    <tr><td><b>Kategori</b></td><td>:</td><td>${kategori}</td></tr>
                    <tr><td><b>Dilaporkan Oleh</b></td><td>:</td><td>${pelapor} (Pegawai)</td></tr>
                    <tr><td><b>Tanggal Lapor</b></td><td>:</td><td>${tanggal}</td></tr>
                    <tr><td><b>Teknisi Perbaikan</b></td><td>:</td><td class="text-primary fw-bold">${teknisi}</td></tr>
                    <tr><td><b>Status Terkini</b></td><td>:</td><td><b>${status}</b></td></tr>
                    <tr><td colspan="3" class="pt-3"><b>Keterangan/Rincian Kerusakan:</b><br><div class="bg-light p-2 mt-1 rounded border">${ket}</div></td></tr>
                 </table>`;

            new bootstrap.Modal(document.getElementById("detailModal")).show();
        }

        const searchInput = document.getElementById("searchInput");
        const filterStatus = document.getElementById("filterStatus");
        const table = document.getElementById("tabelKerusakan");
        const trs = table.getElementsByTagName("tr");

        function applyFilter() {
            let search = searchInput.value.toLowerCase();
            let status = filterStatus.value.toLowerCase();

            for (let i = 1; i < trs.length; i++) {
                let tdNama = trs[i].children[1].textContent.toLowerCase();
                let tdLokasi = trs[i].children[2].textContent.toLowerCase();
                let tdTeknisi = trs[i].children[4].textContent.toLowerCase();
                let tdStatus = trs[i].children[5].textContent.toLowerCase(); // Indeks disesuaikan karena 2 kolom dihapus

                let matchesSearch = tdNama.includes(search) ||
                    tdLokasi.includes(search) ||
                    tdTeknisi.includes(search);

                let matchesStatus = (status === "" || tdStatus.includes(status));

                trs[i].style.display = (matchesSearch && matchesStatus) ? "" : "none";
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