<?php
// Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database
require_once 'config.php';

// Mulai session (penting untuk menyimpan status login)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
    } elseif ($_SESSION['role'] === 'asisten') {
        header("Location: asisten/dashboard.php");
    }
    exit();
}

$email = $password = "";
$email_err = $password_err = "";

// Proses data form ketika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Mohon masukkan email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validasi password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Mohon masukkan password Anda.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Cek input credentials
    if (empty($email_err) && empty($password_err)) {
        // Siapkan statement SELECT
        $sql = "SELECT id, nama, email, password, role FROM users WHERE email = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind parameter ke statement
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            // Coba eksekusi prepared statement
            if ($stmt->execute()) {
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();

                    // Verifikasi password
                    if (password_verify($password, $user['password'])) {
                        // Password benar, mulai session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_nama'] = $user['nama']; 
                        $_SESSION['role'] = $user['role'];

                        // Arahkan ke dashboard sesuai role
                        if ($user['role'] === 'mahasiswa') {
                            header("Location: mahasiswa/dashboard.php");
                        } elseif ($user['role'] === 'asisten') {
                            header("Location: asisten/dashboard.php");
                        }
                        exit();
                    } else {
                        // Password salah
                        $_SESSION['error_message'] = "Email atau password salah.";
                        header("Location: login.php");
                        exit();
                    }
                } else {
                    // Email tidak ditemukan
                    $_SESSION['error_message'] = "Email atau password salah.";
                    header("Location: login.php");
                    exit();
                }
            } else {
                $_SESSION['error_message'] = "Terjadi kesalahan database saat eksekusi query.";
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Terjadi kesalahan database saat menyiapkan query.";
            header("Location: login.php");
            exit();
        }

        // Tutup statement
        $stmt->close();
    }
    // Tutup koneksi
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMPRAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .bg-pastel-main {
            background-color: #e8afd9;
        }
        .bg-pastel-button {
            background-color: #b37cb3;
        }
        .hover\:bg-pastel-button-darker:hover {
            background-color: #9c689c;
        }
        .bg-pastel-footer {
            background-color: #afd2f5;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen items-center justify-center">
    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login SIMPRAK</h2>

        <?php
        // Menampilkan pesan error atau sukses
        if (isset($_SESSION['error_message'])) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<strong class="font-bold">Error!</strong>';
            echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['error_message']) . '</span>';
            echo '</div>';
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">';
            echo '<strong class="font-bold">Sukses!</strong>';
            echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['success_message']) . '</span>';
            echo '</div>';
            unset($_SESSION['success_message']);
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($email_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required>
                <span class="text-red-500 text-xs italic"><?php echo $email_err; ?></span>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>" required>
                <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-pastel-button hover:bg-pastel-button-darker text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors duration-200">
                    Login
                </button>
                <a href="register.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    Belum punya akun? Register
                </a>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="bg-pastel-footer text-gray-800 p-4 text-center mt-8 w-full">
        <div class="container mx-auto">
            <p>&copy; <?php echo date('Y'); ?> SIMPRAK. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
