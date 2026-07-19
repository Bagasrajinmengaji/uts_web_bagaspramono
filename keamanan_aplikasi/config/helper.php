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

// Aktifkan Output Buffering untuk menyisipkan skrip & elemen Dark Mode secara otomatis di halaman HTML
if (
    php_sapi_name() !== 'cli' &&
    (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') &&
    (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false)
) {
    ob_start(function($buffer) {
        // Abaikan halaman ekspor/unduhan binary agar file tidak rusak
        $script_name = basename($_SERVER['SCRIPT_NAME']);
        $is_export = strpos($script_name, 'export_') === 0 || strpos($script_name, 'download_') === 0;
        if ($is_export) {
            return $buffer;
        }

        // Periksa apakah output berupa dokumen HTML lengkap (mengandung tag </body>)
        if (stripos($buffer, '</body>') === false) {
            return $buffer;
        }

        // 1. Injeksi skrip inisialisasi tema instan ke dalam <head> guna mencegah kedipan latar belakang putih
        $head_script = '
    <script>
        (function() {
            const savedTheme = localStorage.getItem("theme") || "light";
            document.documentElement.setAttribute("data-theme", savedTheme);
        })();
    </script>';
        
        if (stripos($buffer, '<head>') !== false) {
            $buffer = str_ireplace('<head>', '<head>' . $head_script, $buffer);
        } else {
            $buffer = $head_script . $buffer;
        }

        // Cache-busting: tambahkan timestamp file CSS sebagai query string agar
        // browser tidak menggunakan versi lama yang ada di cache
        $css_path = __DIR__ . '/../assets/css/style.css';
        $css_ver = file_exists($css_path) ? filemtime($css_path) : time();
        $buffer = preg_replace(
            '/(href=["\'])([^"\']*assets\/css\/style\.css)(["\'])/',
            '$1$2?v=' . $css_ver . '$3',
            $buffer
        );

        // 2. Injeksi penyesuaian otomatis untuk default warna teks & kisi-kisi pustaka Chart.js
        $chartjs_tag = 'https://cdn.jsdelivr.net/npm/chart.js';
        if (stripos($buffer, $chartjs_tag) !== false) {
            $chart_defaults_script = '
    <script>
        if (typeof Chart !== "undefined") {
            const isDark = (document.documentElement.getAttribute("data-theme") || "light") === "dark";
            Chart.defaults.color = isDark ? "#94a3b8" : "#64748b";
            Chart.defaults.borderColor = isDark ? "#334155" : "#e2e8f0";
        }
    </script>';
            // Sisipkan sesaat setelah elemen script Chart.js dimuat
            $buffer = str_ireplace('<script src="' . $chartjs_tag . '"></script>', '<script src="' . $chartjs_tag . '"></script>' . $chart_defaults_script, $buffer);
            $buffer = str_ireplace("<script src='" . $chartjs_tag . "'></script>", "<script src='" . $chartjs_tag . "'></script>" . $chart_defaults_script, $buffer);
        }

        // 3. Injeksi Tombol Toggle Melayang dan Logika Interaktif ke akhir tag </body>
        $toggle_html = '
    <!-- Tombol Saklar Tema Melayang -->
    <button id="theme-toggle-btn" class="theme-toggle-btn" title="Ganti Tema">
        <i class="bi bi-moon-stars-fill"></i>
    </button>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggleBtn = document.getElementById("theme-toggle-btn");
            if (!toggleBtn) return;
            
            const icon = toggleBtn.querySelector("i");
            
            function perbaruiIkon(theme) {
                if (theme === "dark") {
                    icon.className = "bi bi-sun-fill";
                } else {
                    icon.className = "bi bi-moon-stars-fill";
                }
            }
            
            // Atur ikon saat pertama kali halaman selesai dimuat
            const temaSekarang = document.documentElement.getAttribute("data-theme") || "light";
            perbaruiIkon(temaSekarang);
            
            // Klik listener untuk pergantian tema
            toggleBtn.addEventListener("click", function() {
                // Tambahkan kelas transisi agar pemudaran warna berlangsung mulus
                document.documentElement.classList.add("theme-transitioning");
                
                const temaLama = document.documentElement.getAttribute("data-theme") || "light";
                const temaBaru = temaLama === "dark" ? "light" : "dark";
                
                document.documentElement.setAttribute("data-theme", temaBaru);
                localStorage.setItem("theme", temaBaru);
                perbaruiIkon(temaBaru);
                
                // Perbarui Chart.js secara dinamis jika ada grafik aktif di halaman
                if (typeof Chart !== "undefined" && Chart.instances) {
                    const isDark = temaBaru === "dark";
                    Object.keys(Chart.instances).forEach(function(key) {
                        const chart = Chart.instances[key];
                        
                        if (chart.options.scales) {
                            Object.keys(chart.options.scales).forEach(function(scaleKey) {
                                const scale = chart.options.scales[scaleKey];
                                if (scale.ticks) {
                                    scale.ticks.color = isDark ? "#94a3b8" : "#64748b";
                                }
                                if (scale.grid) {
                                    scale.grid.color = isDark ? "#334155" : "#e2e8f0";
                                }
                            });
                        }
                        
                        if (chart.options.plugins && chart.options.plugins.legend && chart.options.plugins.legend.labels) {
                            chart.options.plugins.legend.labels.color = isDark ? "#94a3b8" : "#64748b";
                        }
                        
                        chart.update();
                    });
                }
                
                // Lepaskan kelas transisi setelah animasi selesai
                setTimeout(function() {
                    document.documentElement.classList.remove("theme-transitioning");
                }, 400);
            });
        });
    </script>';

        return str_ireplace('</body>', $toggle_html . '</body>', $buffer);
    });
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

    // Cek status aktif user dari database pada setiap page load
    global $pdo;
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("SELECT username, foto_profile, is_active FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(["id" => $_SESSION["user_id"]]);
            $u = $stmt->fetch();
            if ($u) {
                $user_active = isset($u["is_active"]) ? intval($u["is_active"]) : 1;
                if ($user_active === 0) {
                    // Bersihkan session
                    $_SESSION = [];
                    if (ini_get("session.use_cookies")) {
                        $params = session_get_cookie_params();
                        setcookie(session_name(), '', time() - 42000,
                            $params["path"], $params["domain"],
                            $params["secure"], $params["httponly"]
                        );
                    }
                    session_destroy();
                    
                    // Set flash message menggunakan session baru
                    session_start();
                    set_flash_message("danger", "Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi dukungan.");
                    header("Location: login.php");
                    exit();
                }
                
                $_SESSION["username"] = $u["username"];
                $_SESSION["foto_profile"] = $u["foto_profile"];
                $_SESSION["foto_profile_loaded"] = $_SESSION["user_id"];
            }
        } catch (\Exception $e) {
            error_log("Gagal memuat detail profil user: " . $e->getMessage());
        }
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

// Memastikan pengguna memiliki role admin.
// Jika bukan admin, set flash message "Akses ditolak" lalu arahkan ke dashboard biasa.
function admin_check()
{
    // Pastikan sudah login dulu
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }

    // Cek role di session
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
        set_flash_message("danger", "Akses ditolak. Halaman ini khusus untuk Administrator.");
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
        "SMTP_FROM_NAME"       => "DompetKu",
        "DB_HOST"              => "localhost",
        "DB_NAME"              => "dompetku",
        "DB_USER"              => "root",
        "DB_PASS"              => ""
    ];
    foreach ($defaults as $key => $val) {
        // Cek jika variabel env ada di level OS (misal di Render.com atau runtime container)
        $system_val = getenv($key);
        if ($system_val !== false) {
            $_ENV[$key] = $system_val;
            $_SERVER[$key] = $system_val;
        } else {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $val;
            }
            if (!isset($_SERVER[$key])) {
                $_SERVER[$key] = $val;
            }
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
                    <a href='http://localhost/uts_web_bagaspramono/keamanan_aplikasi/login.php' style='background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); color: #ffffff; text-decoration: none; padding: 12px 30px; font-weight: bold; border-radius: 10px; display: inline-block; box-shadow: 0 4px 10px rgba(56, 189, 248, 0.3); font-size: 15px;'>Mulai Catat Keuangan</a>
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

// Bagian 10: Helper Pengiriman Email Background Asinkron dengan Fallback Sinkron
function send_email_async($params)
{
    $email = isset($params["email"]) ? trim($params["email"]) : "";
    $username = isset($params["username"]) ? trim($params["username"]) : "";
    $type = isset($params["type"]) ? trim($params["type"]) : "login";
    $method = isset($params["method"]) ? trim($params["method"]) : "Kredensial Standard";
    $link = isset($params["link"]) ? trim($params["link"]) : "";
    $otp = isset($params["otp"]) ? trim($params["otp"]) : "";

    if (empty($email) || empty($username)) {
        error_log("send_email_async: Parameter kurang (email atau username kosong).");
        return false;
    }

    // 1. Coba kirim menggunakan HTTP loopback request secara asinkron (untuk server hosting web)
    if (isset($_SERVER["HTTP_HOST"])) {
        $host = $_SERVER["HTTP_HOST"];
        $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") ? "ssl://" : "";
        $port = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") ? 443 : 80;
        
        $path = rtrim(dirname($_SERVER["PHP_SELF"]), '/\\') . "/send_email_bg_http.php";
        
        $params["token"] = md5(($_ENV["DB_PASS"] ?? "dompetku_secret") . "dompetku_async");
        $query = http_build_query($params);
        
        $fp = @fsockopen($protocol . $host, $port, $errno, $errstr, 2);
        if ($fp) {
            $out = "POST {$path} HTTP/1.1\r\n";
            $out .= "Host: {$host}\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "Content-Length: " . strlen($query) . "\r\n";
            $out .= "Connection: Close\r\n\r\n";
            $out .= $query;
            
            fwrite($fp, $out);
            fclose($fp);
            return true;
        } else {
            error_log("send_email_async fsockopen failed: {$errstr} ({$errno})");
        }
    }

    // 2. Fallback CLI (jika dipanggil dari CLI atau fsockopen gagal)
    $popen_allowed = true;
    $disabled_functions = explode(',', ini_get('disable_functions'));
    $disabled_functions = array_map('trim', $disabled_functions);
    if (
        in_array('popen', $disabled_functions) || 
        !function_exists('popen') || 
        in_array('pclose', $disabled_functions) || 
        !function_exists('pclose')
    ) {
        $popen_allowed = false;
    }

    if ($popen_allowed) {
        $bg_script = __DIR__ . "/../send_email_bg.php";
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "start /B C:\\xampp\\php\\php.exe " . escapeshellarg($bg_script) . 
                   " --email=" . escapeshellarg($email) . 
                   " --username=" . escapeshellarg($username) . 
                   " --method=" . escapeshellarg($method) . 
                   " --type=" . escapeshellarg($type) . 
                   " --link=" . escapeshellarg($link) . 
                   " --otp=" . escapeshellarg($otp);
        } else {
            $php_bin = defined('PHP_BINARY') && !empty(PHP_BINARY) ? PHP_BINARY : 'php';
            if (basename($php_bin) !== 'php' && basename($php_bin) !== 'php-cli') {
                $php_bin = 'php';
            }
            $cmd = escapeshellcmd($php_bin) . " " . escapeshellarg($bg_script) . 
                   " --email=" . escapeshellarg($email) . 
                   " --username=" . escapeshellarg($username) . 
                   " --method=" . escapeshellarg($method) . 
                   " --type=" . escapeshellarg($type) . 
                   " --link=" . escapeshellarg($link) . 
                   " --otp=" . escapeshellarg($otp) . " > /dev/null 2>&1 &";
        }

        try {
            $handle = @popen($cmd, "r");
            if ($handle !== false) {
                pclose($handle);
                return true;
            }
        } catch (\Throwable $t) {
            error_log("popen failed inside send_email_async: " . $t->getMessage());
        }
    }

    // 3. Fallback Sinkron terakhir jika semua asinkron gagal
    error_log("send_email_async: Menjalankan secara sinkron (fallback) untuk type = {$type}");
    if ($type === "register") {
        return send_register_welcome_email($username, $email);
    } else if ($type === "register_otp") {
        return send_otp_email($username, $email, $otp);
    } else if ($type === "forgot_password") {
        return send_forgot_password_email($username, $email, $link);
    } else if ($type === "admin_register") {
        return notify_admin_register($username, $email);
    } else {
        return send_login_welcome_email($username, $email, $method);
    }
}

