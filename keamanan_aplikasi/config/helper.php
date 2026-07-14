<?php
// Security Helpers and General Utilities

// 1. Safe Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    // Enforce cookie-only sessions and prevent uninitialized session ID acceptance (Session Adoption)
    ini_set("session.use_only_cookies", 1);
    ini_set("session.use_strict_mode", 1);

    // Configure session cookie params securely (Lifetime, Path, Domain, Secure, HttpOnly, SameSite)
    // SameSite=Lax mitigates CSRF attacks; HttpOnly blocks XSS access to the session ID
    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/",
        "domain" => "",
        "secure" => isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on",
        "httponly" => true,
        "samesite" => "Lax",
    ]);

    session_start();
}

/**
 * 2. Cross-Site Scripting (XSS) Mitigation Helper
 * Sanitizes output data before displaying it in HTML.
 *
 * @param string|null $string
 * @return string
 */
function escape($string)
{
    if ($string === null) {
        return "";
    }
    return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
}

/**
 * 3. Authentication Check Guard
 * Ensures only logged-in users can access specific pages.
 */
function auth_check()
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }
}

/**
 * 4. Guest Check Guard
 * Redirects logged-in users away from authentication pages (login, register).
 */
function guest_check()
{
    if (isset($_SESSION["user_id"])) {
        header("Location: dashboard.php");
        exit();
    }
}

/**
 * 5. Format Currency to Rupiah
 *
 * @param float|int $number
 * @return string
 */
function format_rupiah($number)
{
    return "Rp " . number_format($number, 0, ",", ".");
}

/**
 * 6. Set Alert Flash Message
 * Stores a message in session to display after page redirect.
 *
 * @param string $type ('success' or 'danger')
 * @param string $message
 */
function set_flash_message($type, $message)
{
    $_SESSION["flash"] = [
        "type" => $type,
        "message" => $message,
    ];
}

/**
 * 7. Render Alert Flash Message
 * Displays the flash message stored in session using Bootstrap 5 alert template.
 */
function display_flash_message()
{
    if (isset($_SESSION["flash"])) {
        $flash = $_SESSION["flash"];
        unset($_SESSION["flash"]); // Clean up flash message after displaying once

        $type = $flash["type"] === "success" ? "success" : "danger";
        $icon =
            $flash["type"] === "success"
                ? "bi-check-circle-fill"
                : "bi-exclamation-triangle-fill";

        echo '<div class="alert alert-' .
            $type .
            ' alert-dismissible fade show d-flex align-items-center" role="alert">';
        echo '  <i class="bi ' . $icon . ' me-2"></i>';
        echo "  <div>" . escape($flash["message"]) . "</div>";
        echo '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo "</div>";
    }
}
/**
 * Validasi jenis transaksi
 */
function in_amount_type($val)
{
    return in_array($val, ["Pemasukan", "Pengeluaran"], true);
}
function send_smtp_mail($to, $subject, $message_body, $max_retries = 2)
{
    // Pengaturan Akun SMTP
    $smtp_host = isset($_ENV["SMTP_HOST"]) ? $_ENV["SMTP_HOST"] : "";
    $smtp_port = isset($_ENV["SMTP_PORT"]) ? intval($_ENV["SMTP_PORT"]) : 587;
    $smtp_user = isset($_ENV["SMTP_USERNAME"])
        ? $_ENV["SMTP_USERNAME"]
        : (isset($_ENV["SMTP_UNAME"])
            ? $_ENV["SMTP_UNAME"]
            : (isset($_ENV["SMTP_USER"])
                ? $_ENV["SMTP_USER"]
                : ""));
    $smtp_pass = isset($_ENV["SMTP_PASSWORD"])
        ? $_ENV["SMTP_PASSWORD"]
        : (isset($_ENV["SMTP_PASS"])
            ? $_ENV["SMTP_PASS"]
            : "");
    $from_email = isset($_ENV["SMTP_FROM_EMAIL"])
        ? $_ENV["SMTP_FROM_EMAIL"]
        : $smtp_user;
    $from_name = isset($_ENV["SMTP_FROM_NAME"])
        ? $_ENV["SMTP_FROM_NAME"]
        : "DompetKu";

    if (!function_exists('get_smtp_response')) {
        function get_smtp_response($socket)
        {
            $response = "";
            while (substr($response, 3, 1) != " ") {
                if (!($line = fgets($socket, 256))) {
                    break;
                }
                $response .= $line;

                // Cek jika terjadi timeout pada stream
                $info = stream_get_meta_data($socket);
                if ($info["timed_out"]) {
                    break;
                }
            }
            return $response;
        }
    }

    // Helper: Ambil kode status SMTP dari response (3 digit pertama)
    if (!function_exists('get_smtp_code')) {
        function get_smtp_code($response)
        {
            return intval(substr(trim($response), 0, 3));
        }
    }

    // Retry mechanism untuk mengatasi rate-limiting Gmail saat kirim email berturut-turut
    for ($attempt = 0; $attempt <= $max_retries; $attempt++) {
        // Jeda sebelum retry (tidak pada percobaan pertama)
        if ($attempt > 0) {
            error_log("SMTP Retry #{$attempt} untuk {$to} - menunggu 2 detik...");
            sleep(2);
        }

        // 1. Membuka Koneksi Socket ke Server SMTP
        $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
        if (!$socket) {
            error_log("SMTP Error: Gagal membuka koneksi ke {$smtp_host}:{$smtp_port} (attempt {$attempt}) - {$errstr}");
            continue; // Coba lagi
        }

        // Set timeout baca/tulis socket 10 detik agar tidak hang
        stream_set_timeout($socket, 10);

        $greeting = get_smtp_response($socket);
        if (get_smtp_code($greeting) !== 220) {
            error_log("SMTP Error: Greeting gagal - " . trim($greeting));
            fclose($socket);
            continue;
        }

        // 2. Jabat tangan (EHLO) dan Mulai Enkripsi TLS
        $ehlo_host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "localhost";

        fwrite($socket, "EHLO {$ehlo_host}\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            error_log("SMTP Error: EHLO gagal - " . trim($resp));
            fclose($socket);
            continue;
        }

        fwrite($socket, "STARTTLS\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 220) {
            error_log("SMTP Error: STARTTLS gagal - " . trim($resp));
            fclose($socket);
            continue;
        }

        $crypto_res = @stream_socket_enable_crypto(
            $socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT,
        );
        if (!$crypto_res) {
            error_log("SMTP Error: TLS encryption gagal untuk {$to}");
            fclose($socket);
            continue;
        }

        // 3. Jabat tangan ulang setelah enkripsi aktif + Login Otentikasi
        fwrite($socket, "EHLO {$ehlo_host}\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            error_log("SMTP Error: EHLO setelah TLS gagal - " . trim($resp));
            fclose($socket);
            continue;
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 334) {
            error_log("SMTP Error: AUTH LOGIN gagal - " . trim($resp));
            fclose($socket);
            continue;
        }

        fwrite($socket, base64_encode($smtp_user) . "\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 334) {
            error_log("SMTP Error: Username ditolak - " . trim($resp));
            fclose($socket);
            continue;
        }

        fwrite($socket, base64_encode($smtp_pass) . "\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 235) {
            error_log("SMTP Error: Autentikasi gagal - " . trim($resp));
            fclose($socket);
            continue;
        }

        // 4. Set Pengirim dan Penerima
        fwrite($socket, "MAIL FROM: <$from_email>\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            error_log("SMTP Error: MAIL FROM ditolak - " . trim($resp));
            fclose($socket);
            continue;
        }

        fwrite($socket, "RCPT TO: <$to>\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            error_log("SMTP Error: RCPT TO ditolak untuk {$to} - " . trim($resp));
            fclose($socket);
            continue;
        }

        // 5. Mengirimkan Konten Email (Header & Body)
        fwrite($socket, "DATA\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 354) {
            error_log("SMTP Error: DATA ditolak - " . trim($resp));
            fclose($socket);
            continue;
        }

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .=
            "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";

        fwrite($socket, $headers . "\r\n" . $message_body . "\r\n.\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            error_log("SMTP Error: Email gagal dikirim ke {$to} - " . trim($resp));
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            continue;
        }

        // 6. Tutup Koneksi — Berhasil!
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        error_log("SMTP OK: Email berhasil dikirim ke {$to} (attempt {$attempt})");
        return true;
    }

    // Semua percobaan gagal
    error_log("SMTP FATAL: Gagal mengirim email ke {$to} setelah " . ($max_retries + 1) . " percobaan.");
    return false;
}
function load_env()
{
    $paths = [__DIR__ . "/../../.env", __DIR__ . "/../.env"];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), "#") === 0) {
                    continue;
                } // Lewati komentar
                if (strpos($line, "=") === false) {
                    continue;
                }
                [$name, $value] = explode("=", $line, 2);
                $name = trim($name);
                $value = trim(trim($value), '"\''); // Hapus kutip jika ada
                if (
                    !array_key_exists($name, $_SERVER) &&
                    !array_key_exists($name, $_ENV)
                ) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
            break;
        }
    }

    // Set fallback default values if .env doesn't exist to prevent "Undefined array key" warnings/errors in PHP 8+
    $defaults = [
        "GOOGLE_CLIENT_ID" => "",
        "GOOGLE_CLIENT_SECRET" => "",
        "GOOGLE_REDIRECT_URL" => "",
        "SMTP_HOST" => "",
        "SMTP_PORT" => "587",
        "SMTP_UNAME" => "",
        "SMTP_PASS" => "",
        "SMTP_USERNAME" => "",
        "SMTP_PASSWORD" => "",
        "SMTP_FROM_EMAIL" => "",
        "SMTP_FROM_NAME" => "DompetKu"
    ];
    foreach ($defaults as $key => $val) {
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $val;
        }
        if (!isset($_SERVER[$key])) {
            $_SERVER[$key] = $val;
        }
    }
}

// Langsung jalankan fungsinya agar env siap dipakai di file lain
load_env();

/**
 * Menghitung pengeluaran berjalan dan membandingkannya dengan limit anggaran bulanan (PDO Version)
 */
function dapatkan_analisis_anggaran($id_user, $bulan, $tahun)
{
    global $pdo;
    $hasil_analisis = [];

    try {
        $query = "SELECT 
                    a.id_kategori,
                    k.nama_kategori,
                    a.jumlah_budget,
                    COALESCE(SUM(t.nominal), 0) AS total_pengeluaran
                  FROM anggaran a
                  JOIN kategori k ON a.id_kategori = k.id_kategori
                  LEFT JOIN transaksi t ON t.id_kategori = a.id_kategori 
                    AND t.user_id = a.id_user 
                    AND t.jenis = 'Pengeluaran'
                    AND t.is_transfer = 0
                    AND MONTH(t.tanggal) = :bulan 
                    AND YEAR(t.tanggal) = :tahun
                  WHERE a.id_user = :id_user AND a.bulan = :bulan_anggaran AND a.tahun = :tahun_anggaran
                  GROUP BY a.id_kategori, k.nama_kategori, a.jumlah_budget";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            "bulan" => $bulan,
            "tahun" => $tahun,
            "id_user" => $id_user,
            "bulan_anggaran" => $bulan,
            "tahun_anggaran" => $tahun,
        ]);

        while ($row = $stmt->fetch()) {
            $budget = floatval($row["jumlah_budget"]);
            $pengeluaran = floatval($row["total_pengeluaran"]);
            $persentase = $budget > 0 ? ($pengeluaran / $budget) * 100 : 0;

            $hasil_analisis[] = [
                "id_kategori" => $row["id_kategori"],
                "nama_kategori" => $row["nama_kategori"],
                "budget" => $budget,
                "pengeluaran" => $pengeluaran,
                "persentase" => $persentase,
            ];
        }
    } catch (\PDOException $e) {
        error_log($e->getMessage());
    }

    return $hasil_analisis;
}

/**
 * Mengirimkan email notifikasi ke admin (pramonobagas01@gmail.com) saat ada user baru mendaftar
 */
function notify_admin_register($username, $email)
{
    $admin_email = "pramonobagas01@gmail.com";
    $subject = "Notifikasi Admin: Registrasi Pengguna Baru";
    $ip_address = isset($_SERVER["REMOTE_ADDR"])
        ? $_SERVER["REMOTE_ADDR"]
        : "N/A";
    $timestamp = date("Y-m-d H:i:s");
    $tahun = date("Y");

    $email_template =
        "
        <div style='background-color: #f8fafc; padding: 30px 15px; font-family: Arial, sans-serif;'>
            <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);'>
                <div style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 25px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;'>DompetKu Admin</h1>
                </div>
                <div style='padding: 30px 25px; color: #1e293b; line-height: 1.6;'>
                    <h2 style='margin-top: 0; color: #0f172a; font-size: 18px; font-weight: 700; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;'>Pendaftaran Pengguna Baru</h2>
                    <p style='color: #475569; font-size: 15px;'>Halo Admin,</p>
                    <p style='color: #475569; font-size: 15px;'>Seorang pengguna baru telah berhasil mendaftar ke sistem DompetKu:</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;'>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; width: 35%; border-bottom: 1px solid #f1f5f9;'>Username</td>
                            <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" .
        escape($username) .
        "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Alamat Email</td>
                            <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" .
        escape($email) .
        "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Waktu Daftar</td>
                            <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" .
        $timestamp .
        "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>IP Address</td>
                            <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" .
        $ip_address .
        "</td>
                        </tr>
                    </table>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                    <p style='color: #64748b; font-size: 12px; margin-bottom: 0; text-align: center;'>&copy; " .
        $tahun .
        " DompetKu Admin System.</p>
                </div>
            </div>
        </div>
    ";

    return send_smtp_mail($admin_email, $subject, $email_template);
}

/**
 * Mengirimkan email notifikasi ke admin (pramonobagas01@gmail.com) saat ada user melakukan login
 */
function notify_admin_login($username, $email, $method = "Kredensial Standard")
{
    $admin_email = "pramonobagas01@gmail.com";
    $subject = "Notifikasi Admin: Aktivitas Login Pengguna";
    $ip_address = isset($_SERVER["REMOTE_ADDR"])
        ? $_SERVER["REMOTE_ADDR"]
        : "N/A";
    $timestamp = date("Y-m-d H:i:s");
    $tahun = date("Y");

    $email_template =
        "
        <div style='background-color: #f8fafc; padding: 30px 15px; font-family: Arial, sans-serif;'>
            <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);'>
                <div style='background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 25px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;'>DompetKu Admin</h1>
                </div>
                <div style='padding: 30px 25px; color: #1e293b; line-height: 1.6;'>
                    <h2 style='margin-top: 0; color: #0f172a; font-size: 18px; font-weight: 700; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;'>Aktivitas Login Pengguna</h2>
                    <p style='color: #475569; font-size: 15px;'>Halo Admin,</p>
                    <p style='color: #475569; font-size: 15px;'>Terdeteksi aktivitas login pengguna pada sistem DompetKu:</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;'>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; width: 35%; border-bottom: 1px solid #f1f5f9;'>Username</td>
                            <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" .
        escape($username) .
        "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Alamat Email</td>
                            <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" .
        escape($email) .
        "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #0284c7; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" .
        $method .
        "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Waktu Login</td>
                            <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" .
        $timestamp .
        "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>IP Address</td>
                            <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" .
        $ip_address .
        "</td>
                        </tr>
                    </table>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                    <p style='color: #64748b; font-size: 12px; margin-bottom: 0; text-align: center;'>&copy; " .
        $tahun .
        " DompetKu Admin System.</p>
                </div>
            </div>
        </div>
    ";

    return send_smtp_mail($admin_email, $subject, $email_template);
}

/**
 * Mengirimkan email notifikasi selamat datang / pemberitahuan login ke email pengguna yang bersangkutan
 */
function send_login_welcome_email($username, $email, $method = "Kredensial Standard")
{
    $subject = "Selamat Datang Kembali di DompetKu!";
    $ip_address = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "N/A";
    $timestamp = date("d M Y, H:i:s");
    $tahun = date("Y");

    $email_template =
        "
        <div style='background-color: #f8fafc; padding: 30px 15px; font-family: Arial, sans-serif;'>
            <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);'>
                <div style='background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); padding: 25px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;'>DompetKu</h1>
                </div>
                <div style='padding: 30px 25px; color: #1e293b; line-height: 1.6;'>
                    <h2 style='margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 700;'>Halo, " . escape($username) . "!</h2>
                    <p style='color: #475569; font-size: 15px;'>Selamat datang kembali! Kami mendeteksi aktivitas login baru pada akun DompetKu Anda menggunakan alamat email ini.</p>
                    
                    <p style='color: #475569; font-size: 15px; margin-bottom: 5px;'><strong>Rincian Aktivitas Login:</strong></p>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;'>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; width: 35%; border-bottom: 1px solid #f1f5f9;'>Waktu Login</td>
                            <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" . $timestamp . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Metode</td>
                            <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" . $method . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>IP Address</td>
                            <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" . $ip_address . "</td>
                        </tr>
                    </table>

                    <p style='color: #475569; font-size: 14px;'>Jika ini adalah aktivitas Anda, Anda dapat mengabaikan email ini. Jika Anda tidak merasa melakukan login ini, segera hubungi admin atau ganti password akun Anda demi keamanan.</p>
                    
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                    <p style='color: #64748b; font-size: 12px; margin-bottom: 0; text-align: center;'>&copy; " . $tahun . " DompetKu. Keamanan finansial Anda adalah prioritas kami.</p>
                </div>
            </div>
        </div>
        ";

    return send_smtp_mail($email, $subject, $email_template);
}
