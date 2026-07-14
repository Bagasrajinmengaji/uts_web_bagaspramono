<?php
/**
 * Process Email Queue — Background Email Processor
 * Dipanggil via AJAX dari dashboard.php setelah halaman dimuat.
 * Membaca email dari tabel `email_queue` dan mengirimnya via SMTP.
 */

// Nonaktifkan penampilan error agar tidak mengganggu JSON response
ini_set('display_errors', 0);

require_once "config/koneksi.php";
require_once "config/helper.php";

// Session check removed to allow background queue processing from registration/login page.
// This is secure because the email queue is populated only by server-side code.

header("Content-Type: application/json");

try {
    // Ambil email pending (maksimal 5 per batch untuk menghindari timeout)
    $stmt = $pdo->prepare(
        "SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 5"
    );
    $stmt->execute();
    $emails = $stmt->fetchAll();

    if (empty($emails)) {
        echo json_encode(["status" => "ok", "sent" => 0, "message" => "Tidak ada email dalam antrian"]);
        exit();
    }

    $sent = 0;
    $failed = 0;

    foreach ($emails as $email_row) {
        // Tandai sebagai 'processing' dulu untuk menghindari duplikat
        $update_processing = $pdo->prepare(
            "UPDATE email_queue SET status = 'processing' WHERE id = :id AND status = 'pending'"
        );
        $update_processing->execute(["id" => $email_row["id"]]);

        // Jika row sudah diproses oleh request lain (race condition), lewati
        if ($update_processing->rowCount() === 0) {
            continue;
        }

        // Kirim email via SMTP
        $smtp_error = null;
        $result = send_smtp_mail(
            $email_row["to_email"],
            $email_row["subject"],
            $email_row["body"],
            1, // max_retries
            $smtp_error
        );

        // Update status berdasarkan hasil pengiriman
        $new_status = $result ? "sent" : "failed";
        $update_status = $pdo->prepare(
            "UPDATE email_queue SET status = :status, error_message = :error_message, processed_at = NOW() WHERE id = :id"
        );
        $update_status->execute([
            "status" => $new_status,
            "error_message" => $smtp_error,
            "id" => $email_row["id"],
        ]);

        if ($result) {
            $sent++;
        } else {
            $failed++;
        }
    }

    echo json_encode([
        "status" => "ok",
        "sent" => $sent,
        "failed" => $failed,
        "total" => count($emails),
    ]);
} catch (\PDOException $e) {
    error_log("Process Email Queue Error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
