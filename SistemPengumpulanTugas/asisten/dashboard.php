<?php
// Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database DI SINI agar $conn tersedia secara global
require_once '../config.php';

// 1. Definisi Variabel untuk Template
$pageTitle = 'Dashboard Asisten';
$activePage = 'dashboard';

// Panggil Header (akan menangani session_start() dan cek autentikasi)
require_once 'templates/header.php';

// Koneksi database ($conn) sekarang sudah tersedia dari require_once '../config.php';

// Ambil nama asisten dari session (sudah dipastikan ada karena cek di header.php)
$nama_asisten = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'Asisten';

// Query untuk mendapatkan statistik atau ringkasan untuk dashboard asisten
$total_praktikum = 0;
$total_modul = 0;
$total_laporan_belum_dinilai = 0;
$total_user_mahasiswa = 0;

// Query untuk total mata praktikum
$sql_praktikum_count = "SELECT COUNT(*) AS total FROM mata_praktikum";
$result_praktikum_count = $conn->query($sql_praktikum_count);
if ($result_praktikum_count) {
    $total_praktikum = $result_praktikum_count->fetch_assoc()['total'];
}

// Query untuk total modul
$sql_modul_count = "SELECT COUNT(*) AS total FROM modul";
$result_modul_count = $conn->query($sql_modul_count);
if ($result_modul_count) {
    $total_modul = $result_modul_count->fetch_assoc()['total'];
}

// Query untuk total laporan masuk (belum dinilai)
$sql_laporan_belum_dinilai_count = "SELECT COUNT(*) AS total FROM laporan WHERE status_penilaian = 'belum dinilai'";
$result_laporan_belum_dinilai_count = $conn->query($sql_laporan_belum_dinilai_count);
if ($result_laporan_belum_dinilai_count) {
    $total_laporan_belum_dinilai = $result_laporan_belum_dinilai_count->fetch_assoc()['total'];
}

// Query untuk total user mahasiswa
$sql_mahasiswa_count = "SELECT COUNT(*) AS total FROM users WHERE role = 'mahasiswa'";
$result_mahasiswa_count = $conn->query($sql_mahasiswa_count);
if ($result_mahasiswa_count) {
    $total_user_mahasiswa = $result_mahasiswa_count->fetch_assoc()['total'];
}

// Query untuk aktivitas laporan terbaru (contoh: 5 laporan terakhir)
$recent_laporan_sql = "
    SELECT
        l.tanggal_unggah,
        u.nama AS mahasiswa_nama,
        m.judul_modul AS modul_nama
    FROM
        laporan l
    JOIN
        users u ON l.user_id = u.id
    JOIN
        modul m ON l.modul_id = m.id
    ORDER BY
        l.tanggal_unggah DESC
    LIMIT 5
";
$recent_laporan_result = $conn->query($recent_laporan_sql);
$recent_laporan_list = [];
if ($recent_laporan_result) {
    while ($row = $recent_laporan_result->fetch_assoc()) {
        $recent_laporan_list[] = $row;
    }
}

// Tutup koneksi database di sini, setelah semua query selesai
$conn->close();
?>

<!-- Konten spesifik Dashboard Asisten -->
<div class="bg-pastel-accent p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-2xl font-semibold text-gray-800 mb-2">Selamat Datang Kembali, <?php echo htmlspecialchars($nama_asisten); ?>!</h3>
    <p class="text-gray-700">Ini adalah ringkasan aktivitas sistem praktikum.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Total Mata Praktikum -->
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-pastel-main p-3 rounded-full">
            <svg class="w-6 h-6 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Mata Praktikum</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_praktikum; ?></p>
        </div>
    </div>

    <!-- Total Modul -->
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-pastel-green p-3 rounded-full">
            <svg class="w-6 h-6 text-green-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_modul; ?></p>
        </div>
    </div>

    <!-- Laporan Belum Dinilai -->
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-pastel-orange p-3 rounded-full">
            <svg class="w-6 h-6 text-orange-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_laporan_belum_dinilai; ?></p>
        </div>
    </div>
    <!-- Tambahan: Total Mahasiswa -->
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-pastel-purple p-3 rounded-full">
            <svg class="w-6 h-6 text-purple-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        </div>
        <div>
            <p class="text-sm text-gray-500">Total Mahasiswa</p>
            <p class="text-2xl font-bold text-gray-800"><?php echo $total_user_mahasiswa; ?></p>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-lg shadow-md mt-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
    <div class="space-y-4">
        <?php if (empty($recent_laporan_list)): ?>
            <p class="text-gray-600">Belum ada aktivitas laporan terbaru.</p>
        <?php else: ?>
            <?php foreach ($recent_laporan_list as $laporan): ?>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                        <span class="font-bold text-gray-500"><?php echo htmlspecialchars(substr($laporan['mahasiswa_nama'], 0, 2)); ?></span>
                    </div>
                    <div>
                        <p class="text-gray-800"><strong><?php echo htmlspecialchars($laporan['mahasiswa_nama']); ?></strong> mengumpulkan laporan untuk <strong><?php echo htmlspecialchars($laporan['modul_nama']); ?></strong></p>
                        <p class="text-sm text-gray-500"><?php echo time_elapsed_string($laporan['tanggal_unggah']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Fungsi helper untuk format waktu (Anda bisa letakkan di file helper.php terpisah jika mau)
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' lalu' : 'baru saja';
}

// Panggil Footer
require_once 'templates/footer.php';
?>
