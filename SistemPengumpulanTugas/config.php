<?php
// Pengaturan Database
define('DB_SERVER', '127.0.0.1'); // Bisa juga 'localhost'
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'pengumpulantugas'); // <--- PASTIKAN NAMA INI SESUAI DENGAN NAMA DATABASE ANDA DI PHPMYADMIN!

// Membuat koneksi ke database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// Set karakter set untuk koneksi (penting untuk menghindari masalah encoding)
$conn->set_charset("utf8mb4");

?>
