<?php
require_once 'config.php';
cekLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

// Proses tambah pemasukan
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
        $hasil = tambahTransaksi($user_id, 'pemasukan', $kategori, $jumlah, $keterangan, $tanggal);
        if ($hasil) $success = 'Pemasukan berhasil ditambahkan!';
        else $error = 'Gagal menambahkan pemasukan!';
    }
}

// Proses hapus
if (isset($_GET['hapus'])) {
    $hapus_id = (int)$_GET['hapus'];
    hapusTransaksi($hapus_id, $user_id);
    header("Location: pemasukan.php?deleted=1");
    exit();
}

if (isset($_GET['deleted'])) $success = 'Pemasukan berhasil dihapus!';

$data_pemasukan = [];
$conn = koneksi();
$q = mysqli_query($conn, "SELECT * FROM transaksi WHERE user_id = $user_id AND jenis = 'pemasukan' ORDER BY tanggal DESC, created_at DESC");
while ($r = mysqli_fetch_assoc($q)) $data_pemasukan[] = $r;
mysqli_close($conn);

$total = getTotalPemasukan($user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemasukan — Budgetin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <h1>📥 Pemasukan</h1>
            <p>Catat semua sumber pemasukanmu</p>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <div class="grid-2" style="margin-bottom:24px;">
            <!-- Form Tambah -->
            <div class="card">
                <div class="card-title">➕ Tambah Pemasukan</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Uang Saku">Uang Saku</option>
                            <option value="Beasiswa">Beasiswa</option>
                            <option value="Gaji / Kerja Paruh Waktu">Gaji / Kerja Paruh Waktu</option>
                            <option value="Transferan Ortu">Transferan Ortu</option>
                            <option value="Freelance">Freelance</option>
                            <option value="Hadiah / Bonus">Hadiah / Bonus</option>
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
                    <button type="submit" name="tambah" class="btn btn-success">💾 Simpan Pemasukan</button>
                </form>
            </div>

            <!-- Info Total -->
            <div class="card" style="display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;">
                <div style="font-size:3rem; margin-bottom:12px;">📥</div>
                <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; font-weight:600;">Total Pemasukan</div>
                <div style="font-size:2rem; font-weight:800; color:var(--success); font-family:'Syne',sans-serif; margin-top:8px; letter-spacing:-1px;"><?= formatRupiah($total) ?></div>
                <div style="margin-top:16px; font-size:0.85rem; color:var(--text-muted);"><?= count($data_pemasukan) ?> transaksi pemasukan</div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card">
            <div class="card-title">📋 Riwayat Pemasukan</div>
            <?php if (empty($data_pemasukan)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>Belum ada data pemasukan.<br>Tambahkan pemasukan pertamamu!</p>
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
                            <?php foreach ($data_pemasukan as $i => $p): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= date('d/m/Y', strtotime($p['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($p['kategori'] ?: '-') ?></td>
                                <td style="font-weight:700; color:var(--success);">+<?= formatRupiah($p['jumlah']) ?></td>
                                <td><?= htmlspecialchars($p['keterangan'] ?: '-') ?></td>
                                <td>
                                    <a href="pemasukan.php?hapus=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus transaksi ini?')">🗑 Hapus</a>
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
