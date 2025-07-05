    <?php
    // Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Sertakan file konfigurasi database
    require_once 'config.php';

    // Mulai session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Redirect jika sudah login
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'mahasiswa') {
            header("Location: /SistemPengumpulanTugas/mahasiswa/dashboard.php");
        } elseif ($_SESSION['role'] === 'asisten') {
            header("Location: /SistemPengumpulanTugas/asisten/dashboard.php");
        }
        exit();
    }

    $nama = $email = $password = $confirm_password = "";
    $nama_err = $email_err = $password_err = $confirm_password_err = "";

    // Proses data form ketika form disubmit
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Validasi nama
        if (empty(trim($_POST["nama"]))) {
            $nama_err = "Mohon masukkan nama lengkap.";
        } else {
            $nama = trim($_POST["nama"]);
        }

        // Validasi email
        if (empty(trim($_POST["email"]))) {
            $email_err = "Mohon masukkan email.";
        } else {
            // Cek apakah email sudah terdaftar
            $sql = "SELECT id FROM users WHERE email = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_email);
                $param_email = trim($_POST["email"]);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows == 1) {
                        $email_err = "Email ini sudah terdaftar.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    echo "Terjadi kesalahan database.";
                }
                $stmt->close();
            }
        }

        // Validasi password
        if (empty(trim($_POST["password"]))) {
            $password_err = "Mohon masukkan password.";
        } elseif (strlen(trim($_POST["password"])) < 6) {
            $password_err = "Password harus memiliki setidaknya 6 karakter.";
        } else {
            $password = trim($_POST["password"]);
        }

        // Validasi konfirmasi password
        if (empty(trim($_POST["confirm_password"]))) {
            $confirm_password_err = "Mohon konfirmasi password.";
        } else {
            $confirm_password = trim($_POST["confirm_password"]);
            if (empty($password_err) && ($password != $confirm_password)) {
                $confirm_password_err = "Password tidak cocok.";
            }
        }

        // Cek input errors sebelum insert ke database
        if (empty($nama_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {

            // Siapkan statement INSERT
            $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'mahasiswa')";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sss", $param_nama, $param_email, $param_password);

                // Set parameter
                $param_nama = $nama;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash password

                // Coba eksekusi prepared statement
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Registrasi berhasil! Silakan login.";
                    header("Location: /SistemPengumpulanTugas/login.php"); // Path absolut
                    exit();
                } else {
                    $_SESSION['error_message'] = "Gagal registrasi: " . $stmt->error;
                }
                $stmt->close();
            }
        }
        $conn->close();
    }
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register - SIMPRAK</title>
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
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Register Akun Baru</h2>

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
                    <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
                    <input type="text" id="nama" name="nama" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($nama); ?>" required>
                    <span class="text-red-500 text-xs italic"><?php echo $nama_err; ?></span>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                    <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($email_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required>
                    <span class="text-red-500 text-xs italic"><?php echo $email_err; ?></span>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                    <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($password); ?>" required>
                    <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
                </div>
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($confirm_password); ?>" required>
                    <span class="text-red-500 text-xs italic"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-pastel-button hover:bg-pastel-button-darker text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors duration-200">
                        Register
                    </button>
                    <a href="/SistemPengumpulanTugas/login.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                        Sudah punya akun? Login
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer (opsional, jika ingin ada footer di halaman register) -->
        <footer class="bg-pastel-footer text-gray-800 p-4 text-center mt-8 w-full">
            <div class="container mx-auto">
                <p>&copy; <?php echo date('Y'); ?> SIMPRAK. All rights reserved.</p>
            </div>
        </footer>
    </body>
    </html>
    