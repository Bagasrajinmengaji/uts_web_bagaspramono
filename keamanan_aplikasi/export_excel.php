<?php
// Manggil file koneksi, helper, dan Composer autoloader
require_once "config/koneksi.php";
require_once "config/helper.php";
require_once __DIR__ . "/../vendor/autoload.php";

// Pastikan user sudah login
auth_check();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Ambil parameter ekspor khusus satu transaksi atau filter massal
$id = isset($_GET["id"]) ? trim($_GET["id"]) : "";
$jenis_filter = isset($_GET["jenis"]) ? trim($_GET["jenis"]) : "";
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

try {
    if ($id !== "") {
        $query =
            "SELECT t.*, k.nama_kategori, d.nama_dompet FROM transaksi t LEFT JOIN kategori k ON t.id_kategori = k.id_kategori LEFT JOIN dompet d ON t.id_dompet = d.id_dompet WHERE t.id = :id AND t.user_id = :user_id";
        $params = ["id" => $id, "user_id" => $user_id];
    } else {
        $query =
            "SELECT t.*, k.nama_kategori, d.nama_dompet FROM transaksi t LEFT JOIN kategori k ON t.id_kategori = k.id_kategori LEFT JOIN dompet d ON t.id_dompet = d.id_dompet WHERE t.user_id = :user_id";
        $params = ["user_id" => $user_id];

        if ($jenis_filter === "Pemasukan" || $jenis_filter === "Pengeluaran") {
            $query .= " AND t.jenis = :jenis";
            $params["jenis"] = $jenis_filter;
        }

        if ($search !== "") {
            $query .= " AND t.keterangan LIKE :search";
            $params["search"] = "%" . $search . "%";
        }

        $query .= " ORDER BY t.tanggal DESC, t.id DESC";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log($e->getMessage());
    die("Gagal mengambil data untuk export.");
}

// Tentukan judul laporan dan nama file berkas
if ($id !== "") {
    $title_report = "KUITANSI TRANSAKSI — DOMPETKU";
    $filename = "Kuitansi_Transaksi_" . $id . "_" . date("Ymd_His") . ".xlsx";
} else {
    $title_report = "LAPORAN TRANSAKSI — DOMPETKU";
    $filename = "Laporan_Transaksi_DompetKu_" . date("Ymd_His") . ".xlsx";
}

// 1. Inisialisasi Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Laporan Keuangan");

// Nonaktifkan garis grid default jika ingin kustom, tapi Excel biasanya lebih rapi jika grid tetap aktif
$sheet->setShowGridlines(true);

// 2. Styling Judul Laporan
$sheet->setCellValue("A1", $title_report);
$sheet->mergeCells("A1:G1");
$sheet
    ->getStyle("A1")
    ->getFont()
    ->setName("Segoe UI")
    ->setSize(16)
    ->setBold(true);
$sheet
    ->getStyle("A1")
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

$sheet->setCellValue(
    "A2",
    "Aplikasi Pencatatan Keuangan Pribadi yang Aman & Presisi",
);
$sheet->mergeCells("A2:G2");
$sheet
    ->getStyle("A2")
    ->getFont()
    ->setName("Segoe UI")
    ->setSize(10)
    ->setItalic(true)
    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color("555555"));

// Spasi di Row 3 (Dibiarkan kosong)

// 3. Informasi Metadata
$sheet->setCellValue("A4", "Nama Pengguna");
$sheet->setCellValue("B4", ": " . $username);
$sheet->getStyle("A4")->getFont()->setBold(true);

$sheet->setCellValue("A5", "Tanggal Ekspor");
$sheet->setCellValue("B5", ": " . date("d M Y H:i:s"));
$sheet->getStyle("A5")->getFont()->setBold(true);

$metadata_end_row = 5;
if ($jenis_filter !== "") {
    $metadata_end_row++;
    $sheet->setCellValue("A" . $metadata_end_row, "Filter Jenis");
    $sheet->setCellValue("B" . $metadata_end_row, ": " . $jenis_filter);
    $sheet
        ->getStyle("A" . $metadata_end_row)
        ->getFont()
        ->setBold(true);
}
if ($search !== "") {
    $metadata_end_row++;
    $sheet->setCellValue("A" . $metadata_end_row, "Kata Kunci");
    $sheet->setCellValue("B" . $metadata_end_row, ': "' . $search . '"');
    $sheet
        ->getStyle("A" . $metadata_end_row)
        ->getFont()
        ->setBold(true);
}

// Row setelah metadata dibiarkan kosong
$header_row = $metadata_end_row + 2;

// 4. Header Tabel
$headers = ["No", "Tanggal", "Kategori", "Dompet", "Keterangan", "Nominal", "Tipe"];
$sheet->fromArray($headers, null, "A" . $header_row);

// Styling Header Tabel (Bold, Putih di atas Abu-abu Gelap #262626, Rata Tengah)
$headerStyleRange = "A" . $header_row . ":G" . $header_row;
$sheet->getStyle($headerStyleRange)->applyFromArray([
    "font" => [
        "name" => "Segoe UI",
        "bold" => true,
        "size" => 11,
        "color" => ["rgb" => "FFFFFF"],
    ],
    "fill" => [
        "fillType" => Fill::FILL_SOLID,
        "startColor" => ["rgb" => "262626"],
    ],
    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER,
        "vertical" => Alignment::VERTICAL_CENTER,
    ],
    "borders" => [
        "allBorders" => [
            "borderStyle" => Border::BORDER_THIN,
            "color" => ["rgb" => "404040"],
        ],
    ],
]);
$sheet->getRowDimension($header_row)->setRowHeight(26);

// 5. Mengisi Data Transaksi
$start_data_row = $header_row + 1;
$current_row = $start_data_row;

if (empty($transactions)) {
    $sheet->setCellValue("A" . $current_row, "Tidak ada data transaksi.");
    $sheet->mergeCells("A" . $current_row . ":G" . $current_row);
    $sheet
        ->getStyle("A" . $current_row)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet
        ->getStyle("A" . $current_row)
        ->getFont()
        ->setItalic(true);

    // Border tipis untuk baris kosong
    $sheet
        ->getStyle("A" . $current_row . ":G" . $current_row)
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN);
    $current_row++;
} else {
    $no = 1;
    foreach ($transactions as $row) {
        $date_formatted = date("Y-m-d", strtotime($row["tanggal"]));
        $kategori_name = $row["nama_kategori"]
            ? $row["nama_kategori"]
            : "Tanpa Kategori";
        $dompet_name = $row["nama_dompet"]
            ? $row["nama_dompet"]
            : "Tanpa Dompet";

        $sheet->setCellValue("A" . $current_row, $no);
        $sheet->setCellValue("B" . $current_row, $date_formatted);
        $sheet->setCellValue("C" . $current_row, $kategori_name);
        $sheet->setCellValue("D" . $current_row, $dompet_name);
        $sheet->setCellValue("E" . $current_row, $row["keterangan"]);
        $sheet->setCellValue("F" . $current_row, (float) $row["nominal"]);
        $sheet->setCellValue("G" . $current_row, $row["jenis"]);

        // Zebra Striping (Baris genap abu-abu sangat tipis #F9F9F9)
        if ($no % 2 === 0) {
            $sheet
                ->getStyle("A" . $current_row . ":G" . $current_row)
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB("F9F9F9");
        }

        // Alignment per kolom
        $sheet
            ->getStyle("A" . $current_row)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER); // No
        $sheet
            ->getStyle("B" . $current_row)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tanggal
        $sheet
            ->getStyle("C" . $current_row)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER); // Kategori
        $sheet
            ->getStyle("D" . $current_row)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER); // Dompet
        $sheet
            ->getStyle("E" . $current_row)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT); // Keterangan
        $sheet
            ->getStyle("F" . $current_row)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Nominal
        $sheet
            ->getStyle("G" . $current_row)
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tipe

        // Format Mata Uang Rupiah untuk Nominal
        $sheet
            ->getStyle("F" . $current_row)
            ->getNumberFormat()
            ->setFormatCode(
                '_-* "Rp" #,##0_-;\-* "Rp" #,##0_-;_-* "-"_-;_-@_-',
            );

        // Border tipis untuk sel data
        $sheet
            ->getStyle("A" . $current_row . ":G" . $current_row)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB("D3D3D3");

        $no++;
        $current_row++;
    }

    $last_data_row = $current_row - 1;

    // Spasi 1 baris kosong sebelum total
    $current_row++;

    // 6. Baris Total Pemasukan
    $sheet->setCellValue("A" . $current_row, "TOTAL PEMASUKAN");
    $sheet->mergeCells("A" . $current_row . ":E" . $current_row);
    $sheet->setCellValue(
        "F" . $current_row,
        "=SUMIF(G" .
            $start_data_row .
            ":G" .
            $last_data_row .
            ', "Pemasukan", F' .
            $start_data_row .
            ":F" .
            $last_data_row .
            ")",
    );

    // Styling Total Pemasukan (Abu-abu Muda, Bold, Border Atas-Bawah)
    $pemasukan_range = "A" . $current_row . ":G" . $current_row;
    $sheet->getStyle($pemasukan_range)->applyFromArray([
        "font" => ["bold" => true, "name" => "Segoe UI"],
        "fill" => [
            "fillType" => Fill::FILL_SOLID,
            "startColor" => ["rgb" => "F2F2F2"],
        ],
        "alignment" => [
            "vertical" => Alignment::VERTICAL_CENTER,
        ],
        "borders" => [
            "top" => [
                "borderStyle" => Border::BORDER_THIN,
                "color" => ["rgb" => "A0A0A0"],
            ],
            "bottom" => [
                "borderStyle" => Border::BORDER_THIN,
                "color" => ["rgb" => "A0A0A0"],
            ],
        ],
    ]);
    $sheet
        ->getStyle("A" . $current_row)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet
        ->getStyle("F" . $current_row)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet
        ->getStyle("F" . $current_row)
        ->getNumberFormat()
        ->setFormatCode('_-* "Rp" #,##0_-;\-* "Rp" #,##0_-;_-* "-"_-;_-@_-');
    $row_pemasukan = $current_row;
    $current_row++;

    // 7. Baris Total Pengeluaran
    $sheet->setCellValue("A" . $current_row, "TOTAL PENGELUARAN");
    $sheet->mergeCells("A" . $current_row . ":E" . $current_row);
    $sheet->setCellValue(
        "F" . $current_row,
        "=SUMIF(G" .
            $start_data_row .
            ":G" .
            $last_data_row .
            ', "Pengeluaran", F' .
            $start_data_row .
            ":F" .
            $last_data_row .
            ")",
    );

    // Styling Total Pengeluaran (Abu-abu Muda, Bold, Border Atas-Bawah)
    $pengeluaran_range = "A" . $current_row . ":G" . $current_row;
    $sheet->getStyle($pengeluaran_range)->applyFromArray([
        "font" => ["bold" => true, "name" => "Segoe UI"],
        "fill" => [
            "fillType" => Fill::FILL_SOLID,
            "startColor" => ["rgb" => "F2F2F2"],
        ],
        "alignment" => [
            "vertical" => Alignment::VERTICAL_CENTER,
        ],
        "borders" => [
            "top" => [
                "borderStyle" => Border::BORDER_THIN,
                "color" => ["rgb" => "A0A0A0"],
            ],
            "bottom" => [
                "borderStyle" => Border::BORDER_THIN,
                "color" => ["rgb" => "A0A0A0"],
            ],
        ],
    ]);
    $sheet
        ->getStyle("A" . $current_row)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet
        ->getStyle("F" . $current_row)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet
        ->getStyle("F" . $current_row)
        ->getNumberFormat()
        ->setFormatCode('_-* "Rp" #,##0_-;\-* "Rp" #,##0_-;_-* "-"_-;_-@_-');
    $row_pengeluaran = $current_row;
    $current_row++;

    // 8. Baris Saldo Akhir
    $sheet->setCellValue("A" . $current_row, "SALDO AKHIR");
    $sheet->mergeCells("A" . $current_row . ":E" . $current_row);
    $sheet->setCellValue(
        "F" . $current_row,
        "=F" . $row_pemasukan . "-F" . $row_pengeluaran,
    );

    // Styling Saldo Akhir (Biru Muda Lembut #D9E1F2, Bold, Border Atas-Bawah Akuntansi Double)
    $saldo_range = "A" . $current_row . ":G" . $current_row;
    $sheet->getStyle($saldo_range)->applyFromArray([
        "font" => [
            "bold" => true,
            "name" => "Segoe UI",
            "color" => ["rgb" => "000000"],
        ],
        "fill" => [
            "fillType" => Fill::FILL_SOLID,
            "startColor" => ["rgb" => "D9E1F2"],
        ],
        "alignment" => [
            "vertical" => Alignment::VERTICAL_CENTER,
        ],
        "borders" => [
            "top" => [
                "borderStyle" => Border::BORDER_THIN,
                "color" => ["rgb" => "5B9BD5"],
            ],
            "bottom" => [
                "borderStyle" => Border::BORDER_DOUBLE,
                "color" => ["rgb" => "000000"],
            ],
        ],
    ]);
    $sheet
        ->getStyle("A" . $current_row)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet
        ->getStyle("F" . $current_row)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet
        ->getStyle("F" . $current_row)
        ->getNumberFormat()
        ->setFormatCode('_-* "Rp" #,##0_-;\-* "Rp" #,##0_-;_-* "-"_-;_-@_-');
}

// 9. Auto-Fit Lebar Kolom untuk Semua Kolom
foreach (range("A", "G") as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// 10. Pengamanan & Pengiriman Berkas Langsung
// Bersihkan output buffer untuk mencegah kebocoran karakter HTML
if (ob_get_level()) {
    ob_end_clean();
}

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header("Cache-Control: max-age=0");
// Jika ada IE 9, aktifkan berikut:
header("Cache-Control: max-age=1");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit();
