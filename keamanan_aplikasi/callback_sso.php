<?php
require_once "config/koneksi.php";
require_once "config/helper.php";

$google_client_id = $_ENV["GOOGLE_CLIENT_ID"];
$google_client_secret = $_ENV["GOOGLE_CLIENT_SECRET"];
$google_redirect_url = $_ENV["GOOGLE_REDIRECT_URL"];

if (!isset($_GET["code"])) {
    header("Location: login.php");
    exit();
}

try {
    // 1. Tukarkan Otentikasi Code dengan Access Token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        http_build_query([
            "code" => $_GET["code"],
            "client_id" => $google_client_id,
            "client_secret" => $google_client_secret,
            "redirect_uri" => $google_redirect_url,
            "grant_type" => "authorization_code",
        ]),
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $exec_result = curl_exec($ch);
    if ($exec_result === false) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: " . $error_msg);
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($exec_result, true);
    if ($http_code !== 200) {
        $error_desc = isset($response["error_description"])
            ? $response["error_description"]
            : (isset($response["error"])
                ? $response["error"]
                : "Unknown error");
        throw new Exception(
            "Google Token API Error (HTTP $http_code): " . $error_desc,
        );
    }

    if (isset($response["access_token"])) {
        // 2. Ambil Data Profil User dari Google API
        $ch = curl_init();
        curl_setopt(
            $ch,
            CURLOPT_URL,
            "https://www.googleapis.com/oauth2/v3/userinfo?access_token=" .
                $response["access_token"],
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $user_info_result = curl_exec($ch);
        if ($user_info_result === false) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL User Info Error: " . $error_msg);
        }

        $http_code_user = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $user_info = json_decode($user_info_result, true);
        if ($http_code_user !== 200) {
            $error_desc = isset($user_info["error_description"])
                ? $user_info["error_description"]
                : (isset($user_info["error"])
                    ? $user_info["error"]
                    : "Unknown error");
            throw new Exception(
                "Google UserInfo API Error (HTTP $http_code_user): " .
                    $error_desc,
            );
        }

        $google_id = $user_info["sub"];
        $email = $user_info["email"];
        // Normalisasi username dari nama depan Google (tanpa spasi, lowercase)
        $username =
            strtolower(
                preg_replace("/[^a-zA-Z0-9_]/", "", $user_info["given_name"]),
            ) .
            "_" .
            rand(10, 99);

        // 3. Cek apakah user sudah pernah login pakai Google SSO sebelumnya
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE google_id = :google_id OR email = :email",
        );
        $stmt->execute(["google_id" => $google_id, "email" => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Jika akun ada tapi google_id belum terikat (pendaftaran manual via email sebelumnya)
            if (empty($user["google_id"])) {
                $update = $pdo->prepare(
                    "UPDATE users SET google_id = :google_id WHERE id = :id",
                );
                $update->execute([
                    "google_id" => $google_id,
                    "id" => $user["id"],
                ]);
            }
            // Kirim notifikasi login admin
            notify_admin_login($user["username"], $user["email"], "Google SSO");
        } else {
            // Jika belum terdaftar sama sekali, buatkan akun otomatis (Password di-random & di-hash aman)
            $random_password = password_hash(
                bin2hex(random_bytes(8)),
                PASSWORD_DEFAULT,
            );
            $insert = $pdo->prepare(
                "INSERT INTO users (username, email, password, google_id) VALUES (:username, :email, :password, :google_id)",
            );
            $insert->execute([
                "username" => $username,
                "email" => $email,
                "password" => $random_password,
                "google_id" => $google_id,
            ]);

            // Ambil data user yang baru dimasukkan
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(["id" => $pdo->lastInsertId()]);
            $user = $stmt->fetch();

            // Kirim notifikasi registrasi admin
            notify_admin_register($user["username"], $user["email"]);
            // Kirim notifikasi login admin (karena user langsung masuk setelah register)
            notify_admin_login(
                $user["username"],
                $user["email"],
                "Google SSO (Pendaftaran)",
            );
        }

        // 4. Set Session (Sama persis dengan mekanisme login.php milikmu)
        session_regenerate_id(true);
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["email"] = $user["email"];

        header("Location: dashboard.php");
        exit();
    } else {
        set_flash_message(
            "danger",
            "Gagal mendapatkan token otentikasi dari Google.",
        );
        header("Location: login.php");
        exit();
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    set_flash_message(
        "danger",
        "Terjadi kesalahan sistem saat SSO: " . $e->getMessage(),
    );
    header("Location: login.php");
    exit();
}
//
