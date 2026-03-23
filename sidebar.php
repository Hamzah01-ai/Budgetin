<?php
// sidebar.php - komponen sidebar (di-include di semua halaman dashboard)
$current_page = basename($_SERVER['PHP_SELF']);
$initial = strtoupper(substr($_SESSION['user_nama'], 0, 1));
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <span class="logo-icon">💰</span>
        <h2>Budgetin</h2>
        <p>Aplikasi Keuangan</p>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>
        <a href="pemasukan.php" class="nav-item <?= $current_page == 'pemasukan.php' ? 'active' : '' ?>">
            <span class="nav-icon">📥</span> Pemasukan
        </a>
        <a href="pengeluaran.php" class="nav-item <?= $current_page == 'pengeluaran.php' ? 'active' : '' ?>">
            <span class="nav-icon">📤</span> Pengeluaran
        </a>
        <a href="transaksi.php" class="nav-item <?= $current_page == 'transaksi.php' ? 'active' : '' ?>">
            <span class="nav-icon">📋</span> Semua Transaksi
        </a>
        <a href="limit.php" class="nav-item <?= $current_page == 'limit.php' ? 'active' : '' ?>">
            <span class="nav-icon">🎯</span> Atur Limit
        </a>
        <a href="laporan.php" class="nav-item <?= $current_page == 'laporan.php' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Laporan
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar"><?= $initial ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['user_nama']) ?></div>
            <div class="user-role">Mahasiswa</div>
        </div>
        <a href="logout.php" class="btn-logout" title="Keluar">🚪</a>
    </div>
</div>
