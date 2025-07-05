<?php
/**
 * File: mahasiswa/upload_laporan.php
 * Deskripsi: Menangani proses unggah file laporan oleh mahasiswa.
 * Fungsionalitas: Bagian dari Modul 2, Poin 4: Mengumpulkan Laporan.
 */

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

// Periksa apakah pengguna sudah login dan memiliki role 'mahasiswa'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    $_SESSION['error_message'] = "Anda harus login sebagai mahasiswa untuk mengunggah laporan.";
    header("Location: /SistemPengumpulanTugas/login.php");
    exit();
}

// Pastikan request adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modul_id = filter_input(INPUT_POST, 'modul_id', FILTER_VALIDATE_INT);
    $praktikum_id = filter_input(INPUT_POST, 'praktikum_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    // Direktori untuk menyimpan file laporan
    $upload_dir = '../laporan/'; // Pastikan folder ini ada di root proyek Anda

    // Pastikan direktori upload ada
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Buat folder jika belum ada
    }

    // Validasi input
    if (!$modul_id || !$praktikum_id) {
        $_SESSION['error_message'] = "ID Modul atau ID Praktikum tidak valid.";
        header("Location: /SistemPengumpulanTugas/mahasiswa/dashboard.php"); // Redirect ke dashboard
        exit();
    }

    // Handle file upload
    if (isset($_FILES['file_laporan']) && $_FILES['file_laporan']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['file_laporan']['tmp_name'];
        $file_name = basename($_FILES['file_laporan']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'docx'];

        if (in_array($file_ext, $allowed_ext)) {
            // Buat nama file unik untuk menghindari konflik (misal: laporan_modulID_userID_timestamp.ext)
            $new_file_name = 'laporan_' . $modul_id . '_' . $user_id . '_' . time() . '.' . $file_ext;
            $destination_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination_path)) {
                $file_laporan_path = 'laporan/' . $new_file_name; // Simpan path relatif ke database

                // Cek apakah mahasiswa sudah pernah mengumpulkan laporan untuk modul ini
                $check_sql = "SELECT id, file_laporan FROM laporan WHERE modul_id = ? AND user_id = ?";
                $stmt_check = $conn->prepare($check_sql);
                $stmt_check->bind_param("ii", $modul_id, $user_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    // Update laporan yang sudah ada (unggah ulang)
                    $existing_laporan = $result_check->fetch_assoc();
                    $existing_laporan_id = $existing_laporan['id'];
                    $old_file_to_delete = '../' . $existing_laporan['file_laporan']; // Path file lama untuk dihapus

                    $update_sql = "UPDATE laporan SET file_laporan = ?, tanggal_unggah = NOW(), nilai = NULL, feedback = NULL, status_penilaian = 'belum dinilai' WHERE id = ?";
                    $stmt_update = $conn->prepare($update_sql);
                    $stmt_update->bind_param("si", $file_laporan_path, $existing_laporan_id);

                    if ($stmt_update->execute()) {
                        // Hapus file lama jika ada dan berhasil diupdate
                        if (file_exists($old_file_to_delete) && is_file($old_file_to_delete)) {
                            unlink($old_file_to_delete);
                        }
                        $_SESSION['success_message'] = "Laporan berhasil diunggah ulang.";
                    } else {
                        $_SESSION['error_message'] = "Gagal mengunggah ulang laporan: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                    // Insert laporan baru
                    $insert_sql = "INSERT INTO laporan (modul_id, user_id, file_laporan, tanggal_unggah, status_penilaian) VALUES (?, ?, ?, NOW(), 'belum dinilai')";
                    $stmt_insert = $conn->prepare($insert_sql);
                    $stmt_insert->bind_param("iis", $modul_id, $user_id, $file_laporan_path);

                    if ($stmt_insert->execute()) {
                        $_SESSION['success_message'] = "Laporan berhasil diunggah.";
                    } else {
                        $_SESSION['error_message'] = "Gagal mengunggah laporan: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                }
                $stmt_check->close(); // Tutup statement cek
            } else {
                $_SESSION['error_message'] = "Gagal memindahkan file yang diunggah.";
            }
        } else {
            $_SESSION['error_message'] = "Format file tidak diizinkan. Hanya PDF dan DOCX.";
        }
    } else {
        $_SESSION['error_message'] = "Gagal mengunggah file. Error: " . $_FILES['file_laporan']['error'];
    }

    $conn->close();
    // Arahkan kembali ke halaman detail praktikum
    header("Location: /SistemPengumpulanTugas/mahasiswa/detail_praktikum.php?id=" . $praktikum_id);
    exit();

} else {
    // Jika bukan POST request, arahkan kembali ke dashboard
    header("Location: /SistemPengumpulanTugas/mahasiswa/dashboard.php");
    exit();
}
?>
