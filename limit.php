<?php
require_once 'config.php';
cekLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $limit_harian  = trim($_POST['limit_harian'] ?? 0);
    $limit_bulanan = trim($_POST['limit_bulanan'] ?? 0);

    if (!is_numeric($limit_harian) || !is_numeric($limit_bulanan)) {
        $error = 'Limit harus berupa angka!';
    } elseif ($limit_harian < 0 || $limit_bulanan < 0) {
        $error = 'Limit tidak boleh negatif!';
    } else {
        $hasil = simpanLimit($user_id, $limit_harian, $limit_bulanan);
        if ($hasil) $success = 'Limit berhasil disimpan!';
        else $error = 'Gagal menyimpan limit!';
    }
}

$limit    = getLimit($user_id);
$harian   = getPengeluaranHarian($user_id);
$bulanan  = getPengeluaranBulanan($user_id);

$persen_harian  = ($limit['limit_harian'] > 0) ? min(100, ($harian / $limit['limit_harian']) * 100) : 0;
$persen_bulanan = ($limit['limit_bulanan'] > 0) ? min(100, ($bulanan / $limit['limit_bulanan']) * 100) : 0;

function getClass($persen) {
    if ($persen >= 100) return 'danger';
    if ($persen >= 80) return 'warning';
    return 'safe';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Limit — Budgetin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header">
            <h1>🎯 Atur Limit Pengeluaran</h1>
            <p>Kendalikan pengeluaranmu dengan menetapkan batas maksimal</p>
        </div>

        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <div class="alert alert-info">
            ℹ️ Notifikasi <strong>warning</strong> akan muncul saat pengeluaran mencapai <strong>80%</strong> dari limit. Notifikasi <strong>bahaya</strong> muncul saat limit terlampaui.
        </div>

        <div class="grid-2">
            <!-- Form Atur Limit -->
            <div class="card">
                <div class="card-title">⚙️ Pengaturan Limit</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Limit Pengeluaran Harian (Rp)</label>
                        <input type="number" name="limit_harian" value="<?= $limit['limit_harian'] ?? 0 ?>" min="0" placeholder="0 = tidak ada limit">
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:5px;">Isi 0 jika tidak ingin mengatur limit harian</div>
                    </div>
                    <div class="form-group">
                        <label>Limit Pengeluaran Bulanan (Rp)</label>
                        <input type="number" name="limit_bulanan" value="<?= $limit['limit_bulanan'] ?? 0 ?>" min="0" placeholder="0 = tidak ada limit">
                        <div style="font-size:0.8rem; color:var(--text-muted); margin-top:5px;">Isi 0 jika tidak ingin mengatur limit bulanan</div>
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Simpan Limit</button>
                </form>
            </div>

            <!-- Status Limit -->
            <div class="card">
                <div class="card-title">📊 Status Pengeluaran vs Limit</div>

                <!-- Harian -->
                <div style="margin-bottom:24px;">
                    <h3 style="font-size:0.95rem; margin-bottom:12px; color:var(--text);">📅 Harian (Hari Ini)</h3>
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.88rem;">
                        <span>Pengeluaran: <strong><?= formatRupiah($harian) ?></strong></span>
                        <span>Limit: <strong><?= $limit['limit_harian'] > 0 ? formatRupiah($limit['limit_harian']) : 'Tidak diatur' ?></strong></span>
                    </div>
                    <?php if ($limit['limit_harian'] > 0): ?>
                        <div class="progress-bar">
                            <div class="progress-fill <?= getClass($persen_harian) ?>" style="width:<?= $persen_harian ?>%"></div>
                        </div>
                        <div style="font-size:0.8rem; margin-top:6px; color:var(--text-muted);">
                            <?= round($persen_harian) ?>% terpakai |
                            Sisa: <?= formatRupiah(max(0, $limit['limit_harian'] - $harian)) ?>
                        </div>
                        <?php if ($persen_harian >= 100): ?>
                            <div class="alert alert-danger" style="margin-top:10px;">⛔ Limit harian sudah terlampaui!</div>
                        <?php elseif ($persen_harian >= 80): ?>
                            <div class="alert alert-warning" style="margin-top:10px;">⚠️ Mendekati limit harian!</div>
                        <?php else: ?>
                            <div class="alert alert-success" style="margin-top:10px;">✅ Pengeluaran harian masih aman</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="font-size:0.85rem; color:var(--text-muted); padding:12px 0;">Limit harian belum diatur.</div>
                    <?php endif; ?>
                </div>

                <!-- Bulanan -->
                <div>
                    <h3 style="font-size:0.95rem; margin-bottom:12px; color:var(--text);">📆 Bulanan (Bulan Ini)</h3>
                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:0.88rem;">
                        <span>Pengeluaran: <strong><?= formatRupiah($bulanan) ?></strong></span>
                        <span>Limit: <strong><?= $limit['limit_bulanan'] > 0 ? formatRupiah($limit['limit_bulanan']) : 'Tidak diatur' ?></strong></span>
                    </div>
                    <?php if ($limit['limit_bulanan'] > 0): ?>
                        <div class="progress-bar">
                            <div class="progress-fill <?= getClass($persen_bulanan) ?>" style="width:<?= $persen_bulanan ?>%"></div>
                        </div>
                        <div style="font-size:0.8rem; margin-top:6px; color:var(--text-muted);">
                            <?= round($persen_bulanan) ?>% terpakai |
                            Sisa: <?= formatRupiah(max(0, $limit['limit_bulanan'] - $bulanan)) ?>
                        </div>
                        <?php if ($persen_bulanan >= 100): ?>
                            <div class="alert alert-danger" style="margin-top:10px;">⛔ Limit bulanan sudah terlampaui!</div>
                        <?php elseif ($persen_bulanan >= 80): ?>
                            <div class="alert alert-warning" style="margin-top:10px;">⚠️ Mendekati limit bulanan!</div>
                        <?php else: ?>
                            <div class="alert alert-success" style="margin-top:10px;">✅ Pengeluaran bulanan masih aman</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="font-size:0.85rem; color:var(--text-muted); padding:12px 0;">Limit bulanan belum diatur.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
