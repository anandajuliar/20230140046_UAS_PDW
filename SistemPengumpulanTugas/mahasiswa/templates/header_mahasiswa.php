<?php
// Mulai session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah pengguna sudah login dan memiliki role 'mahasiswa'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    $_SESSION['error_message'] = "Anda harus login sebagai mahasiswa untuk mengakses halaman ini.";
    header("Location: /SistemPengumpulanTugas/login.php"); // Path absolut ke login.php
    exit();
}

// Ambil nama mahasiswa dari session
$nama_mahasiswa = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'Mahasiswa';

// Variabel untuk judul halaman dan active link (didefinisikan di file yang memanggil header ini)
global $pageTitle, $activePage;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'SIMPRAK'); ?> - SIMPRAK Mahasiswa</title>
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
                <a href="/SistemPengumpulanTugas/index.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Katalog</a>
                <a href="/SistemPengumpulanTugas/mahasiswa/dashboard.php" class="px-3 py-2 rounded-md <?php echo ($activePage === 'dashboard') ? 'bg-pastel-darker' : ''; ?> hover:bg-pastel-darker transition-colors duration-200">Dashboard Saya</a>
                <a href="/SistemPengumpulanTugas/logout.php" class="px-3 py-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors duration-200">Logout</a>
            </nav>
        </div>
    </header>
    <main class="flex-grow container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center"><?php echo htmlspecialchars($pageTitle ?? 'Halaman'); ?></h2>
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
