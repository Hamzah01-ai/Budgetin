<?php
require_once 'config.php';
cekLogin();

$user_id = $_SESSION['user_id'];
$nama    = $_SESSION['user_nama'];

// Filter laporan
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

$conn = koneksi();
$bulan_esc = mysqli_real_escape_string($conn, $filter_bulan);
$q = mysqli_query($conn, "SELECT * FROM transaksi WHERE user_id = $user_id AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_esc' ORDER BY tanggal ASC, created_at ASC");
$transaksi = [];
while ($r = mysqli_fetch_assoc($q)) $transaksi[] = $r;
mysqli_close($conn);

// Hitung ringkasan
$total_pemasukan   = 0;
$total_pengeluaran = 0;
foreach ($transaksi as $t) {
    if ($t['jenis'] == 'pemasukan') $total_pemasukan += $t['jumlah'];
    else $total_pengeluaran += $t['jumlah'];
}
$saldo_bulan = $total_pemasukan - $total_pengeluaran;

$bulan_label = date('F Y', strtotime($filter_bulan . '-01'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan — Budgetin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        @media print {
            .sidebar, .page-header, .filter-card, .btn-download, .btn-logout { display: none !important; }
            .main-content { margin-left: 0 !important; }
            .layout { display: block; }
        }
    </style>
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <h1>📊 Laporan Keuangan</h1>
            <p>Lihat dan unduh laporan keuanganmu per bulan</p>
        </div>

        <!-- Filter -->
        <div class="card filter-card" style="margin-bottom:20px;">
            <form method="GET" style="display:flex; gap:14px; align-items:flex-end;">
                <div class="form-group" style="margin:0; flex:1; max-width:220px;">
                    <label>Pilih Bulan</label>
                    <input type="month" name="bulan" value="<?= htmlspecialchars($filter_bulan) ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width:auto; padding:11px 22px;">🔍 Tampilkan</button>
            </form>
        </div>

        <!-- Laporan -->
        <div class="card" id="laporan-cetak">
            <!-- Header Laporan -->
            <div style="text-align:center; border-bottom:2px solid var(--border); padding-bottom:20px; margin-bottom:24px;">
                <div style="font-size:1.8rem; margin-bottom:8px;">💰</div>
                <h2 style="font-size:1.3rem; color:var(--text); font-weight:800;">LAPORAN KEUANGAN MAHASISWA</h2>
                <div style="font-size:1rem; color:var(--primary); font-weight:600; margin-top:4px;"><?= strtoupper($bulan_label) ?></div>
                <div style="font-size:0.88rem; color:var(--text-muted); margin-top:6px;">
                    Nama: <strong><?= htmlspecialchars($nama) ?></strong> &nbsp;|&nbsp;
                    Dicetak: <?= date('d/m/Y H:i') ?>
                </div>
            </div>

            <!-- Ringkasan -->
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:28px;">
                <div style="background:#d1fae5; border-radius:10px; padding:16px; text-align:center;">
                    <div style="font-size:0.78rem; color:#065f46; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Pemasukan</div>
                    <div style="font-size:1.3rem; font-weight:800; color:#065f46; font-family:'Syne',sans-serif; margin-top:4px;"><?= formatRupiah($total_pemasukan) ?></div>
                </div>
                <div style="background:#fee2e2; border-radius:10px; padding:16px; text-align:center;">
                    <div style="font-size:0.78rem; color:#991b1b; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Pengeluaran</div>
                    <div style="font-size:1.3rem; font-weight:800; color:#991b1b; font-family:'Syne',sans-serif; margin-top:4px;"><?= formatRupiah($total_pengeluaran) ?></div>
                </div>
                <div style="background:<?= $saldo_bulan >= 0 ? 'var(--primary-light)' : '#fee2e2' ?>; border-radius:10px; padding:16px; text-align:center;">
                    <div style="font-size:0.78rem; color:<?= $saldo_bulan >= 0 ? '#0369a1' : '#991b1b' ?>; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Saldo Bulan Ini</div>
                    <div style="font-size:1.3rem; font-weight:800; color:<?= $saldo_bulan >= 0 ? 'var(--primary)' : 'var(--danger)' ?>; font-family:'Syne',sans-serif; margin-top:4px;"><?= formatRupiah($saldo_bulan) ?></div>
                </div>
            </div>

            <!-- Tabel Transaksi -->
            <?php if (empty($transaksi)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>Tidak ada transaksi pada bulan <?= $bulan_label ?></p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jenis</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th>Pemasukan</th>
                                <th>Pengeluaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transaksi as $i => $t): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                <td>
                                    <span class="badge badge-<?= $t['jenis']=='pemasukan' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($t['jenis']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($t['kategori'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($t['keterangan'] ?: '-') ?></td>
                                <td style="color:var(--success); font-weight:600;">
                                    <?= $t['jenis']=='pemasukan' ? formatRupiah($t['jumlah']) : '-' ?>
                                </td>
                                <td style="color:var(--danger); font-weight:600;">
                                    <?= $t['jenis']=='pengeluaran' ? formatRupiah($t['jumlah']) : '-' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f8fafc; font-weight:700;">
                                <td colspan="5" style="text-align:right; padding:12px 14px; font-family:'Syne',sans-serif;">TOTAL</td>
                                <td style="color:var(--success); padding:12px 14px;"><?= formatRupiah($total_pemasukan) ?></td>
                                <td style="color:var(--danger); padding:12px 14px;"><?= formatRupiah($total_pengeluaran) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tombol Cetak -->
        <?php if (!empty($transaksi)): ?>
        <div style="margin-top:16px;">
            <button onclick="window.print()" class="btn btn-outline" style="width:auto; padding:11px 22px;">🖨️ Cetak Laporan</button>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
