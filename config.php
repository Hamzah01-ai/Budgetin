<?php
// config.php - Konfigurasi Database

$isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;

if ($isLocal) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'keuangan_mahasiswa');

} else {
    error_reporting(0);

    define('DB_HOST', 'sql306.infinityfree.com');
    define('DB_USER', 'if0_41453564');
    define('DB_PASS', 'KhRblT9oZg');
    define('DB_NAME', 'if0_41453564_db_budgetin');
}
session_start();

// Fungsi koneksi database
function koneksi() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die("Koneksi gagal: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8");
    return $conn;
}

// Fungsi cek login
function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Fungsi cek sudah login (redirect ke dashboard jika sudah login)
function sudahLogin() {
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit();
    }
}

// Fungsi format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Fungsi get saldo user
function getSaldo($user_id) {
    $conn = koneksi();
    $pemasukan = 0;
    $pengeluaran = 0;

    $q1 = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi WHERE user_id = $user_id AND jenis = 'pemasukan'");
    $r1 = mysqli_fetch_assoc($q1);
    $pemasukan = $r1['total'] ?? 0;

    $q2 = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi WHERE user_id = $user_id AND jenis = 'pengeluaran'");
    $r2 = mysqli_fetch_assoc($q2);
    $pengeluaran = $r2['total'] ?? 0;

    mysqli_close($conn);
    return $pemasukan - $pengeluaran;
}

// Fungsi get total pemasukan
function getTotalPemasukan($user_id) {
    $conn = koneksi();
    $q = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi WHERE user_id = $user_id AND jenis = 'pemasukan'");
    $r = mysqli_fetch_assoc($q);
    mysqli_close($conn);
    return $r['total'] ?? 0;
}

// Fungsi get total pengeluaran
function getTotalPengeluaran($user_id) {
    $conn = koneksi();
    $q = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi WHERE user_id = $user_id AND jenis = 'pengeluaran'");
    $r = mysqli_fetch_assoc($q);
    mysqli_close($conn);
    return $r['total'] ?? 0;
}

// Fungsi get pengeluaran hari ini
function getPengeluaranHarian($user_id) {
    $conn = koneksi();
    $today = date('Y-m-d');
    $q = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi WHERE user_id = $user_id AND jenis = 'pengeluaran' AND tanggal = '$today'");
    $r = mysqli_fetch_assoc($q);
    mysqli_close($conn);
    return $r['total'] ?? 0;
}

// Fungsi get pengeluaran bulan ini
function getPengeluaranBulanan($user_id) {
    $conn = koneksi();
    $bulan = date('Y-m');
    $q = mysqli_query($conn, "SELECT SUM(jumlah) as total FROM transaksi WHERE user_id = $user_id AND jenis = 'pengeluaran' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'");
    $r = mysqli_fetch_assoc($q);
    mysqli_close($conn);
    return $r['total'] ?? 0;
}

// Fungsi get limit user
function getLimit($user_id) {
    $conn = koneksi();
    $q = mysqli_query($conn, "SELECT * FROM limit_pengeluaran WHERE user_id = $user_id");
    $r = mysqli_fetch_assoc($q);
    mysqli_close($conn);
    return $r ?? ['limit_harian' => 0, 'limit_bulanan' => 0];
}

// Fungsi cek status limit (return: 'aman', 'warning', 'limit')
function cekStatusLimit($user_id) {
    $limit = getLimit($user_id);
    $harian = getPengeluaranHarian($user_id);
    $bulanan = getPengeluaranBulanan($user_id);
    $status = [];

    if ($limit['limit_harian'] > 0) {
        $persen_harian = ($harian / $limit['limit_harian']) * 100;
        if ($persen_harian >= 100) {
            $status[] = ['tipe' => 'limit', 'pesan' => '⛔ Pengeluaran harian sudah mencapai limit! (' . formatRupiah($harian) . ' / ' . formatRupiah($limit['limit_harian']) . ')'];
        } elseif ($persen_harian >= 80) {
            $status[] = ['tipe' => 'warning', 'pesan' => '⚠️ Pengeluaran harian mendekati limit! (' . formatRupiah($harian) . ' / ' . formatRupiah($limit['limit_harian']) . ')'];
        }
    }

    if ($limit['limit_bulanan'] > 0) {
        $persen_bulanan = ($bulanan / $limit['limit_bulanan']) * 100;
        if ($persen_bulanan >= 100) {
            $status[] = ['tipe' => 'limit', 'pesan' => '⛔ Pengeluaran bulanan sudah mencapai limit! (' . formatRupiah($bulanan) . ' / ' . formatRupiah($limit['limit_bulanan']) . ')'];
        } elseif ($persen_bulanan >= 80) {
            $status[] = ['tipe' => 'warning', 'pesan' => '⚠️ Pengeluaran bulanan mendekati limit! (' . formatRupiah($bulanan) . ' / ' . formatRupiah($limit['limit_bulanan']) . ')'];
        }
    }

    return $status;
}

// Fungsi get semua transaksi user
function getTransaksi($user_id, $limit_query = "") {
    $conn = koneksi();
    $q = mysqli_query($conn, "SELECT * FROM transaksi WHERE user_id = $user_id ORDER BY tanggal DESC, created_at DESC $limit_query");
    $data = [];
    while ($r = mysqli_fetch_assoc($q)) {
        $data[] = $r;
    }
    mysqli_close($conn);
    return $data;
}

// Fungsi tambah transaksi
function tambahTransaksi($user_id, $jenis, $kategori, $jumlah, $keterangan, $tanggal) {
    $conn = koneksi();
    $jumlah = (float)$jumlah;
    $kategori = mysqli_real_escape_string($conn, $kategori);
    $keterangan = mysqli_real_escape_string($conn, $keterangan);
    $tanggal = mysqli_real_escape_string($conn, $tanggal);
    $jenis = mysqli_real_escape_string($conn, $jenis);
    $q = mysqli_query($conn, "INSERT INTO transaksi (user_id, jenis, kategori, jumlah, keterangan, tanggal) VALUES ($user_id, '$jenis', '$kategori', $jumlah, '$keterangan', '$tanggal')");
    mysqli_close($conn);
    return $q;
}

// Fungsi hapus transaksi
function hapusTransaksi($id, $user_id) {
    $conn = koneksi();
    $q = mysqli_query($conn, "DELETE FROM transaksi WHERE id = $id AND user_id = $user_id");
    mysqli_close($conn);
    return $q;
}

// Fungsi simpan/update limit
function simpanLimit($user_id, $limit_harian, $limit_bulanan) {
    $conn = koneksi();
    $limit_harian = (float)$limit_harian;
    $limit_bulanan = (float)$limit_bulanan;
    $q = mysqli_query($conn, "INSERT INTO limit_pengeluaran (user_id, limit_harian, limit_bulanan) VALUES ($user_id, $limit_harian, $limit_bulanan) ON DUPLICATE KEY UPDATE limit_harian = $limit_harian, limit_bulanan = $limit_bulanan");
    mysqli_close($conn);
    return $q;
}

// Fungsi register user
function registerUser($nama, $username, $password) {
    $conn = koneksi();
    $nama = mysqli_real_escape_string($conn, $nama);
    $username = mysqli_real_escape_string($conn, $username);
    
    // Cek username sudah ada
    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_close($conn);
        return ['sukses' => false, 'pesan' => 'Username sudah digunakan!'];
    }
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $q = mysqli_query($conn, "INSERT INTO users (nama, username, password) VALUES ('$nama', '$username', '$hash')");
    mysqli_close($conn);
    if ($q) return ['sukses' => true, 'pesan' => 'Akun berhasil dibuat!'];
    return ['sukses' => false, 'pesan' => 'Gagal membuat akun!'];
}

// Fungsi login user
function loginUser($username, $password) {
    $conn = koneksi();
    $username = mysqli_real_escape_string($conn, $username);
    $q = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($q) == 1) {
        $user = mysqli_fetch_assoc($q);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['username'] = $user['username'];
            mysqli_close($conn);
            return ['sukses' => true];
        }
    }
    mysqli_close($conn);
    return ['sukses' => false, 'pesan' => 'Username atau password salah!'];
}
?>
