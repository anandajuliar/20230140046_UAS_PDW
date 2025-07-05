<?php
// Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database (perhatikan path relatif)
require_once '../config.php';

// Mulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah pengguna sudah login dan memiliki role 'asisten'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    $_SESSION['error_message'] = "Anda harus login sebagai asisten untuk mengakses halaman ini.";
    header("Location: ../login.php"); // Arahkan kembali ke halaman login
    exit();
}

$user_id = $_SESSION['user_id'];
$nama_asisten = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'Asisten';

$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // Default action is list
$edit_praktikum = null; // Untuk menyimpan data praktikum yang akan diedit

// --- Handle CRUD Operations ---

// CREATE / UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $kode_praktikum = trim($_POST['kode_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    $praktikum_id = filter_input(INPUT_POST, 'praktikum_id', FILTER_VALIDATE_INT);

    if (empty($nama_praktikum) || empty($kode_praktikum)) {
        $_SESSION['error_message'] = "Nama praktikum dan Kode praktikum tidak boleh kosong.";
    } else {
        if ($praktikum_id) { // UPDATE operation
            $sql = "UPDATE mata_praktikum SET nama_praktikum = ?, kode_praktikum = ?, deskripsi = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nama_praktikum, $kode_praktikum, $deskripsi, $praktikum_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Mata praktikum berhasil diperbarui.";
            } else {
                $_SESSION['error_message'] = "Gagal memperbarui mata praktikum: " . $stmt->error;
            }
            $stmt->close();
        } else { // CREATE operation
            // Cek duplikasi kode_praktikum
            $check_sql = "SELECT COUNT(*) FROM mata_praktikum WHERE kode_praktikum = ?";
            $stmt_check = $conn->prepare($check_sql);
            $stmt_check->bind_param("s", $kode_praktikum);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($count > 0) {
                $_SESSION['error_message'] = "Kode praktikum sudah ada. Mohon gunakan kode lain.";
            } else {
                $sql = "INSERT INTO mata_praktikum (nama_praktikum, kode_praktikum, deskripsi) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $nama_praktikum, $kode_praktikum, $deskripsi);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Mata praktikum berhasil ditambahkan.";
                } else {
                    $_SESSION['error_message'] = "Gagal menambahkan mata praktikum: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    header("Location: praktikum.php"); // Redirect back to list view
    exit();
}

// DELETE operation
if ($action === 'delete') {
    $praktikum_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($praktikum_id) {
        $sql = "DELETE FROM mata_praktikum WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $praktikum_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Mata praktikum berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Gagal menghapus mata praktikum: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "ID praktikum tidak valid untuk dihapus.";
    }
    header("Location: praktikum.php"); // Redirect back to list view
    exit();
}

// EDIT form display
if ($action === 'edit') {
    $praktikum_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($praktikum_id) {
        $sql = "SELECT id, nama_praktikum, kode_praktikum, deskripsi FROM mata_praktikum WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $praktikum_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $edit_praktikum = $result->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Mata praktikum tidak ditemukan.";
            header("Location: praktikum.php");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "ID praktikum tidak valid untuk diedit.";
        header("Location: praktikum.php");
        exit();
    }
}

// READ operation (Fetch all practical courses for display)
$mata_praktikum_list = [];
$sql_list = "SELECT id, nama_praktikum, kode_praktikum, deskripsi, created_at, updated_at FROM mata_praktikum ORDER BY nama_praktikum ASC";
$stmt_list = $conn->prepare($sql_list);
if ($stmt_list === false) {
    die("Error preparing list statement: " . $conn->error);
}
$stmt_list->execute();
$result_list = $stmt_list->get_result();

if ($result_list->num_rows > 0) {
    while($row = $result_list->fetch_assoc()) {
        $mata_praktikum_list[] = $row;
    }
}
$stmt_list->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Praktikum - SIMPRAK</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom CSS untuk font Inter dan warna pastel */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        /* Definisi warna kustom untuk Tailwind */
        .bg-pastel-main {
            background-color: #e8afd9; /* Warna pastel utama (header) */
        }
        .hover\:bg-pastel-darker:hover {
            background-color: #d698c7; /* Warna pastel sedikit lebih gelap untuk hover navigasi */
        }
        .bg-pastel-button {
            background-color: #b37cb3; /* Warna untuk tombol, lebih intens dari pastel utama */
        }
        .hover\:bg-pastel-button-darker:hover {
            background-color: #9c689c; /* Warna hover untuk tombol */
        }
        .bg-pastel-footer {
            background-color: #afd2f5; /* Warna pastel untuk footer */
        }
        .bg-pastel-accent {
            background-color: #c9e2f5; /* Warna aksen pastel biru muda untuk info card */
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <!-- Header Aplikasi -->
    <header class="bg-pastel-main text-gray-800 p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">SIMPRAK</h1>
            <nav>
                <a href="../index.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Katalog</a>
                <a href="dashboard.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Dashboard Asisten</a>
                <a href="praktikum.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Kelola Praktikum</a>
                <a href="../logout.php" class="px-3 py-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors duration-200">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Konten Utama Kelola Mata Praktikum -->
    <main class="flex-grow container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Kelola Mata Praktikum</h2>

        <?php
        // Menampilkan pesan sukses, error, atau info dari session
        if (isset($_SESSION['success_message'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<strong class="font-bold">Sukses!</strong>';
            echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['success_message']) . '</span>';
            echo '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<strong class="font-bold">Error!</strong>';
            echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['error_message']) . '</span>';
            echo '</div>';
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['info_message'])) {
            echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<strong class="font-bold">Info!</strong>';
            echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['info_message']) . '</span>';
            echo '</div>';
            unset($_SESSION['info_message']);
        }
        ?>

        <!-- Form Tambah/Edit Mata Praktikum -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $action === 'edit' ? 'Edit Mata Praktikum' : 'Tambah Mata Praktikum Baru'; ?></h3>
            <form action="praktikum.php" method="POST">
                <?php if ($action === 'edit' && $edit_praktikum): ?>
                    <input type="hidden" name="praktikum_id" value="<?php echo htmlspecialchars($edit_praktikum['id']); ?>">
                <?php endif; ?>
                <div class="mb-4">
                    <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
                    <input type="text" id="nama_praktikum" name="nama_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($edit_praktikum['nama_praktikum'] ?? ''); ?>" required>
                </div>
                <div class="mb-4">
                    <label for="kode_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Kode Praktikum:</label>
                    <input type="text" id="kode_praktikum" name="kode_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($edit_praktikum['kode_praktikum'] ?? ''); ?>" required>
                </div>
                <div class="mb-6">
                    <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
                    <textarea id="deskripsi" name="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($edit_praktikum['deskripsi'] ?? ''); ?></textarea>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-pastel-button hover:bg-pastel-button-darker text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors duration-200">
                        <?php echo $action === 'edit' ? 'Update Praktikum' : 'Tambah Praktikum'; ?>
                    </button>
                    <?php if ($action === 'edit'): ?>
                        <a href="praktikum.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">Batal Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Daftar Mata Praktikum -->
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Daftar Mata Praktikum</h3>
        <?php if (empty($mata_praktikum_list)): ?>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <p class="text-gray-600">Belum ada mata praktikum yang ditambahkan.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Nama Praktikum
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Kode
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Deskripsi
                            </th>
                            <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mata_praktikum_list as $praktikum): ?>
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <p class="text-gray-900"><?php echo htmlspecialchars(substr($praktikum['deskripsi'], 0, 100)); ?><?php echo (strlen($praktikum['deskripsi']) > 100) ? '...' : ''; ?></p>
                            </td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <a href="praktikum.php?action=edit&id=<?php echo htmlspecialchars($praktikum['id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                <a href="praktikum.php?action=delete&id=<?php echo htmlspecialchars($praktikum['id']); ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus praktikum ini? Ini akan menghapus semua modul dan pendaftaran terkait!');" class="text-red-600 hover:text-red-900">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer Aplikasi -->
    <footer class="bg-pastel-footer text-gray-800 p-4 text-center mt-8">
        <div class="container mx-auto">
            <p>&copy; <?php echo date('Y'); ?> SIMPRAK. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
