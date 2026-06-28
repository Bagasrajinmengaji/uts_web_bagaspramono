<?php
// Manggil file koneksi dan helper
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Pastikan user sudah login
auth_check();

$user_id = $_SESSION['user_id'];

// Fungsi untuk mendeteksi pembatas (delimiter) CSV secara otomatis
function detect_delimiter($file_path) {
    $file = fopen($file_path, 'r');
    if (!$file) return ';'; // default
    $first_line = fgets($file);
    fclose($file);
    
    $delimiters = [
        ';'  => 0,
        ','  => 0,
        "\t" => 0
    ];
    
    foreach ($delimiters as $delim => &$count) {
        $count = substr_count($first_line, $delim);
    }
    
    arsort($delimiters);
    return key($delimiters); // Kembalikan delimiter dengan jumlah terbanyak
}

// =========================================================================
// 1. HELPER PARSER ZIP KUSTOM (Penyelamat tanpa ekstensi ZipArchive)
// =========================================================================
function unzip_docx_data($zip_content, $target_file) {
    $pos = 0;
    // Tanda tangan lokal file header ZIP adalah PK\x03\x04
    while (($pos = strpos($zip_content, "\x50\x4b\x03\x04", $pos)) !== false) {
        $header = substr($zip_content, $pos, 30);
        if (strlen($header) < 30) break;
        
        // Unpack metadata header lokal
        $data = unpack("vversion/vflag/vmethod/vtime/vdate/Vcrc/Vcsize/Vucsize/vfnlen/viflen", substr($header, 4));
        $filename = substr($zip_content, $pos + 30, $data['fnlen']);
        
        $data_pos = $pos + 30 + $data['fnlen'] + $data['iflen'];
        
        if ($filename === $target_file) {
            $compressed_data = substr($zip_content, $data_pos, $data['csize']);
            // Method 8 adalah Deflate (kompresi ZIP standar)
            if ($data['method'] == 8) {
                return gzinflate($compressed_data);
            } elseif ($data['method'] == 0) {
                return $compressed_data; // Uncompressed
            }
            return null;
        }
        // Loncat ke file berikutnya di dalam arsip ZIP
        $pos = $data_pos + $data['csize'];
    }
    return null;
}

// =========================================================================
// 2. PARSER WORD XML (Membaca berkas word/document.xml dari berkas DOCX)
// =========================================================================
function parse_docx_xml_table($xml_content) {
    $xml = @simplexml_load_string($xml_content);
    if (!$xml) return [];
    
    // Daftarkan namespace XML WordProcessingML
    $xml->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    
    // Ambil baris-baris dari tabel pertama
    $rows = $xml->xpath('//w:tbl[1]/w:tr');
    if (!$rows) return [];
    
    $rows_data = [];
    foreach ($rows as $row) {
        $cells = $row->xpath('.//w:tc');
        if (count($cells) < 4) continue; // Lewati jika kolom tidak lengkap
        
        $row_data = [];
        foreach ($cells as $cell) {
            $texts = $cell->xpath('.//w:t');
            $cell_text = '';
            foreach ($texts as $t) {
                $cell_text .= (string)$t;
            }
            $row_data[] = trim($cell_text);
        }
        
        // Lewati baris header tabel
        if (in_array(strtolower($row_data[0]), ['no', 'no.'], true)) {
            continue;
        }
        
        $rows_data[] = $row_data;
    }
    return $rows_data;
}

// =========================================================================
// 3. PARSER WORD HTML (Fallback untuk DOCX bertipe HTML-based)
// =========================================================================
function parse_html_table($html_content) {
    $dom = new DOMDocument();
    // Nonaktifkan warning untuk HTML yang tidak sempurna
    @$dom->loadHTML($html_content);
    
    $tables = $dom->getElementsByTagName('table');
    if ($tables->length === 0) return [];
    
    // Temukan tabel transaksi yang memiliki header th lengkap
    $target_table = null;
    foreach ($tables as $table) {
        $ths = $table->getElementsByTagName('th');
        if ($ths->length >= 4) {
            $target_table = $table;
            break;
        }
    }
    if (!$target_table) return [];
    
    $rows_data = [];
    $rows = $target_table->getElementsByTagName('tr');
    foreach ($rows as $row) {
        // Lewati header
        $ths = $row->getElementsByTagName('th');
        if ($ths->length > 0) continue;
        
        $tds = $row->getElementsByTagName('td');
        if ($tds->length < 4) continue; // Lewati jika data tidak lengkap atau baris total
        
        $row_data = [];
        foreach ($tds as $td) {
            $row_data[] = trim($td->nodeValue);
        }
        
        $rows_data[] = $row_data;
    }
    return $rows_data;
}

// =========================================================================
// 4. PARSER ALIRAN TEKS PDF (Membaca teks dari berkas PDF SimplePDF kita)
// =========================================================================
function parse_pdf_text($pdf_content) {
    $texts = [];
    // Cari isi stream biner di dalam PDF
    if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $pdf_content, $streams)) {
        foreach ($streams[1] as $stream) {
            // Cari semua teks di dalam tanda kurung operator Tj: (teks) Tj
            if (preg_match_all('/\((.*?)\)\s*Tj/s', $stream, $matches)) {
                foreach ($matches[1] as $text) {
                    // Unescape tanda kurung khusus PDF
                    $text = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $text);
                    $texts[] = trim($text);
                }
            }
        }
    }
    return $texts;
}

// =========================================================================
// 5. HELPER VALIDASI DAN SANITASI DATA (Sama seperti CSV Importer)
// =========================================================================
function parse_import_date($date_str) {
    $date_str = trim($date_str);
    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd M Y'];
    foreach ($formats as $format) {
        $d = DateTime::createFromFormat($format, $date_str);
        if ($d && $d->format($format) === $date_str) {
            return $d->format('Y-m-d');
        }
    }
    // Coba tebak dengan strtotime jika formatnya bahasa inggris standar
    $ts = strtotime($date_str);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }
    return false;
}

function clean_import_nominal($nominal_str) {
    $nominal_str = trim($nominal_str);
    
    // Hilangkan simbol plus (+) atau minus (-) yang sering diekspor
    $nominal_str = ltrim($nominal_str, '+-');
    
    // Hapus karakter mata uang dan spasi
    $clean = preg_replace('/[^\d.,-]/', '', $nominal_str);
    
    if (strpos($clean, '.') !== false && strpos($clean, ',') !== false) {
        if (strrpos($clean, ',') > strrpos($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }
    } elseif (strpos($clean, ',') !== false) {
        $parts = explode(',', $clean);
        if (strlen(end($parts)) === 2) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }
    } elseif (strpos($clean, '.') !== false) {
        $parts = explode('.', $clean);
        if (strlen(end($parts)) === 3) {
            $clean = str_replace('.', '', $clean);
        }
    }
    
    return is_numeric($clean) ? floatval($clean) : false;
}

// =========================================================================
// 6. ALUR UTAMA PROSES UNGGAHAN FILE
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_dokumen'])) {
    $file = $_FILES['file_dokumen'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('danger', 'Terjadi kesalahan saat mengunggah file.');
        header('Location: dashboard.php');
        exit;
    }
    
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    $file_content = file_get_contents($file['tmp_name']);
    
    $rows_data = [];
    
    try {
        if (in_array($extension, ['docx', 'doc'], true)) {
            // DETEKSI FORMAT WORD: BINER (ZIP) atau HTML
            if (substr($file_content, 0, 4) === "\x50\x4b\x03\x04") {
                // Format Biner DOCX (ZIP) -> Ekstrak word/document.xml
                $xml_content = unzip_docx_data($file_content, 'word/document.xml');
                if (!$xml_content) {
                    throw new Exception("Gagal mengekstrak data dari berkas Word DOCX.");
                }
                $rows_data = parse_docx_xml_table($xml_content);
            } else {
                // Format HTML-based Word -> Parse sebagai HTML
                $rows_data = parse_html_table($file_content);
            }
        } elseif ($extension === 'pdf') {
            // PROSES BERKAS PDF
            $pdf_texts = parse_pdf_text($file_content);
            
            // Temukan indeks "Nominal" sebagai patokan header terakhir tabel
            $nominal_index = array_search('Nominal', $pdf_texts);
            if ($nominal_index === false) {
                throw new Exception("Format berkas PDF salah atau tidak dibuat oleh DompetKu.");
            }
            
            // Ambil semua teks setelah header "Nominal"
            $slice = array_slice($pdf_texts, $nominal_index + 1);
            $collected = [];
            foreach ($slice as $text) {
                $text_lower = strtolower($text);
                // Berhenti mengumpulkan jika menyentuh baris ringkasan/catatan kaki
                if (strpos($text_lower, 'total') === 0 || strpos($text_lower, 'catatan') === 0 || empty($text)) {
                    break;
                }
                $collected[] = $text;
            }
            
            // Kelompokkan menjadi 5 kolom per baris transaksi
            $chunks = array_chunk($collected, 5);
            foreach ($chunks as $chunk) {
                if (count($chunk) === 5) {
                    $rows_data[] = $chunk;
                }
            }
        } elseif ($extension === 'xlsx') {
            // PROSES BERKAS EXCEL (XLSX)
            require_once 'lib/SimpleXLSX.php';
            
            if ($xlsx = \Shuchkin\SimpleXLSX::parse($file['tmp_name'])) {
                $rows = $xlsx->rows();
                if (empty($rows)) {
                    throw new Exception("Berkas Excel kosong atau tidak dapat dibaca.");
                }
                
                $header = $rows[0];
                if (count($header) < 4) {
                    throw new Exception("Format header kolom Excel salah. Harus memiliki minimal 4 kolom: Tanggal, Jenis, Nominal, Keterangan.");
                }
                
                // Cari index masing-masing kolom secara dinamis berdasarkan nama headernya
                $col_indices = [
                    'tanggal' => -1,
                    'jenis' => -1,
                    'nominal' => -1,
                    'keterangan' => -1
                ];
                
                foreach ($header as $idx => $col_name) {
                    $clean_name = strtolower(trim($col_name));
                    if (strpos($clean_name, 'tanggal') !== false) {
                        $col_indices['tanggal'] = $idx;
                    } elseif (strpos($clean_name, 'jenis') !== false) {
                        $col_indices['jenis'] = $idx;
                    } elseif (strpos($clean_name, 'nominal') !== false || strpos($clean_name, 'jumlah') !== false) {
                        $col_indices['nominal'] = $idx;
                    } elseif (strpos($clean_name, 'keterangan') !== false) {
                        $col_indices['keterangan'] = $idx;
                    }
                }
                
                // Fallback default mapping jika kolom tidak terdeteksi dinamis
                if ($col_indices['tanggal'] === -1) $col_indices['tanggal'] = 0;
                if ($col_indices['jenis'] === -1) $col_indices['jenis'] = 1;
                if ($col_indices['nominal'] === -1) $col_indices['nominal'] = 2;
                if ($col_indices['keterangan'] === -1) $col_indices['keterangan'] = 3;
                
                $no_excel = 1;
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                        continue;
                    }
                    
                    $val_tanggal = isset($row[$col_indices['tanggal']]) ? $row[$col_indices['tanggal']] : '';
                    $val_jenis = isset($row[$col_indices['jenis']]) ? $row[$col_indices['jenis']] : '';
                    $val_nominal = isset($row[$col_indices['nominal']]) ? $row[$col_indices['nominal']] : '';
                    $val_keterangan = isset($row[$col_indices['keterangan']]) ? $row[$col_indices['keterangan']] : '';
                    
                    if (empty($val_tanggal) && empty($val_jenis) && empty($val_nominal) && empty($val_keterangan)) {
                        continue;
                    }
                    
                    $rows_data[] = [
                        $no_excel++,
                        $val_tanggal,
                        $val_jenis,
                        $val_keterangan,
                        $val_nominal
                    ];
                }
            } else {
                throw new Exception("Gagal membaca berkas Excel: " . \Shuchkin\SimpleXLSX::parseError());
            }
        } else {
            throw new Exception("Format ekstensi berkas tidak didukung.");
        }
        
        if (empty($rows_data)) {
            throw new Exception("Tidak ada baris data transaksi yang berhasil ditemukan di dalam dokumen.");
        }
        
        // PROSES TRANSAKSI DATABASE (ATOMIK)
        $pdo->beginTransaction();
        $row_num = 1; // Untuk penanda baris
        $imported_count = 0;
        
        $stmt = $pdo->prepare("INSERT INTO transaksi (user_id, jenis, nominal, keterangan, tanggal) VALUES (:user_id, :jenis, :nominal, :keterangan, :tanggal)");
        
        foreach ($rows_data as $row) {
            $row_num++;
            
            // Baris data harus memiliki minimal 4 kolom (Tanggal, Jenis, Keterangan, Nominal)
            // Dalam format tabel kita: kolom 0=No, 1=Tanggal, 2=Jenis, 3=Keterangan, 4=Nominal
            if (count($row) < 5) {
                continue; // Skip jika baris tidak lengkap
            }
            
            $raw_tanggal    = $row[1];
            $raw_jenis      = $row[2];
            $raw_keterangan = $row[3];
            $raw_nominal    = $row[4];
            
            // 1. Validasi & Format Tanggal
            $tanggal = parse_import_date($raw_tanggal);
            if (!$tanggal) {
                throw new Exception("Format Tanggal '$raw_tanggal' tidak valid. Gunakan format YYYY-MM-DD atau DD/MM/YYYY.");
            }
            
            // 2. Validasi & Normalisasi Jenis
            $jenis = trim($raw_jenis);
            $jenis = ucfirst(strtolower($jenis));
            if (!in_array($jenis, ['Pemasukan', 'Pengeluaran'], true)) {
                throw new Exception("Jenis transaksi '$raw_jenis' tidak valid. Harus berupa 'Pemasukan' atau 'Pengeluaran'.");
            }
            
            // 3. Validasi & Format Nominal
            $nominal = clean_import_nominal($raw_nominal);
            if ($nominal === false || $nominal <= 0) {
                throw new Exception("Nominal '$raw_nominal' tidak valid. Harus berupa angka positif.");
            }
            
            // 4. Validasi Keterangan
            $keterangan = trim($raw_keterangan);
            if (empty($keterangan)) {
                throw new Exception("Keterangan tidak boleh kosong.");
            }
            if (strlen($keterangan) > 255) {
                $keterangan = substr($keterangan, 0, 255);
            }
            
            // Eksekusi insert data ke database
            $stmt->execute([
                'user_id'    => $user_id,
                'jenis'      => $jenis,
                'nominal'    => $nominal,
                'keterangan' => $keterangan,
                'tanggal'    => $tanggal
            ]);
            
            $imported_count++;
        }
        
        if ($imported_count === 0) {
            $pdo->rollBack();
            set_flash_message('danger', 'Tidak ada transaksi baru yang diimpor.');
        } else {
            $pdo->commit();
            set_flash_message('success', "Berhasil mengimpor $imported_count transaksi baru dari dokumen!");
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash_message('danger', 'Gagal mengimpor berkas! ' . $e->getMessage());
    }
    
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: dashboard.php');
    exit;
}
