<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database
require_once 'config.php';

// Mulai session (jika belum dimulai, untuk fitur login/logout nanti)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Query untuk mengambil semua data mata_praktikum dari database
$sql = "SELECT id, nama_praktikum, deskripsi, kode_praktikum FROM mata_praktikum ORDER BY nama_praktikum ASC";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

$mata_praktikum = [];
if ($result->num_rows > 0) {
    // Ambil setiap baris data dan simpan ke array
    while($row = $result->fetch_assoc()) {
        $mata_praktikum[] = $row;
    }
}

// Tutup statement dan koneksi database setelah selesai mengambil data
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Mata Praktikum - SIMPRAK</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom CSS untuk font Inter dan warna pastel */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Warna latar belakang abu-abu muda, cocok dengan pastel */
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
                <?php /* Tombol Katalog dihapus dari sini karena ini adalah halaman katalog itu sendiri */ ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Tautan untuk pengguna yang sudah login -->
                    <?php if ($_SESSION['role'] === 'mahasiswa'): ?>
                        <a href="/SistemPengumpulanTugas/mahasiswa/dashboard.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Dashboard Saya</a>
                    <?php elseif ($_SESSION['role'] === 'asisten'): ?>
                        <a href="/SistemPengumpulanTugas/asisten/dashboard.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Dashboard Asisten</a>
                    <?php endif; ?>
                    <a href="/SistemPengumpulanTugas/logout.php" class="px-3 py-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors duration-200">Logout</a>
                <?php else: ?>
                    <!-- Tautan untuk pengguna yang belum login -->
                    <a href="/SistemPengumpulanTugas/login.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Login</a>
                    <a href="/SistemPengumpulanTugas/register.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Konten Utama Halaman Katalog -->
    <main class="flex-grow container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Katalog Mata Praktikum</h2>

        <?php
        // Menampilkan pesan sukses, error, atau info dari session
        if (isset($_SESSION['success_message'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<strong class="font-bold">Sukses!</strong>';
            echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['success_message']) . '</span>';
            echo '</div>';
            unset($_SESSION['success_message']); // Hapus pesan setelah ditampilkan
        }

        if (isset($_SESSION['error_message'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<strong class="font-bold">Error!</strong>';
            echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['error_message']) . '</span>';
            echo '</div>';
            unset($_SESSION['error_message']); // Hapus pesan setelah ditampilkan
        }

        if (isset($_SESSION['info_message'])) {
            echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<strong class="font-bold">Info!</strong>';
            echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['info_message']) . '</span>';
            echo '</div>';
            unset($_SESSION['info_message']); // Hapus pesan setelah ditampilkan
        }
        ?>

        <?php if (empty($mata_praktikum)): ?>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <p class="text-gray-600">Belum ada mata praktikum yang tersedia saat ini.</p>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'asisten'): ?>
                    <p class="mt-2 text-sm text-gray-500">Asisten dapat menambahkan mata praktikum melalui dashboard mereka.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($mata_praktikum as $praktikum): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                        <p class="text-sm text-gray-500 mb-3">Kode: <?php echo htmlspecialchars($praktikum['kode_praktikum']); ?></p>
                        <p class="text-gray-700 text-base mb-4"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa'): ?>
                            <!-- Tombol daftar hanya jika mahasiswa sudah login -->
                            <form action="/SistemPengumpulanTugas/daftar_praktikum.php" method="POST">
                                <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
                                <button type="submit" class="w-full bg-pastel-button text-white py-2 px-4 rounded-md hover:bg-pastel-button-darker focus:outline-none focus:ring-2 focus:ring-pastel-button focus:ring-opacity-50 transition-colors duration-200">
                                    Daftar Praktikum
                                </button>
                            </form>
                        <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] === 'asisten'): ?>
                            <p class="text-blue-500 text-sm">Asisten tidak bisa mendaftar praktikum.</p>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">Login sebagai mahasiswa untuk mendaftar.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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
