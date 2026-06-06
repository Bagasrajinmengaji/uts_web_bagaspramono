<?php
/**
 * Standalone Login Page
 * Styled with Bootstrap 5 & Cyberpunk Accents
 */
session_start();

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'connection.php';
    
    $usernameOrEmail = isset($_POST['username_or_email']) ? trim($_POST['username_or_email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($usernameOrEmail) || empty($password)) {
        $error = 'Username/Email dan Password wajib diisi.';
    } else {
        try {
            // Check in Database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :input OR email = :input");
            $stmt->execute([':input' => $usernameOrEmail]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set Session
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ];
                header("Location: index.php");
                exit;
            } else {
                $error = 'Username/Email atau Password salah.';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan Database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - NusaGrid GPU Portal</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #07040e;
            background-image: radial-gradient(circle at 30% 20%, rgba(157, 78, 221, 0.15) 0%, transparent 40%),
                              radial-gradient(circle at 70% 80%, rgba(0, 240, 255, 0.12) 0%, transparent 40%),
                              linear-gradient(135deg, #090514 0%, #110923 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f3efff;
        }

        .auth-card {
            background: rgba(26, 15, 52, 0.45);
            border: 1px solid rgba(157, 78, 221, 0.25);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 25px rgba(157, 78, 221, 0.15);
            backdrop-filter: blur(15px);
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #9d4edd, #00f0ff);
        }

        .logo-icon {
            background: linear-gradient(135deg, #9d4edd, #00f0ff);
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
            box-shadow: 0 0 15px rgba(157, 78, 221, 0.4);
            margin: 0 auto 1rem;
        }

        .logo-title {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            background: linear-gradient(to right, #ffffff, #00f0ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-control {
            background-color: rgba(7, 4, 14, 0.5);
            border: 1px solid rgba(157, 78, 221, 0.25);
            color: #fff;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .form-control:focus {
            background-color: rgba(7, 4, 14, 0.7);
            border-color: #00f0ff;
            color: #fff;
            box-shadow: 0 0 8px rgba(0, 240, 255, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #9d4edd, #3a86c8);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #9d4edd, #00f0ff);
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(157, 78, 221, 0.3);
        }

        .text-neon {
            color: #00f0ff;
            text-decoration: none;
            font-weight: 600;
        }

        .text-neon:hover {
            text-decoration: underline;
        }

        .alert-danger {
            background: rgba(255, 0, 127, 0.1);
            border: 1px solid rgba(255, 0, 127, 0.25);
            color: #ff007f;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-sm-8">
            
            <div class="card auth-card p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="logo-icon">
                        <i class="fa-solid fa-cloud-bolt"></i>
                    </div>
                    <h2 class="logo-title mb-1">NusaGrid Portal</h2>
                    <p class="text-muted small uppercase tracking-wider">Cloud GPU Console</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="username_or_email" class="form-label text-muted small uppercase fw-bold">Username atau Email</label>
                        <input type="text" class="form-control" id="username_or_email" name="username_or_email" placeholder="Masukkan username/email..." required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label text-muted small uppercase fw-bold">Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password..." required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk
                    </button>
                </form>

                <div class="text-center text-muted small mt-2">
                    Belum punya akun? <a href="register.php" class="text-neon">Daftar Baru</a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
