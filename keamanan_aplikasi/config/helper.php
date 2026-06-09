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
}
