<?php
// Security Helpers and General Utilities

// 1. Safe Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    // Enforce cookie-only sessions and prevent uninitialized session ID acceptance (Session Adoption)
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    // Configure session cookie params securely (Lifetime, Path, Domain, Secure, HttpOnly, SameSite)
    // SameSite=Lax mitigates CSRF attacks; HttpOnly blocks XSS access to the session ID
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => true,
        'samesite' => 'Lax'
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
function escape($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 3. Authentication Check Guard
 * Ensures only logged-in users can access specific pages.
 */
function auth_check() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * 4. Guest Check Guard
 * Redirects logged-in users away from authentication pages (login, register).
 */
function guest_check() {
    if (isset($_SESSION['user_id'])) {
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * 5. Format Currency to Rupiah
 *
 * @param float|int $number
 * @return string
 */
function format_rupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}

/**
 * 6. Set Alert Flash Message
 * Stores a message in session to display after page redirect.
 *
 * @param string $type ('success' or 'danger')
 * @param string $message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * 7. Render Alert Flash Message
 * Displays the flash message stored in session using Bootstrap 5 alert template.
 */
function display_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']); // Clean up flash message after displaying once
        
        $type = ($flash['type'] === 'success') ? 'success' : 'danger';
        $icon = ($flash['type'] === 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show d-flex align-items-center" role="alert">';
        echo '  <i class="bi ' . $icon . ' me-2"></i>';
        echo '  <div>' . escape($flash['message']) . '</div>';
        echo '  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}
/**
 * Validasi jenis transaksi
 */
function in_amount_type($val)
{
    return in_array(
        $val,
        ['Pemasukan', 'Pengeluaran'],
        true
    );
}
function send_smtp_mail($to, $subject, $message_body) {
    // Pengaturan Akun SMTP
    $smtp_host  = isset($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : '';
    $smtp_port  = isset($_ENV['SMTP_PORT']) ? intval($_ENV['SMTP_PORT']) : 587;
    $smtp_user  = isset($_ENV['SMTP_USERNAME']) ? $_ENV['SMTP_USERNAME'] : (isset($_ENV['SMTP_UNAME']) ? $_ENV['SMTP_UNAME'] : (isset($_ENV['SMTP_USER']) ? $_ENV['SMTP_USER'] : ''));
    $smtp_pass  = isset($_ENV['SMTP_PASSWORD']) ? $_ENV['SMTP_PASSWORD'] : (isset($_ENV['SMTP_PASS']) ? $_ENV['SMTP_PASS'] : '');
    $from_email = isset($_ENV['SMTP_FROM_EMAIL']) ? $_ENV['SMTP_FROM_EMAIL'] : $smtp_user;
    $from_name  = isset($_ENV['SMTP_FROM_NAME']) ? $_ENV['SMTP_FROM_NAME'] : 'DompetKu';

    // 1. Membuka Koneksi Socket ke Server SMTP
    $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
    if (!$socket) return false;

    // Set timeout baca/tulis socket 5 detik agar tidak hang selamanya
    stream_set_timeout($socket, 5);

    function get_smtp_response($socket) {
        $response = '';
        while (substr($response, 3, 1) != ' ') {
            if (!($line = fgets($socket, 256))) break;
            $response .= $line;
            
            // Cek jika terjadi timeout pada stream
            $info = stream_get_meta_data($socket);
            if ($info['timed_out']) break;
        }
        return $response;
    }

    get_smtp_response($socket); // Baca baris sambutan server

    // 2. Jabat tangan (EHLO) dan Mulai Enkripsi TLS
    fwrite($socket, "EHLO " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . "\r\n");
    get_smtp_response($socket);

    fwrite($socket, "STARTTLS\r\n");
    get_smtp_response($socket);
    
    $crypto_res = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    if (!$crypto_res) {
        fclose($socket);
        return false;
    }

    // 3. Jabat tangan ulang setelah enkripsi aktif + Login Otentikasi
    fwrite($socket, "EHLO " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . "\r\n");
    get_smtp_response($socket);

    fwrite($socket, "AUTH LOGIN\r\n");
    get_smtp_response($socket);

    fwrite($socket, base64_encode($smtp_user) . "\r\n");
    get_smtp_response($socket);

    fwrite($socket, base64_encode($smtp_pass) . "\r\n");
    get_smtp_response($socket);

    // 4. Set Pengirim dan Penerima
    fwrite($socket, "MAIL FROM: <$from_email>\r\n");
    get_smtp_response($socket);

    fwrite($socket, "RCPT TO: <$to>\r\n");
    get_smtp_response($socket);

    // 5. Mengirimkan Konten Email (Header & Body)
    fwrite($socket, "DATA\r\n");
    get_smtp_response($socket);

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?".base64_encode($from_name)."?= <$from_email>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\n";

    fwrite($socket, $headers . "\r\n" . $message_body . "\r\n.\r\n");
    get_smtp_response($socket);

    // 6. Tutup Koneksi
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}
function load_env() {
    $paths = [
        __DIR__ . '/../../.env',
        __DIR__ . '/../.env'
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue; // Lewati komentar
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim(trim($value), '"\''); // Hapus kutip jika ada
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
            break;
        }
    }
}

// Langsung jalankan fungsinya agar env siap dipakai di file lain
load_env();

/**
 * Menghitung pengeluaran berjalan dan membandingkannya dengan limit anggaran bulanan (PDO Version)
 */
function dapatkan_analisis_anggaran($id_user, $bulan, $tahun) {
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
                    AND MONTH(t.tanggal) = :bulan 
                    AND YEAR(t.tanggal) = :tahun
                  WHERE a.id_user = :id_user AND a.bulan = :bulan_anggaran AND a.tahun = :tahun_anggaran
                  GROUP BY a.id_kategori, k.nama_kategori, a.jumlah_budget";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'bulan'          => $bulan,
            'tahun'          => $tahun,
            'id_user'        => $id_user,
            'bulan_anggaran' => $bulan,
            'tahun_anggaran' => $tahun
        ]);
        
        while ($row = $stmt->fetch()) {
            $budget = floatval($row['jumlah_budget']);
            $pengeluaran = floatval($row['total_pengeluaran']);
            $persentase = ($budget > 0) ? ($pengeluaran / $budget) * 100 : 0;

            $hasil_analisis[] = [
                'id_kategori'   => $row['id_kategori'],
                'nama_kategori' => $row['nama_kategori'],
                'budget'        => $budget,
                'pengeluaran'   => $pengeluaran,
                'persentase'    => $persentase
            ];
        }
    } catch (\PDOException $e) {
        error_log($e->getMessage());
    }
    
    return $hasil_analisis;
}