<?php
require_once 'config.php';
cekLogin();

$user_id = $_SESSION['user_id'];
$nama    = $_SESSION['user_nama'];

$saldo           = getSaldo($user_id);
$total_pemasukan = getTotalPemasukan($user_id);
$total_pengeluaran = getTotalPengeluaran($user_id);
$pengeluaran_harian  = getPengeluaranHarian($user_id);
$pengeluaran_bulanan = getPengeluaranBulanan($user_id);
$limit           = getLimit($user_id);
$notifikasi      = cekStatusLimit($user_id);
$transaksi_terbaru = getTransaksi($user_id, "LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Budgetin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Halo, <?= htmlspecialchars($nama) ?>! 👋</h1>
            <p>Selamat datang di dashboard keuanganmu — <?= date('l, d F Y') ?></p>
        </div>

        <!-- Notifikasi Limit -->
        <?php foreach ($notifikasi as $notif): ?>
            <div class="alert alert-<?= $notif['tipe'] == 'limit' ? 'danger' : 'warning' ?>">
                <?= $notif['pesan'] ?>
            </div>
        <?php endforeach; ?>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">💳</div>
                <div class="stat-info">
                    <div class="stat-label">Saldo Saat Ini</div>
                    <div class="stat-value <?= $saldo >= 0 ? 'blue' : 'red' ?>"><?= formatRupiah($saldo) ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📥</div>
                <div class="stat-info">
                    <div class="stat-label">Total Pemasukan</div>
                    <div class="stat-value green"><?= formatRupiah($total_pemasukan) ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">📤</div>
                <div class="stat-info">
                    <div class="stat-label">Total Pengeluaran</div>
                    <div class="stat-value red"><?= formatRupiah($total_pengeluaran) ?></div>
                </div>
            </div>
        </div>

        <div class="grid-3">
            <!-- Transaksi Terbaru -->
            <div class="card">
                <div class="card-title">🕐 Transaksi Terbaru</div>
                <?php if (empty($transaksi_terbaru)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>Belum ada transaksi.<br>Mulai tambahkan transaksimu!</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kategori</th>
                                    <th>Jumlah</th>
                                    <th>Jenis</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_terbaru as $t): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($t['kategori'] ?: '-') ?></td>
                                    <td style="font-weight:600; color: <?= $t['jenis'] == 'pemasukan' ? 'var(--success)' : 'var(--danger)' ?>">
                                        <?= $t['jenis'] == 'pemasukan' ? '+' : '-' ?><?= formatRupiah($t['jumlah']) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $t['jenis'] == 'pemasukan' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($t['jenis']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:14px; text-align:right;">
                        <a href="transaksi.php" class="btn btn-outline btn-sm">Lihat Semua →</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Status Limit -->
            <div class="card">
                <div class="card-title">🎯 Status Limit</div>

                <!-- Limit Harian -->
                <?php
                $persen_harian = 0;
                if ($limit['limit_harian'] > 0) {
                    $persen_harian = min(100, ($pengeluaran_harian / $limit['limit_harian']) * 100);
                }
                $class_harian = $persen_harian >= 100 ? 'danger' : ($persen_harian >= 80 ? 'warning' : 'safe');
                ?>
                <div class="limit-card">
                    <div class="limit-label">
                        <span>Pengeluaran Harian</span>
                        <span><?= formatRupiah($pengeluaran_harian) ?> / <?= $limit['limit_harian'] > 0 ? formatRupiah($limit['limit_harian']) : 'Belum diatur' ?></span>
                    </div>
                    <?php if ($limit['limit_harian'] > 0): ?>
                        <div class="progress-bar">
                            <div class="progress-fill <?= $class_harian ?>" style="width: <?= $persen_harian ?>%"></div>
                        </div>
                        <div style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;"><?= round($persen_harian) ?>% dari limit</div>
                    <?php else: ?>
                        <div style="font-size:0.82rem; color:var(--text-muted);">
                            <a href="limit.php">Atur limit harian →</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Limit Bulanan -->
                <?php
                $persen_bulanan = 0;
                if ($limit['limit_bulanan'] > 0) {
                    $persen_bulanan = min(100, ($pengeluaran_bulanan / $limit['limit_bulanan']) * 100);
                }
                $class_bulanan = $persen_bulanan >= 100 ? 'danger' : ($persen_bulanan >= 80 ? 'warning' : 'safe');
                ?>
                <div class="limit-card" style="margin-top:20px;">
                    <div class="limit-label">
                        <span>Pengeluaran Bulan Ini</span>
                        <span><?= formatRupiah($pengeluaran_bulanan) ?> / <?= $limit['limit_bulanan'] > 0 ? formatRupiah($limit['limit_bulanan']) : 'Belum diatur' ?></span>
                    </div>
                    <?php if ($limit['limit_bulanan'] > 0): ?>
                        <div class="progress-bar">
                            <div class="progress-fill <?= $class_bulanan ?>" style="width: <?= $persen_bulanan ?>%"></div>
                        </div>
                        <div style="font-size:0.78rem; color:var(--text-muted); margin-top:4px;"><?= round($persen_bulanan) ?>% dari limit</div>
                    <?php else: ?>
                        <div style="font-size:0.82rem; color:var(--text-muted);">
                            <a href="limit.php">Atur limit bulanan →</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top:22px;">
                    <a href="pemasukan.php" class="btn btn-success btn-sm" style="margin-right:8px;">+ Pemasukan</a>
                    <a href="pengeluaran.php" class="btn btn-danger btn-sm">+ Pengeluaran</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
