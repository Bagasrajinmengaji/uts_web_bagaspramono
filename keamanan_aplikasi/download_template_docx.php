<?php
// Manggil helper
require_once 'config/helper.php';
auth_check();

// Set header agar diunduh sebagai dokumen Word (.doc)
$filename = "Template_Import_DompetKu.doc";
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

?>
<!DOCTYPE html>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <meta charset="UTF-8">
    <title>Template Import Transaksi - DompetKu</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.3;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #198754;
            text-align: center;
            margin-bottom: 3px;
        }
        .subtitle {
            font-size: 10pt;
            color: #666666;
            text-align: center;
            margin-bottom: 25px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 10pt;
        }
        .info-table td {
            padding: 3px 0;
        }
        .data-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
            font-size: 10pt;
        }
        .data-table th {
            background-color: #198754;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border: 1px solid #cccccc;
        }
        .data-table td {
            padding: 8px;
            border: 1px solid #cccccc;
            vertical-align: middle;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-success {
            color: #198754;
        }
        .text-danger {
            color: #dc3545;
        }
        .note-box {
            background-color: #f8f9fa;
            border: 1px solid #dddddd;
            padding: 12px;
            margin-top: 25px;
            font-size: 9.5pt;
        }
    </style>
</head>
<body>

    <div class="title">TEMPLATE IMPORT TRANSAKSI - DOMPETKU</div>
    <div class="subtitle font-bold">Gunakan dokumen ini untuk mengisi data transaksi keuangan Anda secara massal</div>

    <table class="info-table" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="3"><strong>Instruksi Pengisian:</strong></td>
        </tr>
        <tr>
            <td width="30" class="text-center">1.</td>
            <td colspan="2">Tambahkan data transaksi Anda pada baris tabel di bawah. Jangan mengubah urutan kolom.</td>
        </tr>
        <tr>
            <td class="text-center">2.</td>
            <td colspan="2">Setelah selesai mengisi data, klik <strong>Save As</strong> di Word, lalu pilih jenis file <strong>Word Document (*.docx)</strong>.</td>
        </tr>
        <tr>
            <td class="text-center">3.</td>
            <td colspan="2">Unggah berkas .docx tersebut ke dalam aplikasi DompetKu untuk diimpor.</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="40" style="background-color: #198754; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">No</th>
                <th width="120" style="background-color: #198754; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Tanggal</th>
                <th width="100" style="background-color: #198754; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Jenis</th>
                <th width="280" style="background-color: #198754; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Keterangan</th>
                <th width="120" style="background-color: #198754; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center" style="border: 1px solid #cccccc;">1</td>
                <td class="text-center" style="border: 1px solid #cccccc;">2026-06-25</td>
                <td class="text-center" style="border: 1px solid #cccccc; color: #198754; font-weight: bold;">Pemasukan</td>
                <td style="border: 1px solid #cccccc;">Gaji Pekerjaan Sampingan</td>
                <td class="text-right font-bold text-success" style="border: 1px solid #cccccc;">750000</td>
            </tr>
            <tr class="even" style="background-color: #f8f9fa;">
                <td class="text-center" style="border: 1px solid #cccccc;">2</td>
                <td class="text-center" style="border: 1px solid #cccccc;">2026-06-25</td>
                <td class="text-center" style="border: 1px solid #cccccc; color: #dc3545; font-weight: bold;">Pengeluaran</td>
                <td style="border: 1px solid #cccccc;">Belanja Bulanan Swalayan</td>
                <td class="text-right font-bold text-danger" style="border: 1px solid #cccccc;">350000</td>
            </tr>
            <!-- Silakan tambahkan baris di bawah ini -->
        </tbody>
    </table>

    <div class="note-box">
        <strong>Aturan Pengisian Tabel:</strong><br>
        1. <strong>Tanggal:</strong> Gunakan format YYYY-MM-DD (contoh: 2026-06-25) atau format Word standar DD/MM/YYYY.<br>
        2. <strong>Jenis:</strong> Harus ditulis tepat "Pemasukan" atau "Pengeluaran".<br>
        3. <strong>Nominal:</strong> Isi dengan angka murni (angka desimal atau ribuan murni tanpa Rp atau simbol lainnya).<br>
        4. <strong>Keterangan:</strong> Keterangan transaksi tidak boleh kosong (maksimal 255 karakter).
    </div>

</body>
</html>
