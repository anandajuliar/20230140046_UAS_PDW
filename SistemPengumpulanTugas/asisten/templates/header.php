<?php
// Mulai session (PENTING: session_start() harus di sini atau di file utama sebelum output apapun)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah pengguna sudah login dan memiliki role 'asisten'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    $_SESSION['error_message'] = "Anda harus login sebagai asisten untuk mengakses halaman ini.";
    header("Location: /SistemPengumpulanTugas/login.php"); // Path absolut ke login.php
    exit();
}

// Ambil nama asisten dari session (sudah dipastikan ada karena cek di atas)
$nama_asisten = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'Asisten';

// Variabel untuk judul halaman dan active link (didefinisikan di file yang memanggil header ini)
global $pageTitle, $activePage;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'SIMPRAK'); ?> - SIMPRAK Asisten</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .bg-pastel-main { background-color: #e8afd9; }
        .hover\:bg-pastel-darker:hover { background-color: #d698c7; }
        .bg-pastel-button { background-color: #b37cb3; }
        .hover\:bg-pastel-button-darker:hover { background-color: #9c689c; }
        .bg-pastel-footer { background-color: #afd2f5; }
        .bg-pastel-accent { background-color: #c9e2f5; }
        .bg-pastel-green { background-color: #d4edda; }
        .bg-pastel-orange { background-color: #ffeeba; }
        .bg-pastel-purple { background-color: #e2d9f3; }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <header class="bg-pastel-main text-gray-800 p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">SIMPRAK</h1>
            <nav>
                <a href="/SistemPengumpulanTugas/index.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Katalog</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'mahasiswa'): ?>
                        <a href="/SistemPengumpulanTugas/mahasiswa/dashboard.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Dashboard Saya</a>
                    <?php elseif ($_SESSION['role'] === 'asisten'): ?>
                        <a href="/SistemPengumpulanTugas/asisten/dashboard.php" class="px-3 py-2 rounded-md <?php echo ($activePage === 'dashboard') ? 'bg-pastel-darker' : ''; ?> hover:bg-pastel-darker transition-colors duration-200">Dashboard Asisten</a>
                        <a href="/SistemPengumpulanTugas/asisten/praktikum.php" class="px-3 py-2 rounded-md <?php echo ($activePage === 'praktikum') ? 'bg-pastel-darker' : ''; ?> hover:bg-pastel-darker transition-colors duration-200">Kelola Praktikum</a>
                        <a href="/SistemPengumpulanTugas/asisten/modul.php" class="px-3 py-2 rounded-md <?php echo ($activePage === 'modul') ? 'bg-pastel-darker' : ''; ?> hover:bg-pastel-darker transition-colors duration-200">Kelola Modul</a>
                        <a href="/SistemPengumpulanTugas/asisten/laporan_masuk.php" class="px-3 py-2 rounded-md <?php echo ($activePage === 'laporan_masuk') ? 'bg-pastel-darker' : ''; ?> hover:bg-pastel-darker transition-colors duration-200">Laporan Masuk</a>
                        <a href="/SistemPengumpulanTugas/asisten/users.php" class="px-3 py-2 rounded-md <?php echo ($activePage === 'users') ? 'bg-pastel-darker' : ''; ?> hover:bg-pastel-darker transition-colors duration-200">Kelola Pengguna</a>
                    <?php endif; ?>
                    <a href="/SistemPengumpulanTugas/logout.php" class="px-3 py-2 rounded-md bg-red-500 text-white hover:bg-red-600 transition-colors duration-200">Logout</a>
                <?php else: ?>
                    <a href="/SistemPengumpulanTugas/login.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Login</a>
                    <a href="/SistemPengumpulanTugas/register.php" class="px-3 py-2 rounded-md hover:bg-pastel-darker transition-colors duration-200">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="flex-grow container mx-auto p-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center"><?php echo htmlspecialchars($pageTitle ?? 'Halaman'); ?></h2>
        <?php
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
