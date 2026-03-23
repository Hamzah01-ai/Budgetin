<?php
require_once 'config.php';
cekLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Proses tambah pengeluaran
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $kategori   = trim($_POST['kategori'] ?? '');
    $jumlah     = trim($_POST['jumlah'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $tanggal    = trim($_POST['tanggal'] ?? '');

    if (empty($jumlah) || empty($tanggal)) {
        $error = 'Jumlah dan tanggal wajib diisi!';
    } elseif (!is_numeric($jumlah) || $jumlah <= 0) {
        $error = 'Jumlah harus berupa angka positif!';
    } else {
        $hasil = tambahTransaksi($user_id, 'pengeluaran', $kategori, $jumlah, $keterangan, $tanggal);
        if ($hasil) $success = 'Pengeluaran berhasil ditambahkan!';
        else $error = 'Gagal menambahkan pengeluaran!';
    }
}

// Proses hapus
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    hapusTransaksi($hapus_id, $user_id);
    header("Location: pengeluaran.php?deleted=1");
    exit();
}

if (isset($_GET['deleted'])) $success = 'Pengeluaran berhasil dihapus!';

$data_pengeluaran = [];
$conn = koneksi();
$q = mysqli_query($conn, "SELECT * FROM transaksi WHERE user_id = $user_id AND jenis = 'pengeluaran' ORDER BY tanggal DESC, created_at DESC");
while ($r = mysqli_fetch_assoc($q)) $data_pengeluaran[] = $r;
mysqli_close($conn);

$total    = getTotalPengeluaran($user_id);
$harian   = getPengeluaranHarian($user_id);
$bulanan  = getPengeluaranBulanan($user_id);
$limit    = getLimit($user_id);
$notifikasi = cekStatusLimit($user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengeluaran — Budgetin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <h1>📤 Pengeluaran</h1>
            <p>Pantau dan catat semua pengeluaranmu</p>
        </div>

        <?php foreach ($notifikasi as $notif): ?>
            <div class="alert alert-<?= $notif['tipe'] == 'limit' ? 'danger' : 'warning' ?>"><?= $notif['pesan'] ?></div>
        <?php endforeach; ?>

        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <div class="grid-2" style="margin-bottom:24px;">
            <!-- Form Tambah -->
            <div class="card">
                <div class="card-title">➕ Tambah Pengeluaran</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Makan & Minum">Makan & Minum</option>
                            <option value="Transportasi">Transportasi</option>
                            <option value="Kos / Tempat Tinggal">Kos / Tempat Tinggal</option>
                            <option value="Keperluan Kuliah">Keperluan Kuliah</option>
                            <option value="Hiburan">Hiburan</option>
                            <option value="Kesehatan">Kesehatan</option>
                            <option value="Pakaian">Pakaian</option>
                            <option value="Internet & Pulsa">Internet & Pulsa</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jumlah (Rp) *</label>
                            <input type="number" name="jumlah" placeholder="0" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal *</label>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" placeholder="Keterangan tambahan (opsional)..."></textarea>
                    </div>
                    <button type="submit" name="tambah" class="btn btn-danger">💾 Simpan Pengeluaran</button>
                </form>
            </div>

            <!-- Ringkasan -->
            <div class="card">
                <div class="card-title">📊 Ringkasan Pengeluaran</div>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div style="text-align:center; padding:16px; background:var(--bg); border-radius:10px;">
                        <div style="font-size:0.78rem; color:var(--text-muted); text-transform:uppercase; font-weight:600;">Total Keseluruhan</div>
                        <div style="font-size:1.6rem; font-weight:800; color:var(--danger); font-family:'Syne',sans-serif; letter-spacing:-1px;"><?= formatRupiah($total) ?></div>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div style="text-align:center; padding:12px; background:var(--bg); border-radius:10px;">
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">Hari Ini</div>
                            <div style="font-size:1.1rem; font-weight:700; color:var(--danger);"><?= formatRupiah($harian) ?></div>
                            <?php if ($limit['limit_harian'] > 0): ?>
                                <div style="font-size:0.72rem; color:var(--text-muted);">Limit: <?= formatRupiah($limit['limit_harian']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:center; padding:12px; background:var(--bg); border-radius:10px;">
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:600;">Bulan Ini</div>
                            <div style="font-size:1.1rem; font-weight:700; color:var(--danger);"><?= formatRupiah($bulanan) ?></div>
                            <?php if ($limit['limit_bulanan'] > 0): ?>
                                <div style="font-size:0.72rem; color:var(--text-muted);">Limit: <?= formatRupiah($limit['limit_bulanan']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="font-size:0.82rem; color:var(--text-muted); text-align:center;"><?= count($data_pengeluaran) ?> total transaksi pengeluaran</div>
                </div>
            </div>
        </div>

        <!-- Tabel -->
        <div class="card">
            <div class="card-title">📋 Riwayat Pengeluaran</div>
            <?php if (empty($data_pengeluaran)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>Belum ada data pengeluaran.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data_pengeluaran as $i => $p): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($p['kategori'] ?: '-') ?></td>
                                <td style="font-weight:700; color:var(--danger);">-<?= formatRupiah($p['jumlah']) ?></td>
                                <td><?= htmlspecialchars($p['keterangan'] ?: '-') ?></td>
                                <td>
                                    <a href="pengeluaran.php?hapus=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus transaksi ini?')">🗑 Hapus</a>
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
