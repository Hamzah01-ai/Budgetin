<?php
require_once 'config.php';
cekLogin();

$user_id = $_SESSION['user_id'];

// Hapus transaksi
if (isset($_GET['hapus'])) {
    hapusTransaksi((int)$_GET['hapus'], $user_id);
    header("Location: transaksi.php?deleted=1");
    exit();
}

$success = isset($_GET['deleted']) ? 'Transaksi berhasil dihapus!' : '';

// Filter
$filter_jenis = $_GET['jenis'] ?? '';
$filter_bulan = $_GET['bulan'] ?? '';

$conn = koneksi();
$where = "WHERE t.user_id = $user_id";
if ($filter_jenis == 'pemasukan' || $filter_jenis == 'pengeluaran') {
    $where .= " AND t.jenis = '$filter_jenis'";
}
if ($filter_bulan) {
    $bulan_esc = mysqli_real_escape_string($conn, $filter_bulan);
    $where .= " AND DATE_FORMAT(t.tanggal, '%Y-%m') = '$bulan_esc'";
}

$q = mysqli_query($conn, "SELECT * FROM transaksi t $where ORDER BY t.tanggal DESC, t.created_at DESC");
$semua = [];
while ($r = mysqli_fetch_assoc($q)) $semua[] = $r;
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Transaksi — Budgetin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <h1>📋 Semua Transaksi</h1>
            <p>Riwayat lengkap semua transaksimu</p>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

        <!-- Filter -->
        <div class="card" style="margin-bottom:20px;">
            <form method="GET" style="display:flex; gap:14px; align-items:flex-end; flex-wrap:wrap;">
                <div class="form-group" style="margin:0; flex:1; min-width:150px;">
                    <label>Filter Jenis</label>
                    <select name="jenis">
                        <option value="">Semua</option>
                        <option value="pemasukan" <?= $filter_jenis=='pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                        <option value="pengeluaran" <?= $filter_jenis=='pengeluaran' ? 'selected' : '' ?>>Pengeluaran</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0; flex:1; min-width:150px;">
                    <label>Filter Bulan</label>
                    <input type="month" name="bulan" value="<?= htmlspecialchars($filter_bulan) ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="width:auto; padding:11px 22px;">🔍 Filter</button>
                <a href="transaksi.php" class="btn btn-outline" style="padding:11px 22px;">Reset</a>
            </form>
        </div>

        <div class="card">
            <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
                <span>Daftar Transaksi</span>
                <span style="font-size:0.85rem; color:var(--text-muted); font-weight:400;"><?= count($semua) ?> transaksi ditemukan</span>
            </div>
            <?php if (empty($semua)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>Tidak ada transaksi ditemukan.</p>
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
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semua as $i => $t): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                <td>
                                    <span class="badge badge-<?= $t['jenis'] == 'pemasukan' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($t['jenis']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($t['kategori'] ?: '-') ?></td>
                                <td style="font-weight:700; color:<?= $t['jenis']=='pemasukan' ? 'var(--success)' : 'var(--danger)' ?>">
                                    <?= $t['jenis']=='pemasukan' ? '+' : '-' ?><?= formatRupiah($t['jumlah']) ?>
                                </td>
                                <td><?= htmlspecialchars($t['keterangan'] ?: '-') ?></td>
                                <td>
                                    <a href="transaksi.php?hapus=<?= $t['id'] ?>&jenis=<?= $filter_jenis ?>&bulan=<?= $filter_bulan ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus transaksi ini?')">🗑</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
