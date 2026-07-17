<?php
// File helper.php: Sistem utilitas dan keamanan aplikasi

// Bagian 1: Inisialisasi Sesi Aman
if (session_status() === PHP_SESSION_NONE) {
    // Memaksa penggunaan cookie saja untuk sesi dan mencegah Session Adoption
    ini_set("session.use_only_cookies", 1);
    ini_set("session.use_strict_mode", 1);

    // Mengonfigurasi parameter cookie sesi secara aman (lifetime, path, secure, httpOnly, samesite)
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

// Bagian 2: Sanitasi Output & Pencegahan XSS
// Melakukan sanitasi data sebelum ditampilkan ke halaman HTML guna mencegah XSS
function escape($string)
{
    if ($string === null) {
        return "";
    }
    return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
}

// Bagian 3: Proteksi & Guard Akses Halaman
// Memastikan pengguna sudah login. Jika belum, akan diarahkan ke halaman login.
function auth_check()
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }
}

// Memastikan pengguna berstatus tamu (belum login). Jika sudah login, dialihkan ke dashboard.
function guest_check()
{
    if (isset($_SESSION["user_id"])) {
        header("Location: dashboard.php");
        exit();
    }
}

// Bagian 4: Utilitas Data & Validasi
// Memformat angka nominal uang menjadi format Rupiah standar (contoh: Rp 50.000)
function format_rupiah($number)
{
    return "Rp " . number_format($number, 0, ",", ".");
}

// Mengatur pesan notifikasi (flash message) dalam sesi
function set_flash_message($type, $message)
{
    $_SESSION["flash"] = [
        "type" => $type,
        "message" => $message,
    ];
}

// Menampilkan pesan notifikasi flash menggunakan template alert Bootstrap 5
function display_flash_message()
{
    if (isset($_SESSION["flash"])) {
        $flash = $_SESSION["flash"];
        unset($_SESSION["flash"]); // Hapus pesan setelah ditampilkan sekali

        $type = $flash["type"] === "success" ? "success" : "danger";
        $icon = $flash["type"] === "success" ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill";

        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show d-flex align-items-center" role="alert">';
        echo '  <i class="bi ' . $icon . ' me-2"></i>';
        echo '  <div>' . escape($flash["message"]) . '</div>';
        echo '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

// Memvalidasi apakah jenis nominal transaksi sesuai kategori yang valid
function in_amount_type($val)
{
    return in_array($val, ["Pemasukan", "Pengeluaran"], true);
}

// Bagian 5: Helper SMTP Protokol (Standalone)
// Membaca respons balik dari socket koneksi SMTP secara baris-per-baris
function get_smtp_response($socket)
{
    $response = "";
    while (substr($response, 3, 1) != " ") {
        if (!($line = fgets($socket, 256))) {
            break;
        }
        $response .= $line;

        // Cek jika terjadi timeout pada koneksi stream
        $info = stream_get_meta_data($socket);
        if ($info["timed_out"]) {
            break;
        }
    }
    return $response;
}

// Mengambil kode status integer dari respons SMTP (3 digit pertama)
function get_smtp_code($response)
{
    return intval(substr(trim($response), 0, 3));
}

// Bagian 6: Pengiriman Email SMTP Utama
// Mengirimkan email menggunakan protokol raw SMTP dengan fsockopen secara manual
function send_smtp_mail($to, $subject, $message_body, $max_retries = 1, &$error_out = null)
{
    // Konfigurasi Akun SMTP dari Variable Environment
    $smtp_host   = isset($_ENV["SMTP_HOST"]) ? $_ENV["SMTP_HOST"] : "";
    $smtp_port   = isset($_ENV["SMTP_PORT"]) ? intval($_ENV["SMTP_PORT"]) : 587;
    $smtp_user   = (!empty($_ENV["SMTP_USERNAME"]))
        ? $_ENV["SMTP_USERNAME"]
        : (!empty($_ENV["SMTP_UNAME"])
            ? $_ENV["SMTP_UNAME"]
            : (!empty($_ENV["SMTP_USER"])
                ? $_ENV["SMTP_USER"]
                : ""));
    $smtp_pass   = (!empty($_ENV["SMTP_PASSWORD"]))
        ? $_ENV["SMTP_PASSWORD"]
        : (!empty($_ENV["SMTP_PASS"])
            ? $_ENV["SMTP_PASS"]
            : "");
    $from_email  = (!empty($_ENV["SMTP_FROM_EMAIL"])) ? $_ENV["SMTP_FROM_EMAIL"] : $smtp_user;
    $from_name   = (!empty($_ENV["SMTP_FROM_NAME"]))  ? $_ENV["SMTP_FROM_NAME"] : "DompetKu";

    $last_error = "Unknown error";

    // Mekanisme percobaan ulang (retry) untuk menangani kendala koneksi temporer
    for ($attempt = 0; $attempt <= $max_retries; $attempt++) {
        if ($attempt > 0) {
            error_log("SMTP Retry #{$attempt} untuk {$to} - menunggu 1 detik...");
            sleep(1);
        }

        // Membuka Koneksi Socket ke Server SMTP
        $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
        if (!$socket) {
            $last_error = "Gagal koneksi ke {$smtp_host}:{$smtp_port} - {$errstr}";
            error_log("SMTP Error: " . $last_error);
            continue;
        }

        // Atur timeout socket 10 detik agar eksekusi tidak macet (hang)
        stream_set_timeout($socket, 10);

        $greeting = get_smtp_response($socket);
        if (get_smtp_code($greeting) !== 220) {
            $last_error = "Greeting gagal: " . trim($greeting);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        // Jabat Tangan Pertama (EHLO) dan Memulai TLS
        $ehlo_host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "localhost";

        fwrite($socket, "EHLO {$ehlo_host}\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            $last_error = "EHLO gagal: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        fwrite($socket, "STARTTLS\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 220) {
            $last_error = "STARTTLS gagal: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        // Aktifkan enkripsi TLS pada stream socket yang telah terhubung
        $crypto_res = @stream_socket_enable_crypto(
            $socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );
        if (!$crypto_res) {
            $last_error = "TLS encryption gagal";
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        // Jabat Tangan Ulang setelah TLS aktif & Proses Otentikasi Login
        fwrite($socket, "EHLO {$ehlo_host}\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            $last_error = "EHLO setelah TLS gagal: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 334) {
            $last_error = "AUTH LOGIN gagal: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        fwrite($socket, base64_encode($smtp_user) . "\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 334) {
            $last_error = "Username ditolak: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        fwrite($socket, base64_encode($smtp_pass) . "\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 235) {
            $last_error = "Autentikasi gagal: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        // Konfigurasi Alur Pengirim (FROM) dan Penerima (TO)
        fwrite($socket, "MAIL FROM: <$from_email>\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            $last_error = "MAIL FROM ditolak: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        fwrite($socket, "RCPT TO: <$to>\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            $last_error = "RCPT TO ditolak: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        // Mengirimkan Konten Surat (Header, Subject, dan HTML Body)
        fwrite($socket, "DATA\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 354) {
            $last_error = "DATA ditolak: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fclose($socket);
            continue;
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";

        // Tanda '.' menandakan akhir transmisi data email
        fwrite($socket, $headers . "\r\n" . $message_body . "\r\n.\r\n");
        $resp = get_smtp_response($socket);
        if (get_smtp_code($resp) !== 250) {
            $last_error = "Pengiriman data ditolak: " . trim($resp);
            error_log("SMTP Error: " . $last_error);
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            continue;
        }

        // Penutupan Koneksi Sesi SMTP (Sukses)
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        error_log("SMTP OK: Email berhasil dikirim ke {$to} (attempt {$attempt})");
        return true;
    }

    $error_out = $last_error;
    error_log("SMTP FATAL: Gagal mengirim email ke {$to} setelah " . ($max_retries + 1) . " percobaan. Detail: " . $error_out);
    return false;
}

// Bagian 7: Manajemen Environment Variable (.env)
// Membaca konfigurasi aplikasi dari file .env lalu memuatnya ke dalam $_ENV & $_SERVER
function load_env()
{
    $paths = [__DIR__ . "/../../.env", __DIR__ . "/../.env"];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Abaikan baris komentar
                if (strpos(trim($line), "#") === 0) {
                    continue;
                }
                // Abaikan jika tidak mengandung delimiter '='
                if (strpos($line, "=") === false) {
                    continue;
                }
                [$name, $value] = explode("=", $line, 2);
                $name  = trim($name);
                $value = trim(trim($value), '"\''); // Hapus tanda petik pembungkus jika ada
                
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
            break;
        }
    }

    // Fallback nilai standar konfigurasi agar tidak menimbulkan warning/error di PHP 8+
    $defaults = [
        "GOOGLE_CLIENT_ID"     => "",
        "GOOGLE_CLIENT_SECRET" => "",
        "GOOGLE_REDIRECT_URL"  => "",
        "SMTP_HOST"            => "",
        "SMTP_PORT"            => "587",
        "SMTP_UNAME"           => "",
        "SMTP_PASS"            => "",
        "SMTP_USERNAME"        => "",
        "SMTP_PASSWORD"        => "",
        "SMTP_FROM_EMAIL"      => "",
        "SMTP_FROM_NAME"       => "DompetKu"
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

// Jalankan fungsi load_env secara otomatis sewaktu file di-include
load_env();

// Bagian 8: Logika Analisis Anggaran & Pengeluaran
// Mengalkulasi total pengeluaran berjalan dan membandingkannya dengan limit anggaran bulanan
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
            "bulan"          => $bulan,
            "tahun"          => $tahun,
            "id_user"        => $id_user,
            "bulan_anggaran" => $bulan,
            "tahun_anggaran" => $tahun,
        ]);

        while ($row = $stmt->fetch()) {
            $budget      = floatval($row["jumlah_budget"]);
            $pengeluaran = floatval($row["total_pengeluaran"]);
            $persentase  = $budget > 0 ? ($pengeluaran / $budget) * 100 : 0;

            $hasil_analisis[] = [
                "id_kategori"       => $row["id_kategori"],
                "nama_kategori"     => $row["nama_kategori"],
                "budget"            => $budget,
                "pengeluaran"       => $pengeluaran,
                "persentase"        => $persentase,
            ];
        }
    } catch (\PDOException $e) {
        error_log($e->getMessage());
    }

    return $hasil_analisis;
}

// Bagian 9: Pengiriman Notifikasi Email (Admin & User Template)

// Mengirimkan email notifikasi ke admin ketika ada user baru yang berhasil mendaftar
function notify_admin_register($username, $email)
{
    $admin_email = "pramonobagas01@gmail.com";
    $subject     = "Notifikasi Admin: Registrasi Pengguna Baru";
    $ip_address  = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "N/A";
    $timestamp   = date("Y-m-d H:i:s");
    $tahun       = date("Y");

    $email_template = "
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
                        <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" . escape($username) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Alamat Email</td>
                        <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" . escape($email) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Waktu Daftar</td>
                        <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" . $timestamp . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>IP Address</td>
                        <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" . $ip_address . "</td>
                    </tr>
                </table>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                <p style='color: #64748b; font-size: 12px; margin-bottom: 0; text-align: center;'>&copy; " . $tahun . " DompetKu Admin System.</p>
            </div>
        </div>
    </div>
    ";

    return send_smtp_mail($admin_email, $subject, $email_template);
}

// Mengirimkan email notifikasi ke admin saat terdeteksi ada user melakukan aktivitas login
function notify_admin_login($username, $email, $method = "Kredensial Standard")
{
    $admin_email = "pramonobagas01@gmail.com";
    $subject     = "Notifikasi Admin: Aktivitas Login Pengguna";
    $ip_address  = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "N/A";
    $timestamp   = date("Y-m-d H:i:s");
    $tahun       = date("Y");

    $email_template = "
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
                        <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" . escape($username) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Alamat Email</td>
                        <td style='padding: 8px 0; color: #0f172a; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" . escape($email) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Metode</td>
                        <td style='padding: 8px 0; color: #0284c7; font-weight: 600; border-bottom: 1px solid #f1f5f9;'>" . $method . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>Waktu Login</td>
                        <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" . $timestamp . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #64748b; border-bottom: 1px solid #f1f5f9;'>IP Address</td>
                        <td style='padding: 8px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9;'>" . $ip_address . "</td>
                    </tr>
                </table>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                <p style='color: #64748b; font-size: 12px; margin-bottom: 0; text-align: center;'>&copy; " . $tahun . " DompetKu Admin System.</p>
            </div>
        </div>
    </div>
    ";

    return send_smtp_mail($admin_email, $subject, $email_template);
}

// Mengirimkan email notifikasi selamat datang kembali kepada pengguna saat berhasil login
function send_login_welcome_email($username, $email, $method = "Kredensial Standard")
{
    $subject    = "Selamat Datang Kembali di DompetKu!";
    $ip_address = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "N/A";
    $timestamp  = date("d M Y, H:i:s");
    $tahun      = date("Y");

    $email_template = "
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

// Mengirimkan email selamat bergabung kepada pengguna yang baru selesai mendaftar secara lengkap
function send_register_welcome_email($username, $email)
{
    $subject = "Selamat Bergabung di DompetKu!";
    $email_template = "
    <div style='background-color: #f1f5f9; padding: 30px 15px; font-family: Arial, sans-serif;'>
        <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);'>
            <div style='background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); padding: 25px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;'>DompetKu</h1>
            </div>
            <div style='padding: 30px 25px; color: #1e293b; line-height: 1.6;'>
                <h2 style='margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 700;'>Halo, " . escape($username) . "!</h2>
                <p style='color: #475569; font-size: 15px;'>Akun Anda telah berhasil terdaftar secara aman di sistem kami. Sekarang saatnya mengendalikan uangmu dan mulai merancang target finansial masa depan yang rapi!</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://localhost/pemrogramanweb/keamanan_aplikasi/login.php' style='background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); color: #ffffff; text-decoration: none; padding: 12px 30px; font-weight: bold; border-radius: 10px; display: inline-block; box-shadow: 0 4px 10px rgba(56, 189, 248, 0.3); font-size: 15px;'>Mulai Catat Keuangan</a>
                </div>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                <p style='color: #64748b; font-size: 12px; margin-bottom: 0; text-align: center;'>&copy; " . date("Y") . " DompetKu. Dibuat untuk Keamanan Finansial dan Kemudahan Catatan Keuangan Pribadi Anda.</p>
            </div>
        </div>
    </div>
    ";

    return send_smtp_mail($email, $subject, $email_template);
}

// Mengirimkan email pemulihan kata sandi beserta tautan (link) reset
function send_forgot_password_email($username, $email, $link)
{
    $subject   = "Reset Kata Sandi Akun DompetKu";
    $timestamp = date("d M Y, H:i:s");
    $tahun     = date("Y");

    $email_template = "
    <div style='background-color: #f8fafc; padding: 30px 15px; font-family: Arial, sans-serif;'>
        <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);'>
            <div style='background: linear-gradient(135deg, #ea580c 0%, #f97316 100%); padding: 25px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;'>DompetKu</h1>
            </div>
            <div style='padding: 30px 25px; color: #1e293b; line-height: 1.6;'>
                <h2 style='margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 700;'>Halo, " . escape($username) . "!</h2>
                <p style='color: #475569; font-size: 15px;'>Kami menerima permintaan untuk mereset kata sandi akun DompetKu Anda. Silakan klik tombol di bawah ini untuk mengatur ulang kata sandi Anda:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . escape($link) . "' style='background: linear-gradient(135deg, #ea580c 0%, #f97316 100%); color: #ffffff; text-decoration: none; padding: 12px 30px; font-weight: bold; border-radius: 10px; display: inline-block; box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3); font-size: 15px;'>Reset Kata Sandi</a>
                </div>
                <p style='color: #475569; font-size: 14px;'>Tautan ini hanya berlaku selama <strong>1 jam</strong> dari sekarang. Jika Anda tidak meminta pengaturan ulang kata sandi ini, abaikan email ini secara aman.</p>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                <p style='color: #64748b; font-size: 12px; margin-bottom: 0; text-align: center;'>&copy; " . $tahun . " DompetKu. Keamanan finansial Anda adalah prioritas kami.</p>
            </div>
        </div>
    </div>
    ";

    return send_smtp_mail($email, $subject, $email_template);
}

// Mengirimkan email kode verifikasi OTP (One-Time Password) saat registrasi baru
function send_otp_email($username, $email, $otp)
{
    $subject   = "Kode OTP Verifikasi Registrasi DompetKu";
    $timestamp = date("d M Y, H:i:s");
    $tahun     = date("Y");

    $email_template = "
    <div style='background-color: #f8fafc; padding: 30px 15px; font-family: Arial, sans-serif;'>
        <div style='max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);'>
            <div style='background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); padding: 25px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;'>DompetKu</h1>
            </div>
            <div style='padding: 30px 25px; color: #1e293b; line-height: 1.6;'>
                <h2 style='margin-top: 0; color: #0f172a; font-size: 20px; font-weight: 700;'>Halo, " . escape($username) . "!</h2>
                <p style='color: #475569; font-size: 15px;'>Terima kasih telah melakukan registrasi akun di DompetKu. Silakan gunakan kode OTP berikut untuk menyelesaikan verifikasi email Anda:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <div style='background-color: #f1f5f9; border: 2px dashed #0284c7; padding: 15px 30px; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #0284c7; display: inline-block; border-radius: 10px;'>
                        " . escape($otp) . "
                    </div>
                </div>
                <p style='color: #475569; font-size: 14px;'>Kode OTP ini hanya berlaku selama <strong>10 menit</strong>. Jangan berikan kode ini kepada siapapun demi menjaga keamanan akun Anda.</p>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;'>
                <p style='color: #64748b; font-size: 12px; margin-bottom: 0; text-align: center;'>&copy; " . $tahun . " DompetKu. Keamanan finansial Anda adalah prioritas kami.</p>
            </div>
        </div>
    </div>
    ";

    return send_smtp_mail($email, $subject, $email_template);
}
