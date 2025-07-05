<?php
// Sertakan file konfigurasi database
require_once __DIR__ . '/../config.php';

// 1. Definisi Variabel untuk Template
$pageTitle = 'Dashboard Mahasiswa';
$activePage = 'dashboard';

// Panggil Header (akan menangani session_start() dan cek autentikasi)
// PERBAIKAN PATH FILE: header_mahasiswa.php
require_once __DIR__ . '/templates/header_mahasiswa.php';

// Koneksi database ($conn) sudah tersedia dari require_once '../config.php';

$user_id = $_SESSION['user_id'];
$nama_mahasiswa = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'Pengguna';

// Query untuk mengambil daftar praktikum yang diikuti oleh mahasiswa ini
$sql_praktikum_diikuti = "
    SELECT
        mp.id,
        mp.nama_praktikum,
        mp.kode_praktikum,
        mp.deskripsi,
        pm.tanggal_daftar
    FROM
        praktikum_mahasiswa pm
    JOIN
        mata_praktikum mp ON pm.praktikum_id = mp.id
    WHERE
        pm.user_id = ?
    ORDER BY
        pm.tanggal_daftar DESC
";

$stmt_praktikum_diikuti = $conn->prepare($sql_praktikum_diikuti);
if ($stmt_praktikum_diikuti === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt_praktikum_diikuti->bind_param("i", $user_id);
$stmt_praktikum_diikuti->execute();
$result_praktikum_diikuti = $stmt_praktikum_diikuti->get_result();

$praktikum_diikuti = [];
if ($result_praktikum_diikuti->num_rows > 0) {
    while($row = $result_praktikum_diikuti->fetch_assoc()) {
        $praktikum_diikuti[] = $row;
    }
}

$stmt_praktikum_diikuti->close();
$conn->close();
?>

<!-- Konten spesifik Dashboard Mahasiswa -->
<div class="bg-pastel-accent p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-2xl font-semibold text-gray-800 mb-2">Selamat Datang Kembali, <?php echo htmlspecialchars($nama_mahasiswa); ?>!</h3>
    <p class="text-gray-700">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
</div>

<h3 class="text-2xl font-bold text-gray-800 mb-4">Praktikum yang Diikuti</h3>

<?php if (empty($praktikum_diikuti)): ?>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-600">Anda belum mengikuti praktikum apa pun.</p>
        <p class="mt-2 text-gray-500">Silakan <a href="/SistemPengumpulanTugas/index.php" class="text-blue-600 hover:underline">cari praktikum</a> di katalog untuk mendaftar.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($praktikum_diikuti as $praktikum): ?>
            <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                <h4 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h4>
                <p class="text-sm text-gray-500 mb-3">Kode: <?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></p>
                <p class="text-gray-700 text-base mb-4"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
                <p class="text-sm text-gray-600">Terdaftar sejak: <?php echo date('d M Y', strtotime($praktikum['tanggal_daftar'])); ?></p>
                <a href="detail_praktikum.php?id=<?php echo $praktikum['id']; ?>" class="mt-4 inline-block bg-pastel-button text-white py-2 px-4 rounded-md hover:bg-pastel-button-darker focus:outline-none focus:ring-2 focus:ring-pastel-button focus:ring-opacity-50 transition-colors duration-200">
                    Lihat Detail & Tugas
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
// Panggil Footer
// PERBAIKAN PATH FILE: footer_mahasiswa.php
require_once __DIR__ . '/templates/footer_mahasiswa.php';
?>
