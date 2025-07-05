<?php
// AKTIFKAN PELAPORAN ERROR PHP DENGAN SANGAT LENGKAP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database DI SINI
require_once '../config.php';

// --- DEBUGGING START ---
echo "DEBUG: config.php berhasil di-include.<br>";
if (isset($conn) && $conn instanceof mysqli) {
    echo "DEBUG: Variabel \$conn ditemukan dan merupakan objek mysqli.<br>";
    if ($conn->connect_error) {
        // Jika ada error koneksi, tampilkan dan hentikan script
        die("FATAL ERROR: Koneksi database gagal: " . $conn->connect_error);
    } else {
        echo "DEBUG: Koneksi database berhasil. Host Info: " . $conn->host_info . "<br>";
    }
} else {
    // Jika $conn tidak valid, tampilkan error dan hentikan script
    die("FATAL ERROR: Variabel \$conn tidak valid setelah include config.php. Periksa config.php.");
}
// --- DEBUGGING END ---


// Mulai session DI SINI (jika belum dimulai oleh config.php atau file lain yang di-include)
// Namun, karena header.php akan dipanggil dan ia memulai session, ini bisa diabaikan jika header.php dipanggil pertama.
// Tapi untuk memastikan, bisa diletakkan di sini juga atau pastikan header.php dipanggil sebelum ada output.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "DEBUG: Session baru dimulai.<br>";
} else {
    echo "DEBUG: Session sudah aktif.<br>";
}

// Definisi Variabel untuk Template
$pageTitle = 'Laporan Masuk';
$activePage = 'laporan_masuk';

// Panggil Header (akan menangani cek autentikasi dan menampilkan bagian atas HTML)
require_once 'templates/header.php';

// Koneksi database ($conn) sudah tersedia dari require_once '../config.php';

// --- Filter Variables ---
$filter_modul_id = filter_input(INPUT_GET, 'modul_id', FILTER_VALIDATE_INT);
$filter_mahasiswa_id = filter_input(INPUT_GET, 'mahasiswa_id', FILTER_VALIDATE_INT);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);

// --- Query untuk mengambil data laporan ---
$sql_laporan = "
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
        mp.nama_praktikum
    FROM
        laporan l
    JOIN
        users u ON l.user_id = u.id
    JOIN
        modul m ON l.modul_id = m.id
    JOIN
        mata_praktikum mp ON m.praktikum_id = mp.id
    WHERE 1=1 -- Kondisi dummy agar bisa menambahkan AND di bawah
";

$params = [];
$types = "";

if ($filter_modul_id) {
    $sql_laporan .= " AND m.id = ?";
    $params[] = $filter_modul_id;
    $types .= "i";
}
if ($filter_mahasiswa_id) {
    $sql_laporan .= " AND u.id = ?";
    $params[] = $filter_mahasiswa_id;
    $types .= "i";
}
if ($filter_status && in_array($filter_status, ['belum dinilai', 'sudah dinilai'])) {
    $sql_laporan .= " AND l.status_penilaian = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql_laporan .= " ORDER BY l.tanggal_unggah DESC";

$stmt_laporan = $conn->prepare($sql_laporan);
if ($stmt_laporan === false) {
    die("Error preparing laporan statement: " . $conn->error);
}

if (!empty($params)) {
    $stmt_laporan->bind_param($types, ...$params);
}

$stmt_laporan->execute();
$result_laporan = $stmt_laporan->get_result();

$laporan_list = [];
if ($result_laporan->num_rows > 0) {
    while($row = $result_laporan->fetch_assoc()) {
        $laporan_list[] = $row;
    }
}
$stmt_laporan->close();

// --- Query untuk opsi filter (dropdown) ---
// Ambil semua modul
$modul_options = [];
$sql_modul_options = "SELECT id, judul_modul, praktikum_id FROM modul ORDER BY judul_modul ASC";
$result_modul_options = $conn->query($sql_modul_options);
if ($result_modul_options && $result_modul_options->num_rows > 0) {
    while($row = $result_modul_options->fetch_assoc()) {
        $modul_options[] = $row;
    }
}

// Ambil semua mahasiswa
$mahasiswa_options = [];
$sql_mahasiswa_options = "SELECT id, nama, email FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC";
$result_mahasiswa_options = $conn->query($sql_mahasiswa_options);
if ($result_mahasiswa_options && $result_mahasiswa_options->num_rows > 0) {
    while($row = $result_mahasiswa_options->fetch_assoc()) {
        $mahasiswa_options[] = $row;
    }
}

$conn->close(); // Tutup koneksi database setelah semua query selesai
?>

<!-- Konten spesifik Laporan Masuk -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Filter Laporan</h3>
    <form action="laporan_masuk.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="modul_id" class="block text-gray-700 text-sm font-bold mb-2">Modul:</label>
            <select id="modul_id" name="modul_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Semua Modul</option>
                <?php foreach ($modul_options as $modul): ?>
                    <option value="<?php echo htmlspecialchars($modul['id']); ?>" <?php echo ($filter_modul_id == $modul['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($modul['judul_modul']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mahasiswa_id" class="block text-gray-700 text-sm font-bold mb-2">Mahasiswa:</label>
            <select id="mahasiswa_id" name="mahasiswa_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Semua Mahasiswa</option>
                <?php foreach ($mahasiswa_options as $mahasiswa): ?>
                    <option value="<?php echo htmlspecialchars($mahasiswa['id']); ?>" <?php echo ($filter_mahasiswa_id == $mahasiswa['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($mahasiswa['nama']); ?> (<?php echo htmlspecialchars($mahasiswa['email']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status Penilaian:</label>
            <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Semua Status</option>
                <option value="belum dinilai" <?php echo ($filter_status == 'belum dinilai') ? 'selected' : ''; ?>>Belum Dinilai</option>
                <option value="sudah dinilai" <?php echo ($filter_status == 'sudah dinilai') ? 'selected' : ''; ?>>Sudah Dinilai</option>
            </select>
        </div>
        <div class="md:col-span-3 flex justify-end space-x-2">
            <button type="submit" class="bg-pastel-button hover:bg-pastel-button-darker text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors duration-200">
                Terapkan Filter
            </button>
            <a href="laporan_masuk.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors duration-200">
                Reset Filter
            </a>
        </div>
    </form>
</div>

<!-- Daftar Laporan -->
<h3 class="text-2xl font-bold text-gray-800 mb-4">Daftar Laporan Masuk</h3>
<?php if (empty($laporan_list)): ?>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-600">Tidak ada laporan yang ditemukan dengan filter yang dipilih.</p>
    </div>
<?php else: ?>
    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Mahasiswa
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Mata Praktikum
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Modul
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Tanggal Unggah
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Nilai
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($laporan_list as $laporan): ?>
                <tr>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($laporan['mahasiswa_nama']); ?></p>
                        <p class="text-gray-600 text-xs whitespace-no-wrap"><?php echo htmlspecialchars($laporan['mahasiswa_email']); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($laporan['judul_modul']); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo date('d M Y H:i', strtotime($laporan['tanggal_unggah'])); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php echo ($laporan['status_penilaian'] == 'sudah dinilai') ? 'text-green-900' : 'text-yellow-900'; ?>">
                            <span aria-hidden="true" class="absolute inset-0 <?php echo ($laporan['status_penilaian'] == 'sudah dinilai') ? 'bg-green-200' : 'bg-yellow-200'; ?> opacity-50 rounded-full"></span>
                            <span class="relative"><?php echo htmlspecialchars(ucwords($laporan['status_penilaian'])); ?></span>
                        </span>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap">
                            <?php echo !is_null($laporan['nilai']) ? htmlspecialchars($laporan['nilai']) : '-'; ?>
                        </p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <a href="nilai_laporan.php?id=<?php echo htmlspecialchars($laporan['laporan_id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Nilai</a>
                        <a href="../mahasiswa/download_file.php?file=<?php echo urlencode($laporan['file_laporan']); ?>" class="text-blue-600 hover:text-blue-900" target="_blank">Unduh</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
// Panggil Footer
require_once 'templates/footer.php';
?>
