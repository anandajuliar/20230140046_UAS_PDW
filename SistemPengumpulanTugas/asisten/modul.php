<?php
// Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database
require_once '../config.php';

// Definisi Variabel untuk Template
$pageTitle = 'Kelola Modul';
$activePage = 'modul';

// Panggil Header (akan menangani session_start() dan cek autentikasi)
require_once 'templates/header.php';

// Koneksi database ($conn) sudah tersedia dari require_once '../config.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // Default action is list
$edit_modul = null; // Untuk menyimpan data modul yang akan diedit

// Direktori untuk menyimpan file materi
$upload_dir = '../materi/'; // Pastikan folder ini ada di root proyek Anda

// Pastikan direktori upload ada
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Buat folder jika belum ada
}

// --- Handle CRUD Operations ---

// CREATE / UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $praktikum_id = filter_input(INPUT_POST, 'praktikum_id', FILTER_VALIDATE_INT);
    $judul_modul = trim($_POST['judul_modul']);
    $deskripsi = trim($_POST['deskripsi']);
    $urutan = filter_input(INPUT_POST, 'urutan', FILTER_VALIDATE_INT);
    $modul_id = filter_input(INPUT_POST, 'modul_id', FILTER_VALIDATE_INT);
    $current_file_materi = $_POST['current_file_materi'] ?? null; // File materi yang sudah ada

    $file_materi_path = $current_file_materi; // Default ke file yang sudah ada

    // Validasi input
    if (!$praktikum_id || empty($judul_modul) || !$urutan) {
        $_SESSION['error_message'] = "Semua field wajib diisi (Praktikum, Judul Modul, Urutan).";
    } else {
        // Handle file upload
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['file_materi']['tmp_name'];
            $file_name = basename($_FILES['file_materi']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'docx'];

            if (in_array($file_ext, $allowed_ext)) {
                // Buat nama file unik untuk menghindari konflik
                $new_file_name = uniqid('materi_') . '.' . $file_ext;
                $destination_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $destination_path)) {
                    $file_materi_path = 'materi/' . $new_file_name; // Simpan path relatif ke database

                    // Hapus file lama jika ada dan ini adalah update
                    if ($modul_id && $current_file_materi && file_exists('../' . $current_file_materi)) {
                        unlink('../' . $current_file_materi);
                    }
                } else {
                    $_SESSION['error_message'] = "Gagal mengunggah file materi.";
                    header("Location: modul.php");
                    exit();
                }
            } else {
                $_SESSION['error_message'] = "Format file materi tidak diizinkan. Hanya PDF dan DOCX.";
                header("Location: modul.php");
                exit();
            }
        }

        if ($modul_id) { // UPDATE operation
            $sql = "UPDATE modul SET praktikum_id = ?, judul_modul = ?, deskripsi = ?, urutan = ?, file_materi = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issisi", $praktikum_id, $judul_modul, $deskripsi, $urutan, $file_materi_path, $modul_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Modul berhasil diperbarui.";
            } else {
                $_SESSION['error_message'] = "Gagal memperbarui modul: " . $stmt->error;
            }
            $stmt->close();
        } else { // CREATE operation
            $sql = "INSERT INTO modul (praktikum_id, judul_modul, deskripsi, urutan, file_materi) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issis", $praktikum_id, $judul_modul, $deskripsi, $urutan, $file_materi_path);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Modul berhasil ditambahkan.";
            } else {
                $_SESSION['error_message'] = "Gagal menambahkan modul: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header("Location: modul.php"); // Redirect back to list view
    exit();
}

// DELETE operation
if ($action === 'delete') {
    $modul_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($modul_id) {
        // Ambil path file materi sebelum dihapus dari database
        $sql_get_file = "SELECT file_materi FROM modul WHERE id = ?";
        $stmt_get_file = $conn->prepare($sql_get_file);
        $stmt_get_file->bind_param("i", $modul_id);
        $stmt_get_file->execute();
        $stmt_get_file->bind_result($file_to_delete);
        $stmt_get_file->fetch();
        $stmt_get_file->close();

        $sql = "DELETE FROM modul WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $modul_id);
        if ($stmt->execute()) {
            // Hapus file fisik jika ada
            if ($file_to_delete && file_exists('../' . $file_to_delete)) {
                unlink('../' . $file_to_delete);
            }
            $_SESSION['success_message'] = "Modul berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus modul: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "ID modul tidak valid untuk dihapus.";
    }
    header("Location: modul.php"); // Redirect back to list view
    exit();
}

// EDIT form display
if ($action === 'edit') {
    $modul_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($modul_id) {
        $sql = "SELECT id, praktikum_id, judul_modul, deskripsi, urutan, file_materi FROM modul WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $modul_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $edit_modul = $result->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Modul tidak ditemukan.";
            header("Location: modul.php");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "ID modul tidak valid untuk diedit.";
        header("Location: modul.php");
        exit();
    }
}

// Fetch all mata_praktikum for the dropdown in the form
$mata_praktikum_options = [];
$sql_praktikum_options = "SELECT id, nama_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$result_praktikum_options = $conn->query($sql_praktikum_options);
if ($result_praktikum_options && $result_praktikum_options->num_rows > 0) {
    while($row = $result_praktikum_options->fetch_assoc()) {
        $mata_praktikum_options[] = $row;
    }
}

// READ operation (Fetch all modules for display)
$modul_list = [];
$sql_list = "
    SELECT
        m.id,
        m.judul_modul,
        m.deskripsi,
        m.urutan,
        m.file_materi,
        mp.nama_praktikum
    FROM
        modul m
    JOIN
        mata_praktikum mp ON m.praktikum_id = mp.id
    ORDER BY
        mp.nama_praktikum ASC, m.urutan ASC
";
$stmt_list = $conn->prepare($sql_list);
if ($stmt_list === false) {
    die("Error preparing list statement: " . $conn->error);
}
$stmt_list->execute();
$result_list = $stmt_list->get_result();

if ($result_list->num_rows > 0) {
    while($row = $result_list->fetch_assoc()) {
        $modul_list[] = $row;
    }
}
$stmt_list->close();
$conn->close(); // Tutup koneksi database setelah semua query selesai
?>

<!-- Konten spesifik Kelola Modul -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $action === 'edit' ? 'Edit Modul' : 'Tambah Modul Baru'; ?></h3>
    <form action="modul.php" method="POST" enctype="multipart/form-data">
        <?php if ($action === 'edit' && $edit_modul): ?>
            <input type="hidden" name="modul_id" value="<?php echo htmlspecialchars($edit_modul['id']); ?>">
            <input type="hidden" name="current_file_materi" value="<?php echo htmlspecialchars($edit_modul['file_materi'] ?? ''); ?>">
        <?php endif; ?>

        <div class="mb-4">
            <label for="praktikum_id" class="block text-gray-700 text-sm font-bold mb-2">Mata Praktikum:</label>
            <select id="praktikum_id" name="praktikum_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="">Pilih Mata Praktikum</option>
                <?php foreach ($mata_praktikum_options as $praktikum): ?>
                    <option value="<?php echo htmlspecialchars($praktikum['id']); ?>"
                        <?php echo ($edit_modul && $edit_modul['praktikum_id'] == $praktikum['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="judul_modul" class="block text-gray-700 text-sm font-bold mb-2">Judul Modul:</label>
            <input type="text" id="judul_modul" name="judul_modul" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($edit_modul['judul_modul'] ?? ''); ?>" required>
        </div>

        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea id="deskripsi" name="deskripsi" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($edit_modul['deskripsi'] ?? ''); ?></textarea>
        </div>

        <div class="mb-4">
            <label for="urutan" class="block text-gray-700 text-sm font-bold mb-2">Urutan Modul:</label>
            <input type="number" id="urutan" name="urutan" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($edit_modul['urutan'] ?? ''); ?>" required min="1">
        </div>

        <div class="mb-6">
            <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">File Materi (PDF/DOCX):</label>
            <input type="file" id="file_materi" name="file_materi" accept=".pdf,.docx" class="block w-full text-sm text-gray-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-full file:border-0
                file:text-sm file:font-semibold
                file:bg-pastel-button file:text-white
                hover:file:bg-pastel-button-darker">
            <?php if ($action === 'edit' && !empty($edit_modul['file_materi'])): ?>
                <p class="text-sm text-gray-600 mt-2">File saat ini: <a href="../<?php echo htmlspecialchars($edit_modul['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline"><?php echo htmlspecialchars(basename($edit_modul['file_materi'])); ?></a></p>
                <p class="text-xs text-gray-500">Unggah file baru untuk mengganti yang lama.</p>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-pastel-button hover:bg-pastel-button-darker text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors duration-200">
                <?php echo $action === 'edit' ? 'Update Modul' : 'Tambah Modul'; ?>
            </button>
            <?php if ($action === 'edit'): ?>
                <a href="modul.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Daftar Modul -->
<h3 class="text-2xl font-bold text-gray-800 mb-4">Daftar Modul</h3>
<?php if (empty($modul_list)): ?>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-600">Belum ada modul yang ditambahkan.</p>
    </div>
<?php else: ?>
    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Mata Praktikum
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Urutan
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Judul Modul
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Materi
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modul_list as $modul): ?>
                <tr>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($modul['nama_praktikum']); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($modul['urutan']); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($modul['judul_modul']); ?></p>
                        <p class="text-gray-600 text-xs"><?php echo htmlspecialchars(substr($modul['deskripsi'], 0, 50)); ?><?php echo (strlen($modul['deskripsi']) > 50) ? '...' : ''; ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <?php if (!empty($modul['file_materi'])): ?>
                            <a href="../<?php echo htmlspecialchars($modul['file_materi']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                Unduh (<?php echo htmlspecialchars(basename($modul['file_materi'])); ?>)
                            </a>
                        <?php else: ?>
                            <span class="text-gray-500">Tidak ada</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <a href="modul.php?action=edit&id=<?php echo htmlspecialchars($modul['id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <a href="modul.php?action=delete&id=<?php echo htmlspecialchars($modul['id']); ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini? Ini juga akan menghapus laporan terkait!');" class="text-red-600 hover:text-red-900">Hapus</a>
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
