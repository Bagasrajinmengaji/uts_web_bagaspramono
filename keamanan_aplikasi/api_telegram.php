<?php
// api_telegram.php
// Webhook Chatbot Telegram untuk pencatatan otomatis transaksi DompetKu

require_once "config/koneksi.php";
require_once "config/helper.php";

header('Content-Type: application/json');

// Log incoming request for debugging
file_put_contents("telegram_log.txt", "Incoming: " . file_get_contents("php://input") . "\n", FILE_APPEND);


// 1. Ambil token bot dan ID Telegram Anda dari environment variable (.env)
$bot_token  = isset($_ENV["TELEGRAM_BOT_TOKEN"]) ? $_ENV["TELEGRAM_BOT_TOKEN"] : "";
$my_chat_id = isset($_ENV["TELEGRAM_MY_CHAT_ID"]) ? intval($_ENV["TELEGRAM_MY_CHAT_ID"]) : 0;

file_put_contents("telegram_log.txt", "Loaded Token: '$bot_token', My Chat ID: '$my_chat_id'\n", FILE_APPEND);

if (empty($bot_token) || $my_chat_id <= 0) {
    file_put_contents("telegram_log.txt", "ERROR: Token or Chat ID empty!\n", FILE_APPEND);
    exit();
}


// 2. Ambil data JSON kiriman dari server Telegram (Webhook)
$content = file_get_contents("php://input");
$update  = json_decode($content, true);

if (!$update || !isset($update["message"])) {
    exit();
}

$message = $update["message"];
$chat_id = $message["chat"]["id"]; // ID Chat Telegram pengirim
$text    = isset($message["text"]) ? trim($message["text"]) : "";

// 3. KEAMANAN: Validasi bahwa pengirim pesan adalah Anda sendiri (menggunakan ID di .env)
if ($chat_id !== $my_chat_id) {
    send_reply($chat_id, "⚠️ Akses ditolak! Bot ini dikonfigurasi khusus secara privat untuk pemilik DompetKu.", $bot_token);
    exit();
}

// 4. PARSER FORMAT PESAN: 
// Format baru: "out [nominal] # [keterangan] # [nama_dompet] # [nama_kategori]"
// Format lama (fallback): "out [nominal] [keterangan]"
$command = "";
$nominal = 0.0;
$keterangan = "";
$nama_dompet = "";
$nama_kategori = "";

if (strpos($text, "#") !== false) {
    $parts = explode("#", $text);
    
    $first_part = trim($parts[0]);
    $first_data = explode(" ", $first_part, 2);
    $command    = strtolower(trim($first_data[0] ?? ''));
    $nominal    = floatval(trim($first_data[1] ?? '0'));
    
    $keterangan   = isset($parts[1]) ? trim($parts[1]) : 'Tanpa Keterangan';
    $nama_dompet   = isset($parts[2]) ? trim($parts[2]) : '';
    $nama_kategori = isset($parts[3]) ? trim($parts[3]) : '';
} else {
    $data = explode(" ", $text, 3);
    $command    = strtolower($data[0] ?? '');
    $nominal    = isset($data[1]) ? floatval($data[1]) : 0.0;
    $keterangan = isset($data[2]) ? trim($data[2]) : 'Tanpa Keterangan';
}

if (($command === 'out' || $command === 'in') && $nominal > 0) {
    $jenis = ($command === 'out') ? 'Pengeluaran' : 'Pemasukan';

    // Temukan ID Pengguna berdasarkan email SMTP di .env (sebagai pemilik aplikasi)
    $owner_email = isset($_ENV["SMTP_UNAME"]) ? $_ENV["SMTP_UNAME"] : "";
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmtUser->execute(['email' => $owner_email]);
    $owner = $stmtUser->fetch();
    
    $user_id = $owner ? intval($owner['id']) : 4; // Fallback ke ID 4 jika tidak ditemukan

    try {
        $pdo->beginTransaction();

        $created_wallet_msg = "";
        $created_cat_msg = "";

        // --- RESOLVE WALLET (DOMPET) ---
        $id_dompet = null;
        if (!empty($nama_dompet)) {
            // Cek apakah dompet sudah ada (case-insensitive)
            $stmtCheckDompet = $pdo->prepare("SELECT id_dompet FROM dompet WHERE id_user = :uid AND LOWER(nama_dompet) = LOWER(:nama) LIMIT 1");
            $stmtCheckDompet->execute(['uid' => $user_id, 'nama' => $nama_dompet]);
            $dompet = $stmtCheckDompet->fetch();
            
            if ($dompet) {
                $id_dompet = $dompet['id_dompet'];
            } else {
                // Buat dompet baru otomatis
                $stmtInsDompet = $pdo->prepare("INSERT INTO dompet (id_user, nama_dompet) VALUES (:uid, :nama)");
                $stmtInsDompet->execute(['uid' => $user_id, 'nama' => $nama_dompet]);
                $id_dompet = $pdo->lastInsertId();
                $created_wallet_msg = "\n📂 *Dompet Baru Dibuat*: " . escape($nama_dompet);
            }
        } else {
            // Ambil dompet pertama milik user
            $stmtDompet = $pdo->prepare("SELECT id_dompet, nama_dompet FROM dompet WHERE id_user = :uid LIMIT 1");
            $stmtDompet->execute(['uid' => $user_id]);
            $dompet = $stmtDompet->fetch();
            if ($dompet) {
                $id_dompet = $dompet['id_dompet'];
                $nama_dompet = $dompet['nama_dompet'];
            } else {
                // Jika tidak ada dompet sama sekali, buatkan "Dompet Utama"
                $nama_dompet = "Dompet Utama";
                $stmtInsDompet = $pdo->prepare("INSERT INTO dompet (id_user, nama_dompet) VALUES (:uid, :nama)");
                $stmtInsDompet->execute(['uid' => $user_id, 'nama' => $nama_dompet]);
                $id_dompet = $pdo->lastInsertId();
                $created_wallet_msg = "\n📂 *Dompet Baru Dibuat*: Dompet Utama";
            }
        }

        // --- RESOLVE CATEGORY (KATEGORI) ---
        $id_kategori = null;
        if (!empty($nama_kategori)) {
            // Cek apakah kategori sudah ada (case-insensitive)
            $stmtCheckCat = $pdo->prepare("SELECT id_kategori FROM kategori WHERE id_user = :uid AND LOWER(nama_kategori) = LOWER(:nama) AND tipe = :tipe LIMIT 1");
            $stmtCheckCat->execute(['uid' => $user_id, 'nama' => $nama_kategori, 'tipe' => $jenis]);
            $cat = $stmtCheckCat->fetch();
            
            if ($cat) {
                $id_kategori = $cat['id_kategori'];
            } else {
                // Buat kategori baru otomatis
                $stmtInsCat = $pdo->prepare("INSERT INTO kategori (id_user, nama_kategori, tipe) VALUES (:uid, :nama, :tipe)");
                $stmtInsCat->execute(['uid' => $user_id, 'nama' => $nama_kategori, 'tipe' => $jenis]);
                $id_kategori = $pdo->lastInsertId();
                $created_cat_msg = "\n🏷️ *Kategori Baru Dibuat*: " . escape($nama_kategori);
            }
        }

        // --- MASUKKAN TRANSAKSI ---
        $stmtTx = $pdo->prepare(
            "INSERT INTO transaksi (user_id, id_kategori, id_dompet, jenis, nominal, keterangan, tanggal) 
             VALUES (:uid, :id_kategori, :id_dompet, :jenis, :nominal, :keterangan, NOW())"
        );
        $stmtTx->execute([
            'uid'         => $user_id,
            'id_kategori' => $id_kategori,
            'id_dompet'   => $id_dompet,
            'jenis'       => $jenis,
            'nominal'     => $nominal,
            'keterangan'  => "[Telegram] " . $keterangan
        ]);

        $pdo->commit();

        // Format balasan sukses
        $reply = "✅ *Transaksi Berhasil Dicatat!*\n\n";
        $reply .= "• *Jenis*: " . $jenis . "\n";
        $reply .= "• *Nominal*: Rp " . number_format($nominal, 0, ',', '.') . "\n";
        $reply .= "• *Keterangan*: " . escape($keterangan) . "\n";
        $reply .= "• *Dompet*: " . escape($nama_dompet) . "\n";
        if (!empty($nama_kategori)) {
            $reply .= "• *Kategori*: " . escape($nama_kategori) . "\n";
        }
        
        $reply .= $created_wallet_msg;
        $reply .= $created_cat_msg;
        $reply .= "\n\n_Silakan cek dashboard DompetKu Anda._";

        send_reply($chat_id, $reply, $bot_token);
        exit();

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        file_put_contents("telegram_log.txt", "DB Error: " . $e->getMessage() . "\n", FILE_APPEND);
        send_reply($chat_id, "❌ Terjadi kesalahan saat menyimpan transaksi ke database. Detail: " . $e->getMessage(), $bot_token);
        exit();
    }
}

// Balasan bantuan default jika format pesan yang dikirim tidak sesuai
$help  = "❓ *Format Perintah Salah atau Bot Baru Dimulai!*\n\n";
$help .= "Gunakan format pencatatan berikut:\n";
$help .= "• `out [nominal] # [keterangan] # [dompet] # [kategori]`\n";
$help .= "  _Contoh: out 15000 # Kopi # Shopee pay # Jajan_\n\n";
$help .= "• Atau ketik cepat tanpa `#` (default dompet):\n";
$help .= "  _Contoh: out 15000 beli es kopi_\n\n";
$help .= "👇 *Salin Template Cepat di bawah ini (Ketuk sekali untuk menyalin otomatis, lalu tempel di kolom chat dan ubah isinya):*\n\n";
$help .= "• `out 15000 # Kopi # Shopee pay # Jajan`\n";
$help .= "• `out 20000 # Bensin # SEA BANK # Transportasi`\n";
$help .= "• `out 25000 # Makan siang # Shopee pay # Makanan`\n";
$help .= "• `in 50000 # Uang saku # SEA BANK # Pemasukan`";

// Hapus layout tombol keyboard bawaan sebelumnya agar tidak terkirim otomatis
$keyboard_remove = [
    "remove_keyboard" => true
];

send_reply($chat_id, $help, $bot_token, $keyboard_remove);

// =========================================================================
// HELPER FUNCTION: Mengirim Pesan Balasan ke Server API Telegram via cURL
// =========================================================================
function send_reply($chat_id, $text, $token, $reply_markup = null) {
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
    $post_fields = [
        'chat_id'    => $chat_id,
        'text'       => $text,
        'parse_mode' => 'Markdown'
    ];
    
    if ($reply_markup !== null) {
        $post_fields['reply_markup'] = json_encode($reply_markup);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}
