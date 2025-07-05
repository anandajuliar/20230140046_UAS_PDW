<?php
// Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "DEBUG: Script download_file.php dimulai.<br>";

// Sertakan file konfigurasi database (perhatikan path relatif)
require_once '../config.php';

// Mulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "DEBUG: Session baru dimulai.<br>";
} else {
    echo "DEBUG: Session sudah aktif.<br>";
}

// Ambil path file dari parameter GET
$file_path_param = filter_input(INPUT_GET, 'file', FILTER_SANITIZE_URL);

echo "DEBUG: Parameter file dari URL: " . htmlspecialchars($file_path_param) . "<br>";

if (!$file_path_param) {
    die("Parameter file tidak ditemukan.");
}

// Base directory tempat file materi dan laporan disimpan (relatif dari root proyek)
// Contoh: SistemPengumpulanTugas/materi/ atau SistemPengumpulanTugas/laporan/
$base_upload_dir = realpath(__DIR__ . '/../'); // Ini akan mengarah ke root proyek (SistemPengumpulanTugas/)
$full_file_path = $base_upload_dir . '/' . $file_path_param;

echo "DEBUG: Base upload directory: " . htmlspecialchars($base_upload_dir) . "<br>";
echo "DEBUG: Full file path yang dicari: " . htmlspecialchars($full_file_path) . "<br>";

// --- Keamanan KRUSIAL: Mencegah Directory Traversal ---
// Pastikan path yang diminta tidak keluar dari direktori upload yang diizinkan
if (strpos($full_file_path, $base_upload_dir) !== 0) {
    echo "DEBUG: Akses ditolak: File di luar direktori dasar.<br>";
    die("Akses file tidak diizinkan (Outside base directory).");
}

// Periksa apakah file benar-benar ada dan merupakan file
if (!file_exists($full_file_path)) {
    echo "DEBUG: File FISIK TIDAK ditemukan di server.<br>";
    die("File tidak ditemukan.");
}
if (!is_file($full_file_path)) {
    echo "DEBUG: Path BUKAN FILE (mungkin folder atau link).<br>";
    die("File tidak ditemukan atau akses ditolak."); // Lebih spesifik: "Path bukan file."
}

echo "DEBUG: File fisik ditemukan: " . htmlspecialchars($full_file_path) . "<br>";

// --- Kontrol Akses Berdasarkan Tipe File dan Role Pengguna ---
$is_material = (strpos($file_path_param, 'materi/') === 0);
$is_report = (strpos($file_path_param, 'laporan/') === 0);

$allowed_to_download = false;

if (!isset($_SESSION['user_id'])) {
    echo "DEBUG: Pengguna belum login. Akses ditolak.<br>";
    die("Anda harus login untuk mengunduh file ini.");
} else {
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    echo "DEBUG: User ID: " . $user_id . ", Role: " . htmlspecialchars($user_role) . "<br>";

    if ($is_material) {
        // Materi bisa diunduh oleh siapa saja yang sudah login (mahasiswa/asisten)
        $allowed_to_download = true;
        echo "DEBUG: File adalah materi. Diizinkan karena login.<br>";
    } elseif ($is_report) {
        // Laporan memerlukan pemeriksaan lebih lanjut
        echo "DEBUG: File adalah laporan. Melakukan pemeriksaan izin.<br>";
        // Dapatkan laporan_id dari database berdasarkan file_path_param
        $sql_check_report = "SELECT user_id FROM laporan WHERE file_laporan = ?";
        $stmt_check_report = $conn->prepare($sql_check_report);
        if ($stmt_check_report === false) {
            echo "DEBUG: Error menyiapkan query cek laporan: " . $conn->error . "<br>";
            die("Error menyiapkan query cek laporan: " . $conn->error);
        }
        $stmt_check_report->bind_param("s", $file_path_param);
        $stmt_check_report->execute();
        $result_check_report = $stmt_check_report->get_result();

        if ($result_check_report->num_rows > 0) {
            $report_owner_id = $result_check_report->fetch_assoc()['user_id'];
            echo "DEBUG: Pemilik laporan (user_id): " . $report_owner_id . "<br>";

            if ($user_role === 'mahasiswa' && $user_id == $report_owner_id) {
                // Mahasiswa hanya bisa mengunduh laporannya sendiri
                $allowed_to_download = true;
                echo "DEBUG: Mahasiswa mengunduh laporannya sendiri. Diizinkan.<br>";
            } elseif ($user_role === 'asisten') {
                // Asisten bisa mengunduh semua laporan
                $allowed_to_download = true;
                echo "DEBUG: Asisten mengunduh laporan. Diizinkan.<br>";
            } else {
                echo "DEBUG: Mahasiswa mencoba mengunduh laporan orang lain. Ditolak.<br>";
            }
        } else {
            echo "DEBUG: Laporan tidak ditemukan di database untuk path ini.<br>";
        }
        $stmt_check_report->close();
    } else {
        echo "DEBUG: Tipe file tidak diizinkan untuk diunduh (bukan materi/laporan).<br>";
    }
}

// Tutup koneksi database setelah semua pemeriksaan selesai
$conn->close();
echo "DEBUG: Koneksi database ditutup.<br>";

if (!$allowed_to_download) {
    echo "DEBUG: Akses ditolak berdasarkan kontrol izin.<br>";
    die("Akses ditolak. Anda tidak memiliki izin untuk mengunduh file ini.");
}

// --- Jika diizinkan, lanjutkan proses download ---
echo "DEBUG: Izin diberikan. Melanjutkan proses unduh.<br>";

// Tentukan Content-Type berdasarkan ekstensi file
$mime_type = mime_content_type($full_file_path);
if ($mime_type === false) {
    // Fallback jika mime_content_type gagal
    $extension = strtolower(pathinfo($full_file_path, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'pdf': $mime_type = 'application/pdf'; break;
        case 'docx': $mime_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'; break;
        case 'doc': $mime_type = 'application/msword'; break;
        case 'zip': $mime_type = 'application/zip'; break;
        default: $mime_type = 'application/octet-stream'; // Default untuk file yang tidak dikenal
    }
    echo "DEBUG: mime_content_type gagal, fallback ke: " . $mime_type . "<br>";
} else {
    echo "DEBUG: mime_type terdeteksi: " . $mime_type . "<br>";
}


// Set header untuk download
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . basename($full_file_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($full_file_path));

// Bersihkan output buffer sebelum membaca file
ob_clean();
flush();

// Baca file dan kirim ke browser
readfile($full_file_path);
exit;
?>
