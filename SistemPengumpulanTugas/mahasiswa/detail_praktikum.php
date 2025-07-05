<?php
// Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database
require_once __DIR__ . '/../config.php';

// 1. Definisi Variabel untuk Template
$pageTitle = 'Detail Praktikum';
$activePage = 'dashboard'; // Tetap aktifkan 'Dashboard Saya' di navigasi

// Panggil Header (akan menangani session_start() dan cek autentikasi)
// PERBAIKAN PATH FILE: header_mahasiswa.php
require_once __DIR__ . '/templates/header_mahasiswa.php';

// Koneksi database ($conn) sudah tersedia dari require_once '../config.php';

$user_id = $_SESSION['user_id'];
$nama_mahasiswa = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'Pengguna';

// Ambil praktikum_id dari URL (GET parameter)
$praktikum_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// echo "DEBUG: praktikum_id dari URL: " . htmlspecialchars($praktikum_id) . "<br>"; // Hapus debug jika sudah tidak diperlukan

if (!$praktikum_id) {
    $_SESSION['error_message'] = "ID Praktikum tidak valid.";
    header("Location: /SistemPengumpulanTugas/mahasiswa/dashboard.php"); // Kembali ke dashboard jika ID tidak valid
    exit();
}

// 1. Ambil detail mata praktikum
$praktikum_detail = null;
$sql_praktikum = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum WHERE id = ?";
$stmt_praktikum = $conn->prepare($sql_praktikum);
if ($stmt_praktikum === false) {
    die("Error preparing praktikum statement: " . $conn->error);
}
$stmt_praktikum->bind_param("i", $praktikum_id);
$stmt_praktikum->execute();
$result_praktikum = $stmt_praktikum->get_result();

if ($result_praktikum->num_rows > 0) {
    $praktikum_detail = $result_praktikum->fetch_assoc();
    // echo "DEBUG: Detail praktikum ditemukan: " . htmlspecialchars($praktikum_detail['nama_praktikum']) . "<br>"; // Hapus debug
} else {
    $_SESSION['error_message'] = "Praktikum tidak ditemukan atau Anda tidak terdaftar.";
    header("Location: /SistemPengumpulanTugas/mahasiswa/dashboard.php");
    exit();
}
$stmt_praktikum->close();

// 2. Periksa apakah mahasiswa terdaftar di praktikum ini (keamanan tambahan)
$check_enrollment_sql = "SELECT COUNT(*) FROM praktikum_mahasiswa WHERE user_id = ? AND praktikum_id = ?";
$stmt_check_enrollment = $conn->prepare($check_enrollment_sql);
if ($stmt_check_enrollment === false) {
    die("Error preparing enrollment check statement: " . $conn->error);
}
$stmt_check_enrollment->bind_param("ii", $user_id, $praktikum_id);
$stmt_check_enrollment->execute();
$stmt_check_enrollment->bind_result($is_enrolled);
$stmt_check_enrollment->fetch();
$stmt_check_enrollment->close();

// echo "DEBUG: Mahasiswa terdaftar di praktikum ini: " . $is_enrolled . "<br>"; // Hapus debug

if ($is_enrolled == 0) {
    $_SESSION['error_message'] = "Anda tidak terdaftar di praktikum ini.";
    header("Location: /SistemPengumpulanTugas/mahasiswa/dashboard.php");
    exit();
}

// 3. Ambil daftar modul untuk praktikum ini, beserta status laporan dan nilai mahasiswa
$sql_modul = "
    SELECT
        m.id AS modul_id,
        m.judul_modul,
        m.deskripsi AS modul_deskripsi,
        m.file_materi,
        m.urutan,
        l.id AS laporan_id,
        l.file_laporan,
        l.nilai,
        l.feedback,
        l.status_penilaian
    FROM
        modul m
    LEFT JOIN
        laporan l ON m.id = l.modul_id AND l.user_id = ?
    WHERE
        m.praktikum_id = ?
    ORDER BY
        m.urutan ASC
";

$stmt_modul = $conn->prepare($sql_modul);
if ($stmt_modul === false) {
    die("Error preparing modul statement: " . $conn->error);
}
$stmt_modul->bind_param("ii", $user_id, $praktikum_id);
$stmt_modul->execute();
$result_modul = $stmt_modul->get_result();

$modul_list = [];
if ($result_modul->num_rows > 0) {
    while($row = $result_modul->fetch_assoc()) {
        $modul_list[] = $row;
    }
    // echo "DEBUG: Ditemukan " . count($modul_list) . " modul untuk praktikum ini.<br>"; // Hapus debug
} else {
    // echo "DEBUG: Tidak ada modul ditemukan untuk praktikum ini.<br>"; // Hapus debug
}
$stmt_modul->close();
$conn->close(); // Tutup koneksi database setelah semua query selesai
?>

<!-- Konten spesifik Detail Praktikum -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($praktikum_detail['nama_praktikum']); ?></h3>
    <p class="text-sm text-gray-500 mb-3">Kode: <?php echo htmlspecialchars($praktikum_detail['kode_praktikum']); ?></p>
    <p class="text-gray-700 text-base"><?php echo htmlspecialchars($praktikum_detail['deskripsi']); ?></p>
</div>

<h3 class="text-2xl font-bold text-gray-800 mb-4">Daftar Modul & Tugas</h3>

<?php if (empty($modul_list)): ?>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-600">Belum ada modul yang tersedia untuk praktikum ini.</p>
    </div>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($modul_list as $modul): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h4 class="text-xl font-semibold text-gray-900 mb-2">Modul <?php echo htmlspecialchars($modul['urutan']); ?>: <?php echo htmlspecialchars($modul['judul_modul']); ?></h4>
                <p class="text-gray-700 text-base mb-4"><?php echo htmlspecialchars($modul['modul_deskripsi']); ?></p>

                <!-- Mengunduh Materi -->
                <?php if (!empty($modul['file_materi'])): ?>
                    <div class="mb-4">
                        <p class="text-gray-600 font-semibold">Materi Modul:</p>
                        <a href="download_file.php?file=<?php echo urlencode($modul['file_materi']); ?>" class="inline-flex items-center text-blue-600 hover:underline">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Unduh Materi (<?php echo htmlspecialchars(basename($modul['file_materi'])); ?>)
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 mb-4">Materi modul belum tersedia.</p>
                <?php endif; ?>

                <!-- Mengumpulkan Laporan -->
                <div class="mb-4">
                    <p class="text-gray-600 font-semibold">Pengumpulan Laporan:</p>
                    <?php if (!empty($modul['file_laporan'])): ?>
                        <p class="text-green-600">Anda sudah mengumpulkan laporan.</p>
                        <p class="text-sm text-gray-600">File Anda: <a href="download_file.php?file=<?php echo urlencode($modul['file_laporan']); ?>" class="text-blue-600 hover:underline"><?php echo htmlspecialchars(basename($modul['file_laporan'])); ?></a></p>
                        <!-- Opsi untuk unggah ulang, jika diizinkan -->
                        <details class="mt-2">
                            <summary class="text-blue-500 cursor-pointer hover:underline">Unggah Ulang Laporan?</summary>
                            <form action="upload_laporan.php" method="POST" enctype="multipart/form-data" class="mt-2 p-4 border rounded-md bg-gray-50">
                                <input type="hidden" name="modul_id" value="<?php echo $modul['modul_id']; ?>">
                                <input type="hidden" name="praktikum_id" value="<?php echo $praktikum_id; ?>">
                                <label for="file_laporan_<?php echo $modul['modul_id']; ?>" class="block text-gray-700 text-sm font-bold mb-2">Pilih File Laporan Baru (PDF/DOCX):</label>
                                <input type="file" id="file_laporan_<?php echo $modul['modul_id']; ?>" name="file_laporan" accept=".pdf,.docx" class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-pastel-button file:text-white
                                    hover:file:bg-pastel-button-darker" required>
                                <button type="submit" class="mt-3 bg-pastel-button text-white py-2 px-4 rounded-md hover:bg-pastel-button-darker focus:outline-none focus:ring-2 focus:ring-pastel-button focus:ring-opacity-50 transition-colors duration-200">
                                    Unggah Ulang
                                </button>
                            </form>
                        </details>
                    <?php else: ?>
                        <form action="upload_laporan.php" method="POST" enctype="multipart/form-data" class="mt-2">
                            <input type="hidden" name="modul_id" value="<?php echo $modul['modul_id']; ?>">
                            <input type="hidden" name="praktikum_id" value="<?php echo $praktikum_id; ?>">
                            <label for="file_laporan_<?php echo $modul['modul_id']; ?>" class="block text-gray-700 text-sm font-bold mb-2">Unggah File Laporan (PDF/DOCX):</label>
                            <input type="file" id="file_laporan_<?php echo $modul['modul_id']; ?>" name="file_laporan" accept=".pdf,.docx" class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-pastel-button file:text-white
                                hover:file:bg-pastel-button-darker" required>
                            <button type="submit" class="mt-3 bg-pastel-button text-white py-2 px-4 rounded-md hover:bg-pastel-button-darker focus:outline-none focus:ring-2 focus:ring-pastel-button focus:ring-opacity-50 transition-colors duration-200">
                                Unggah Laporan
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Melihat Nilai -->
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-gray-600 font-semibold">Nilai Laporan:</p>
                    <?php if (!is_null($modul['nilai'])): ?>
                        <p class="text-2xl font-bold <?php echo ($modul['nilai'] >= 70) ? 'text-green-600' : 'text-red-600'; ?>"><?php echo htmlspecialchars($modul['nilai']); ?></p>
                        <p class="text-gray-700 text-sm mt-2">Feedback: <?php echo !empty($modul['feedback']) ? htmlspecialchars($modul['feedback']) : '-'; ?></p>
                    <?php else: ?>
                        <p class="text-gray-500">Belum dinilai.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
// Panggil Footer
// PERBAIKAN PATH FILE: footer_mahasiswa.php
require_once __DIR__ . '/templates/footer_mahasiswa.php';
?>
