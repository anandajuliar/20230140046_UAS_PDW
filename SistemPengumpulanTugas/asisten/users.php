<?php
// Aktifkan pelaporan error PHP untuk debugging (hapus saat production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Sertakan file konfigurasi database
require_once '../config.php';

// Definisi Variabel untuk Template
$pageTitle = 'Kelola Akun Pengguna';
$activePage = 'users';

// Panggil Header (akan menangani session_start() dan cek autentikasi)
require_once 'templates/header.php';

// Koneksi database ($conn) sudah tersedia dari require_once '../config.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list'; // Default action is list
$edit_user = null; // Untuk menyimpan data user yang akan diedit

// --- Handle CRUD Operations ---

// CREATE / UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Password bisa kosong jika tidak diubah saat edit
    $role = trim($_POST['role']);
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    // Validasi input
    if (empty($nama) || empty($email) || empty($role)) {
        $_SESSION['error_message'] = "Nama, Email, dan Role tidak boleh kosong.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Format email tidak valid.";
    } elseif (!in_array($role, ['mahasiswa', 'asisten'])) {
        $_SESSION['error_message'] = "Role tidak valid. Pilih Mahasiswa atau Asisten.";
    } else {
        $hashed_password = null;
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        }

        if ($user_id) { // UPDATE operation
            // Ambil password lama jika password baru kosong
            if (is_null($hashed_password)) {
                $sql_get_old_pass = "SELECT password FROM users WHERE id = ?";
                $stmt_get_old_pass = $conn->prepare($sql_get_old_pass);
                $stmt_get_old_pass->bind_param("i", $user_id);
                $stmt_get_old_pass->execute();
                $stmt_get_old_pass->bind_result($old_password_hash);
                $stmt_get_old_pass->fetch();
                $stmt_get_old_pass->close();
                $hashed_password = $old_password_hash;
            }

            // Cek duplikasi email (kecuali untuk email user yang sedang diedit)
            $check_email_sql = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
            $stmt_check_email = $conn->prepare($check_email_sql);
            $stmt_check_email->bind_param("si", $email, $user_id);
            $stmt_check_email->execute();
            $stmt_check_email->bind_result($email_count);
            $stmt_check_email->fetch();
            $stmt_check_email->close();

            if ($email_count > 0) {
                $_SESSION['error_message'] = "Email sudah digunakan oleh akun lain.";
            } else {
                // Query UPDATE TANPA 'updated_at'
                $sql = "UPDATE users SET nama = ?, email = ?, password = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $nama, $email, $hashed_password, $role, $user_id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Akun pengguna berhasil diperbarui.";
                } else {
                    $_SESSION['error_message'] = "Gagal memperbarui akun pengguna: " . $stmt->error;
                }
                $stmt->close();
            }
        } else { // CREATE operation
            // Pastikan password tidak kosong untuk akun baru
            if (empty($password)) {
                $_SESSION['error_message'] = "Password tidak boleh kosong untuk akun baru.";
            } else {
                // Cek duplikasi email untuk akun baru
                $check_email_sql = "SELECT COUNT(*) FROM users WHERE email = ?";
                $stmt_check_email = $conn->prepare($check_email_sql);
                $stmt_check_email->bind_param("s", $email);
                $stmt_check_email->execute();
                $stmt_check_email->bind_result($email_count);
                $stmt_check_email->fetch();
                $stmt_check_email->close();

                if ($email_count > 0) {
                    $_SESSION['error_message'] = "Email sudah digunakan oleh akun lain.";
                } else {
                    // Query INSERT TANPA 'updated_at' (karena created_at sudah otomatis)
                    $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                    if ($stmt->execute()) {
                        $_SESSION['success_message'] = "Akun pengguna berhasil ditambahkan.";
                    } else {
                        $_SESSION['error_message'] = "Gagal menambahkan akun pengguna: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
    header("Location: users.php"); // Redirect back to list view
    exit();
}

// DELETE operation
if ($action === 'delete') {
    $user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($user_id) {
        // Pencegahan: Asisten tidak bisa menghapus akunnya sendiri
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error_message'] = "Anda tidak bisa menghapus akun Anda sendiri.";
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Akun pengguna berhasil dihapus.";
            } else {
                $_SESSION['error_message'] = "Gagal menghapus akun pengguna: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error_message'] = "ID pengguna tidak valid untuk dihapus.";
    }
    header("Location: users.php"); // Redirect back to list view
    exit();
}

// EDIT form display
if ($action === 'edit') {
    $user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($user_id) {
        $sql = "SELECT id, nama, email, role FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $edit_user = $result->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Akun pengguna tidak ditemukan.";
            header("Location: users.php");
            exit();
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "ID pengguna tidak valid untuk diedit.";
        header("Location: users.php");
        exit();
    }
}

// READ operation (Fetch all users for display)
// Query SELECT TANPA 'updated_at'
$user_list = [];
$sql_list = "SELECT id, nama, email, role, created_at FROM users ORDER BY nama ASC";
$stmt_list = $conn->prepare($sql_list);
if ($stmt_list === false) {
    die("Error preparing list statement: " . $conn->error);
}
$stmt_list->execute();
$result_list = $stmt_list->get_result();

if ($result_list->num_rows > 0) {
    while($row = $result_list->fetch_assoc()) {
        $user_list[] = $row;
    }
}
$stmt_list->close();
$conn->close(); // Tutup koneksi database setelah semua query selesai
?>

<!-- Konten spesifik Kelola Akun Pengguna -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4"><?php echo $action === 'edit' ? 'Edit Akun Pengguna' : 'Tambah Akun Pengguna Baru'; ?></h3>
    <form action="users.php" method="POST">
        <?php if ($action === 'edit' && $edit_user): ?>
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user['id']); ?>">
        <?php endif; ?>
        <div class="mb-4">
            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
            <input type="text" id="nama" name="nama" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($edit_user['nama'] ?? ''); ?>" required>
        </div>
        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
            <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password: <?php echo ($action === 'edit') ? '(Kosongkan jika tidak ingin mengubah)' : ''; ?></label>
            <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" <?php echo ($action !== 'edit') ? 'required' : ''; ?>>
        </div>
        <div class="mb-6">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role:</label>
            <select id="role" name="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="mahasiswa" <?php echo ($edit_user && $edit_user['role'] === 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo ($edit_user && $edit_user['role'] === 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-pastel-button hover:bg-pastel-button-darker text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition-colors duration-200">
                <?php echo $action === 'edit' ? 'Update Akun' : 'Tambah Akun'; ?>
            </button>
            <?php if ($action === 'edit'): ?>
                <a href="users.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Daftar Akun Pengguna -->
<h3 class="text-2xl font-bold text-gray-800 mb-4">Daftar Akun Pengguna</h3>
<?php if (empty($user_list)): ?>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <p class="text-gray-600">Belum ada akun pengguna yang terdaftar.</p>
    </div>
<?php else: ?>
    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Nama
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Email
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Role
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Terdaftar Sejak
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_list as $user): ?>
                <tr>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($user['nama']); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo htmlspecialchars($user['email']); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?php echo ($user['role'] === 'asisten') ? 'text-purple-900' : 'text-blue-900'; ?>">
                            <span aria-hidden="true" class="absolute inset-0 <?php echo ($user['role'] === 'asisten') ? 'bg-purple-200' : 'bg-blue-200'; ?> opacity-50 rounded-full"></span>
                            <span class="relative"><?php echo htmlspecialchars(ucwords($user['role'])); ?></span>
                        </span>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap"><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></p>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <a href="users.php?action=edit&id=<?php echo htmlspecialchars($user['id']); ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                        <?php if ($user['id'] != $_SESSION['user_id']): // Asisten tidak bisa menghapus akunnya sendiri ?>
                            <a href="users.php?action=delete&id=<?php echo htmlspecialchars($user['id']); ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini? Ini akan menghapus semua data terkait!');" class="text-red-600 hover:text-red-900">Hapus</a>
                        <?php else: ?>
                            <span class="text-gray-400">Hapus</span>
                        <?php endif; ?>
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