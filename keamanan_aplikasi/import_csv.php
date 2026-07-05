<?php
// Manggil file koneksi dan helper
require_once "config/koneksi.php";
require_once "config/helper.php";

// Pastikan user sudah login
auth_check();

$user_id = $_SESSION["user_id"];

// Fungsi untuk mendeteksi pembatas (delimiter) CSV secara otomatis
function detect_delimiter($file_path)
{
    $file = fopen($file_path, "r");
    if (!$file) {
        return ";";
    } // default
    $first_line = fgets($file);
    fclose($file);

    $delimiters = [
        ";" => 0,
        "," => 0,
        "\t" => 0,
    ];

    foreach ($delimiters as $delim => &$count) {
        $count = substr_count($first_line, $delim);
    }

    arsort($delimiters);
    return key($delimiters); // Kembalikan delimiter dengan jumlah terbanyak
}

// Fungsi pembantu untuk memvalidasi dan memformat tanggal secara fleksibel
function parse_import_date($date_str)
{
    $date_str = trim($date_str);
    $formats = ["Y-m-d", "d/m/Y", "d-m-Y", "Y/m/d"];
    foreach ($formats as $format) {
        $d = DateTime::createFromFormat($format, $date_str);
        if ($d && $d->format($format) === $date_str) {
            return $d->format("Y-m-d");
        }
    }
    return false;
}

// Fungsi pembantu untuk membersihkan nominal uang
function clean_import_nominal($nominal_str)
{
    $nominal_str = trim($nominal_str);
    // Hapus karakter mata uang (seperti Rp, RP, $, dll) dan spasi
    $clean = preg_replace("/[^\d.,-]/", "", $nominal_str);

    // Jika ada tanda titik (ribuan) dan koma (desimal), contoh: 1.250.000,50 atau 12,500.00
    if (strpos($clean, ".") !== false && strpos($clean, ",") !== false) {
        // Deteksi mana desimal dan mana ribuan
        if (strrpos($clean, ",") > strrpos($clean, ".")) {
            // Format ID: ribuan = titik, desimal = koma
            $clean = str_replace(".", "", $clean);
            $clean = str_replace(",", ".", $clean);
        } else {
            // Format EN: ribuan = koma, desimal = titik
            $clean = str_replace(",", "", $clean);
        }
    } elseif (strpos($clean, ",") !== false) {
        // Hanya ada koma, bisa jadi desimal (format ID, misal 50000,00) atau ribuan (format EN, misal 50,000)
        $parts = explode(",", $clean);
        if (strlen(end($parts)) === 2) {
            // Anggap desimal
            $clean = str_replace(",", ".", $clean);
        } else {
            // Anggap ribuan
            $clean = str_replace(",", "", $clean);
        }
    } elseif (strpos($clean, ".") !== false) {
        // Hanya ada titik, bisa jadi ribuan (format ID, misal 50.000) atau desimal (format EN, misal 50000.00)
        $parts = explode(".", $clean);
        if (strlen(end($parts)) === 3) {
            // Anggap ribuan, hilangkan titiknya
            $clean = str_replace(".", "", $clean);
        }
    }

    return is_numeric($clean) ? floatval($clean) : false;
}

// Proses form upload jika ada request POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["file_transaksi"])) {
    $file = $_FILES["file_transaksi"];

    // Validasi error upload
    if ($file["error"] !== UPLOAD_ERR_OK) {
        set_flash_message("danger", "Terjadi kesalahan saat mengunggah file.");
        header("Location: dashboard.php");
        exit();
    }

    // Validasi ekstensi berkas
    $file_info = pathinfo($file["name"]);
    $extension = strtolower($file_info["extension"]);
    if (!in_array($extension, ["csv", "txt"], true)) {
        set_flash_message(
            "danger",
            "Format berkas tidak didukung. Silakan unggah file CSV yang disimpan dari Excel.",
        );
        header("Location: dashboard.php");
        exit();
    }

    $tmp_name = $file["tmp_name"];

    // Deteksi delimiter secara otomatis
    $delimiter = detect_delimiter($tmp_name);

    $handle = fopen($tmp_name, "r");
    if (!$handle) {
        set_flash_message("danger", "Gagal membuka file transaksi.");
        header("Location: dashboard.php");
        exit();
    }

    // Lewati baris pertama (Header Kolom)
    $header = fgetcsv($handle, 1000, $delimiter);

    // Validasi kolom header dasar untuk memastikan template cocok
    if (!$header || count($header) < 4) {
        fclose($handle);
        set_flash_message(
            "danger",
            "Format header kolom salah. Pastikan berkas memiliki minimal 4 kolom: Tanggal, Jenis, Nominal, Keterangan.",
        );
        header("Location: dashboard.php");
        exit();
    }

    // Gunakan database transaction agar impor bersifat atomik (semua sukses atau semua gagal)
    $pdo->beginTransaction();

    $row_num = 1; // Untuk penanda baris jika terjadi error
    $imported_count = 0;

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO transaksi (user_id, jenis, nominal, keterangan, tanggal) VALUES (:user_id, :jenis, :nominal, :keterangan, :tanggal)",
        );

        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
            $row_num++;

            // Lewati baris kosong
            if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                continue;
            }

            // Pastikan baris memiliki minimal 4 kolom
            if (count($row) < 4) {
                throw new Exception("Baris ke-$row_num kekurangan kolom data.");
            }

            $raw_tanggal = $row[0];
            $raw_jenis = $row[1];
            $raw_nominal = $row[2];
            $raw_keterangan = $row[3];

            // 1. Validasi & Format Tanggal
            $tanggal = parse_import_date($raw_tanggal);
            if (!$tanggal) {
                throw new Exception(
                    "Baris ke-$row_num: Format Tanggal '$raw_tanggal' tidak valid. Gunakan format YYYY-MM-DD atau DD/MM/YYYY.",
                );
            }

            // 2. Validasi Jenis
            $jenis = trim($raw_jenis);
            // Normalisasi huruf kapital (Pemasukan / Pengeluaran)
            $jenis = ucfirst(strtolower($jenis));
            if (!in_array($jenis, ["Pemasukan", "Pengeluaran"], true)) {
                throw new Exception(
                    "Baris ke-$row_num: Jenis transaksi '$raw_jenis' tidak valid. Harus berupa 'Pemasukan' atau 'Pengeluaran'.",
                );
            }

            // 3. Validasi & Format Nominal
            $nominal = clean_import_nominal($raw_nominal);
            if ($nominal === false || $nominal <= 0) {
                throw new Exception(
                    "Baris ke-$row_num: Nominal '$raw_nominal' tidak valid. Harus berupa angka positif.",
                );
            }

            // 4. Validasi Keterangan
            $keterangan = trim($raw_keterangan);
            if (empty($keterangan)) {
                throw new Exception(
                    "Baris ke-$row_num: Keterangan tidak boleh kosong.",
                );
            }
            if (strlen($keterangan) > 255) {
                $keterangan = substr($keterangan, 0, 255);
            }

            // Eksekusi insert data ke database
            $stmt->execute([
                "user_id" => $user_id,
                "jenis" => $jenis,
                "nominal" => $nominal,
                "keterangan" => $keterangan,
                "tanggal" => $tanggal,
            ]);

            $imported_count++;
        }

        fclose($handle);

        if ($imported_count === 0) {
            $pdo->rollBack();
            set_flash_message(
                "danger",
                "Tidak ada transaksi baru yang diimpor. Berkas kosong.",
            );
        } else {
            // Commit semua perubahan jika sukses semua
            $pdo->commit();
            set_flash_message(
                "success",
                "Berhasil mengimpor $imported_count transaksi baru!",
            );
        }
    } catch (Exception $e) {
        fclose($handle);
        // Batalkan seluruh data yang diinsert jika ada satu saja baris yang salah
        $pdo->rollBack();
        set_flash_message(
            "danger",
            "Gagal mengimpor data! " . $e->getMessage(),
        );
    }

    header("Location: dashboard.php");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
