<?php
/**
 * API Router for NusaGrid GPU Portal
 * Handles CRUD actions for Users, GPU Services, and Rentals.
 */
//test

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once 'connection.php';
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Koneksi database gagal: ' . $e->getMessage()
    ]);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Authentication Gate Check
$publicActions = ['login', 'register', 'check_auth'];
if (!in_array($action, $publicActions)) {
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode([
            'status' => 'unauthorized',
            'message' => 'Silakan login terlebih dahulu untuk mengakses data.'
        ]);
        exit;
    }
}

switch ($action) {
    // === AUTHENTICATION ACTIONS ===
    case 'check_auth':
        handleCheckAuth();
        break;
    case 'login':
        handleLogin($pdo);
        break;
    case 'register':
        handleRegister($pdo);
        break;
    case 'logout':
        handleLogout();
        break;
    // === GPU SERVICES ACTIONS ===
    case 'read_gpus':
        handleReadGpus($pdo);
        break;
    case 'create_gpu':
        handleCreateGpu($pdo, $uploadDir);
        break;
    case 'update_gpu':
        handleUpdateGpu($pdo, $uploadDir);
        break;
    case 'delete_gpu':
        handleDeleteGpu($pdo, $uploadDir);
        break;

    // === USERS ACTIONS ===
    case 'read_users':
        handleReadUsers($pdo);
        break;
    case 'create_user':
        handleCreateUser($pdo);
        break;
    case 'update_user':
        handleUpdateUser($pdo);
        break;
    case 'delete_user':
        handleDeleteUser($pdo);
        break;

    // === RENTALS ACTIONS ===
    case 'read_rentals':
        handleReadRentals($pdo);
        break;
    case 'create_rental':
        handleCreateRental($pdo);
        break;
    case 'update_rental':
        handleUpdateRental($pdo);
        break;
    case 'delete_rental':
        handleDeleteRental($pdo);
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Action tidak dikenali atau kosong.'
        ]);
        break;
}

// ==========================================
// 1. GPU SERVICES CONTROLLER FUNCTIONS
// ==========================================

function handleReadGpus($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM gpu_services ORDER BY id DESC");
        $stmt->execute();
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membaca GPU: ' . $e->getMessage()]);
    }
}

function handleCreateGpu($pdo, $uploadDir) {
    $nama_gpu = isset($_POST['nama_gpu']) ? trim($_POST['nama_gpu']) : '';
    $harga = isset($_POST['harga']) ? trim($_POST['harga']) : '';
    $kebutuhan = isset($_POST['kebutuhan']) ? trim($_POST['kebutuhan']) : '';
    
    if (empty($nama_gpu) || empty($harga)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama GPU dan Harga wajib diisi.']);
        return;
    }

    $fotoPath = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileSize = $_FILES['foto']['size'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            if ($fileSize < 5242880) { // 5MB limit
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $fotoPath = $destPath;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file foto.']);
                    return;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'File foto maksimal 5MB.']);
                return;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Format file foto harus JPG, PNG, WEBP, atau GIF.']);
            return;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO gpu_services (nama_gpu, harga, kebutuhan, foto) VALUES (:nama_gpu, :harga, :kebutuhan, :foto)");
        $stmt->execute([
            ':nama_gpu' => $nama_gpu,
            ':harga' => $harga,
            ':kebutuhan' => $kebutuhan,
            ':foto' => $fotoPath
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Layanan GPU berhasil ditambahkan.']);
    } catch (PDOException $e) {
        if ($fotoPath && file_exists($fotoPath)) unlink($fotoPath);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()]);
    }
}

function handleUpdateGpu($pdo, $uploadDir) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_gpu = isset($_POST['nama_gpu']) ? trim($_POST['nama_gpu']) : '';
    $harga = isset($_POST['harga']) ? trim($_POST['harga']) : '';
    $kebutuhan = isset($_POST['kebutuhan']) ? trim($_POST['kebutuhan']) : '';
    
    if ($id <= 0 || empty($nama_gpu) || empty($harga)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap. ID, Nama GPU, dan Harga wajib diisi.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT foto FROM gpu_services WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $gpu = $stmt->fetch();
        if (!$gpu) {
            echo json_encode(['status' => 'error', 'message' => 'GPU tidak ditemukan.']);
            return;
        }

        $fotoPath = $gpu['foto'];
        $newPhotoUploaded = false;
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['foto']['tmp_name'];
            $fileName = $_FILES['foto']['name'];
            $fileSize = $_FILES['foto']['size'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                if ($fileSize < 5242880) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $destPath = $uploadDir . $newFileName;
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        if ($fotoPath && file_exists($fotoPath)) {
                            unlink($fotoPath);
                        }
                        $fotoPath = $destPath;
                        $newPhotoUploaded = true;
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload gambar baru.']);
                        return;
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gambar baru maksimal 5MB.']);
                    return;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Format gambar baru tidak didukung.']);
                return;
            }
        }

        $stmt = $pdo->prepare("UPDATE gpu_services SET nama_gpu = :nama_gpu, harga = :harga, kebutuhan = :kebutuhan, foto = :foto WHERE id = :id");
        $stmt->execute([
            ':nama_gpu' => $nama_gpu,
            ':harga' => $harga,
            ':kebutuhan' => $kebutuhan,
            ':foto' => $fotoPath,
            ':id' => $id
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Layanan GPU berhasil diperbarui.']);
    } catch (PDOException $e) {
        if ($newPhotoUploaded && $fotoPath && file_exists($fotoPath)) unlink($fotoPath);
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleDeleteGpu($pdo, $uploadDir) {
    $id = 0;
    if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $raw = json_decode(file_get_contents('php://input'), true);
        $id = isset($raw['id']) ? (int)$raw['id'] : 0;
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    }

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT foto FROM gpu_services WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $gpu = $stmt->fetch();
        
        if (!$gpu) {
            echo json_encode(['status' => 'error', 'message' => 'GPU tidak ditemukan.']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM gpu_services WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        if ($gpu['foto'] && file_exists($gpu['foto'])) {
            unlink($gpu['foto']);
        }
        echo json_encode(['status' => 'success', 'message' => 'Layanan GPU berhasil dihapus.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus GPU: ' . $e->getMessage()]);
    }
}

// ==========================================
// 2. USERS CONTROLLER FUNCTIONS
// ==========================================

function handleReadUsers($pdo) {
    try {
        // Exclude password hash from json response for standard users list
        $stmt = $pdo->prepare("SELECT id, username, email FROM users ORDER BY id DESC");
        $stmt->execute();
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data user: ' . $e->getMessage()]);
    }
}

function handleCreateUser($pdo) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Username, Email, dan Password wajib diisi.']);
        return;
    }

    try {
        // Check for duplicates
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $check->execute([':username' => $username, ':email' => $email]);
        if ($check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username atau Email sudah terdaftar.']);
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hash
        ]);
        echo json_encode(['status' => 'success', 'message' => 'User berhasil ditambahkan.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftarkan user: ' . $e->getMessage()]);
    }
}

function handleUpdateUser($pdo) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($id <= 0 || empty($username) || empty($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap. ID, Username, dan Email wajib diisi.']);
        return;
    }

    try {
        // Verify user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
            return;
        }

        // Check for duplicates excluding current user
        $check = $pdo->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id");
        $check->execute([':username' => $username, ':email' => $email, ':id' => $id]);
        if ($check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Username atau Email sudah digunakan oleh user lain.']);
            return;
        }

        if (!empty($password)) {
            // Update with password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, password = :password WHERE id = :id");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hash,
                ':id' => $id
            ]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email WHERE id = :id");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':id' => $id
            ]);
        }
        echo json_encode(['status' => 'success', 'message' => 'Data user berhasil diperbarui.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui user: ' . $e->getMessage()]);
    }
}

function handleDeleteUser($pdo) {
    $id = 0;
    if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $raw = json_decode(file_get_contents('php://input'), true);
        $id = isset($raw['id']) ? (int)$raw['id'] : 0;
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    }

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID user tidak valid.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status' => 'success', 'message' => 'User berhasil dihapus.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus user: ' . $e->getMessage()]);
    }
}

// ==========================================
// 3. RENTALS CONTROLLER FUNCTIONS
// ==========================================

function handleReadRentals($pdo) {
    try {
        // Read with JOINS to return readable username and gpu name
        $sql = "SELECT r.*, u.username, g.nama_gpu, g.harga as gpu_harga
                FROM rentals r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN gpu_services g ON r.gpu_id = g.id
                ORDER BY r.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data rental: ' . $e->getMessage()]);
    }
}

function handleCreateRental($pdo) {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $gpu_id = isset($_POST['gpu_id']) ? (int)$_POST['gpu_id'] : 0;
    $durasi_jam = isset($_POST['durasi_jam']) ? (int)$_POST['durasi_jam'] : 0;
    $status_pembayaran = isset($_POST['status_pembayaran']) ? trim($_POST['status_pembayaran']) : 'pending';
    
    // Total harga can be passed or calculated
    $total_harga = isset($_POST['total_harga']) ? (float)$_POST['total_harga'] : 0.0;

    if ($user_id <= 0 || $gpu_id <= 0 || $durasi_jam <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data sewa tidak lengkap. Pilihlah User, GPU, dan Durasi Jam.']);
        return;
    }

    try {
        // If total_harga was not provided, calculate it based on GPU price
        if ($total_harga <= 0) {
            $stmt = $pdo->prepare("SELECT harga FROM gpu_services WHERE id = :id");
            $stmt->execute([':id' => $gpu_id]);
            $gpu = $stmt->fetch();
            if (!$gpu) {
                echo json_encode(['status' => 'error', 'message' => 'Layanan GPU tidak ditemukan.']);
                return;
            }
            // Extract numeric value from string (e.g. "Rp 15.000 / Jam" -> 15000)
            $cleanPrice = preg_replace('/[^0-9]/', '', $gpu['harga']);
            $pricePerHour = $cleanPrice ? (float)$cleanPrice : 0.0;
            $total_harga = $pricePerHour * $durasi_jam;
        }

        $stmt = $pdo->prepare("INSERT INTO rentals (user_id, gpu_id, durasi_jam, total_harga, status_pembayaran) 
                               VALUES (:user_id, :gpu_id, :durasi_jam, :total_harga, :status_pembayaran)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':gpu_id' => $gpu_id,
            ':durasi_jam' => $durasi_jam,
            ':total_harga' => $total_harga,
            ':status_pembayaran' => $status_pembayaran
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Sewa GPU berhasil dicatat.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan transaksi sewa: ' . $e->getMessage()]);
    }
}

function handleUpdateRental($pdo) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $gpu_id = isset($_POST['gpu_id']) ? (int)$_POST['gpu_id'] : 0;
    $durasi_jam = isset($_POST['durasi_jam']) ? (int)$_POST['durasi_jam'] : 0;
    $status_pembayaran = isset($_POST['status_pembayaran']) ? trim($_POST['status_pembayaran']) : 'pending';
    $total_harga = isset($_POST['total_harga']) ? (float)$_POST['total_harga'] : 0.0;

    if ($id <= 0 || $user_id <= 0 || $gpu_id <= 0 || $durasi_jam <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap. ID, User, GPU, dan Durasi wajib diisi.']);
        return;
    }

    try {
        // Check if rental exists
        $stmt = $pdo->prepare("SELECT id FROM rentals WHERE id = :id");
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Data sewa tidak ditemukan.']);
            return;
        }

        // Calculate total price if not provided or changed
        if ($total_harga <= 0) {
            $stmt = $pdo->prepare("SELECT harga FROM gpu_services WHERE id = :id");
            $stmt->execute([':id' => $gpu_id]);
            $gpu = $stmt->fetch();
            if ($gpu) {
                $cleanPrice = preg_replace('/[^0-9]/', '', $gpu['harga']);
                $pricePerHour = $cleanPrice ? (float)$cleanPrice : 0.0;
                $total_harga = $pricePerHour * $durasi_jam;
            }
        }

        $stmt = $pdo->prepare("UPDATE rentals 
                               SET user_id = :user_id, gpu_id = :gpu_id, durasi_jam = :durasi_jam, 
                                   total_harga = :total_harga, status_pembayaran = :status_pembayaran 
                               WHERE id = :id");
        $stmt->execute([
            ':user_id' => $user_id,
            ':gpu_id' => $gpu_id,
            ':durasi_jam' => $durasi_jam,
            ':total_harga' => $total_harga,
            ':status_pembayaran' => $status_pembayaran,
            ':id' => $id
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Transaksi sewa berhasil diperbarui.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui transaksi sewa: ' . $e->getMessage()]);
    }
}

function handleDeleteRental($pdo) {
    $id = 0;
    if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $raw = json_decode(file_get_contents('php://input'), true);
        $id = isset($raw['id']) ? (int)$raw['id'] : 0;
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    }

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID rental tidak valid.']);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM rentals WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status' => 'success', 'message' => 'Transaksi sewa berhasil dihapus.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus sewa: ' . $e->getMessage()]);
    }
}

// ==========================================
// 0. AUTHENTICATION CONTROLLER FUNCTIONS
// ==========================================

function handleCheckAuth() {
    if (isset($_SESSION['user'])) {
        echo json_encode([
            'status' => 'success',
            'authenticated' => true,
            'user' => $_SESSION['user']
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'authenticated' => false
        ]);
    }
}

function handleLogin($pdo) {
    $usernameOrEmail = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($usernameOrEmail) || empty($password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username/Email dan Password wajib diisi.'
        ]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :input OR email = :input");
        $stmt->execute([':input' => $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email']
            ];
            echo json_encode([
                'status' => 'success',
                'message' => 'Login berhasil!',
                'user' => $_SESSION['user']
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username/Email atau Password salah.'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function handleRegister($pdo) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Semua kolom pendaftaran wajib diisi.'
        ]);
        return;
    }

    try {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $check->execute([':username' => $username, ':email' => $email]);
        if ($check->rowCount() > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username atau Email sudah terdaftar.'
            ]);
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':password' => $hash
        ]);

        $newId = $pdo->lastInsertId();

        $_SESSION['user'] = [
            'id' => $newId,
            'username' => $username,
            'email' => $email
        ];

        echo json_encode([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil! Anda otomatis masuk.',
            'user' => $_SESSION['user']
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal mendaftar: ' . $e->getMessage()
        ]);
    }
}

function handleLogout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo json_encode([
        'status' => 'success',
        'message' => 'Logout berhasil.'
    ]);
}
?>
