<?php

// Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database
require_once '../config.php';

// Definisi Variabel untuk Template
$pageTitle = 'Nilai Laporan';
$activePage = 'laporan_masuk'; // Tetap aktifkan 'Laporan Masuk' di navigasi

// Panggil Header (akan menangani session_start() dan cek autentikasi)
require_once 'templates/header.php';

// Koneksi database ($conn) sudah tersedia dari require_once '../config.php';

// Ambil laporan_id dari URL (GET parameter)
$laporan_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$laporan_id) {
    $_SESSION['error_message'] = "ID Laporan tidak valid.";
    header("Location: /SistemPengumpulanTugas/asisten/laporan_masuk.php"); // Kembali ke daftar laporan jika ID tidak valid
    exit();
}

$laporan_detail = null;

// --- Handle Form Submission (POST Request) for Grading ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nilai = filter_input(INPUT_POST, 'nilai', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $feedback = trim($_POST['feedback']);
    $status_penilaian = 'sudah dinilai'; // Otomatis menjadi 'sudah dinilai' setelah diberi nilai

    // Validasi nilai (misal: antara 0-100)
    if (!is_numeric($nilai) || $nilai < 0 || $nilai > 100) {
        $_SESSION['error_message'] = "Nilai harus angka antara 0 dan 100.";
    } else {
        // MENGHAPUS 'updated_at = NOW()' karena kolom ini mungkin tidak ada di tabel laporan
        $sql_update = "UPDATE laporan SET nilai = ?, feedback = ?, status_penilaian = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        if ($stmt_update === false) {
            $_SESSION['error_message'] = "Gagal menyiapkan update nilai: " . $conn->error;
        } else {
            $stmt_update->bind_param("dssi", $nilai, $feedback, $status_penilaian, $laporan_id);
            if ($stmt_update->execute()) {
                $_SESSION['success_message'] = "Nilai dan feedback laporan berhasil disimpan.";
            } else {
                $_SESSION['error_message'] = "Gagal menyimpan nilai dan feedback: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
    header("Location: /SistemPengumpulanTugas/asisten/laporan_masuk.php"); // Redirect kembali ke daftar laporan
    exit();
}

// --- Fetch Laporan Details for Display ---
$sql_detail = "
    SELECT
        l.id AS laporan_id,
        l.file_laporan,
        l.tanggal_unggah,
        l.nilai,
        l.feedback,
        l.status_penilaian,
        u.nama AS mahasiswa_nama,
        u.email AS mahasiswa_email,
        m.judul_modul,
        mp.nama_praktikum,
        mp.kode_praktikum
    FROM
        laporan l
    JOIN
        users u ON l.user_id = u.id
    JOIN
        modul m ON l.modul_id = m.id
    JOIN
        mata_praktikum mp ON m.praktikum_id = mp.id
    WHERE
        l.id = ?
";

$stmt_detail = $conn->prepare($sql_detail);
if ($stmt_detail === false) {
    die("Error preparing detail statement: " . $conn->error);
}
$stmt_detail->bind_param("i", $laporan_id);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();

if ($result_detail->num_rows > 0) {
    $laporan_detail = $result_detail->fetch_assoc();
} else {
    $_SESSION['error_message'] = "Laporan tidak ditemukan.";
    header("Location: /SistemPengumpulanTugas/asisten/laporan_masuk.php");
    exit();
}
$stmt_detail->close();
$conn->close(); // Tutup koneksi database setelah semua query selesai
?>

<!-- Konten spesifik Nilai Laporan -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Detail Laporan Mahasiswa</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <p class="text-gray-600 font-semibold">Mahasiswa:</p>
            <p class="text-gray-800"><?php echo htmlspecialchars($laporan_detail['mahasiswa_nama']); ?> (<?php echo htmlspecialchars($laporan_detail['mahasiswa_email']); ?>)</p>
        </div>
        <div>
            <p class="text-gray-600 font-semibold">Mata Praktikum:</p>
            <p class="text-gray-800"><?php echo htmlspecialchars($laporan_detail['nama_praktikum']); ?> (<?php echo htmlspecialchars($laporan_detail['kode_praktikum']); ?>)</p>
        </div>
        <div>
            <p class="text-gray-600 font-semibold">Modul:</p>
            <p class="text-gray-800"><?php echo htmlspecialchars($laporan_detail['judul_modul']); ?></p>
        </div>
        <div>
            <p class="text-gray-600 font-semibold">Tanggal Unggah:</p>
            <p class="text-gray-800"><?php echo date('d M Y H:i', strtotime($laporan_detail['tanggal_unggah'])); ?></p>
        </div>
        <div>
            <p class="text-gray-600 font-semibold">Status Penilaian:</p>
            <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php echo ($laporan_detail['status_penilaian'] == 'sudah dinilai') ? 'text-green-900' : 'text-yellow-900'; ?>">
                <span aria-hidden="true" class="absolute inset-0 <?php echo ($laporan_detail['status_penilaian'] == 'sudah dinilai') ? 'bg-green-200' : 'bg-yellow-200'; ?> opacity-50 rounded-full"></span>
                <span class="relative"><?php echo htmlspecialchars(ucwords($laporan_detail['status_penilaian'])); ?></span>
            </span>
        </div>
        <div>
            <p class="text-gray-600 font-semibold">File Laporan:</p>
            <?php if (!empty($laporan_detail['file_laporan'])): ?>
                <a href="/SistemPengumpulanTugas/mahasiswa/download_file.php?file=<?php echo urlencode($laporan_detail['file_laporan']); ?>" class="text-blue-600 hover:underline inline-flex items-center" target="_blank">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Unduh Laporan (<?php echo htmlspecialchars(basename($laporan_detail['file_laporan'])); ?>)
                </a>
            <?php else: ?>
                <p class="text-red-500">File laporan tidak ditemukan.</p>
            <?php endif; ?>
        </div>
    </div>

    <h3 class="text-xl font-bold text-gray-800 mb-4 mt-6">Input Nilai & Feedback</h3>
    <form action="nilai_laporan.php?id=<?php echo htmlspecialchars($laporan_id); ?>" method="POST">
        <div class="mb-4">
            <label for="nilai" class="block text-gray-700 text-sm font-bold mb-2">Nilai (0-100):</label>
            <input type="number" id="nilai" name="nilai" step="0.01" min="0" max="100" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($laporan_detail['nilai'] ?? ''); ?>" required>
        </div>
        <div class="mb-6">
            <label for="feedback" class="block text-gray-700 text-sm font-bold mb-2">Feedback:</label>
            <textarea id="feedback" name="feedback" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($laporan_detail['feedback'] ?? ''); ?></textarea>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-pastel-button hover:bg-pastel-button-darker text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors duration-200">
                Simpan Nilai
            </button>
            <a href="/SistemPengumpulanTugas/asisten/laporan_masuk.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">Kembali ke Daftar Laporan</a>
        </div>
    </form>
</div>

<?php
// Panggil Footer
require_once 'templates/footer.php';
?>
