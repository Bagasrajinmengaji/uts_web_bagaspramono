<?php
/**
 * SimplePDF - Sebuah class generator PDF ringan dan mandiri (Pure PHP)
 * Didesain khusus untuk membuat dokumen PDF dasar dengan tabel tanpa dependensi eksternal.
 * Mendukung teks (regular & bold), kotak warna (rectangles), dan garis (lines).
 * Menggunakan sistem koordinat PDF standar (0,0 berada di kiri bawah halaman A4).
 */
class SimplePDF {
    private $content = '';
    private $buffer = '';
    private $offsets = [];
    
    // Dimensi halaman A4
    public $width = 595.28;
    public $height = 841.89;
    
    /**
     * Menambahkan teks ke halaman PDF pada koordinat (x, y)
     * 
     * @param float $x Koordinat X (dari kiri halaman)
     * @param float $y Koordinat Y (dari bawah halaman)
     * @param string $text Teks yang akan ditulis
     * @param int $fontSize Ukuran font
     * @param bool $bold Gunakan font tebal jika true
     * @param array|null $color RGB warna teks (contoh: [0, 0, 0] untuk hitam)
     */
    public function addText($x, $y, $text, $fontSize = 10, $bold = false, $color = null) {
        // Escape karakter khusus PDF dalam tanda kurung
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        
        $font = $bold ? '/F2' : '/F1';
        $op = "BT\n";
        
        if ($color && is_array($color) && count($color) === 3) {
            $op .= sprintf("%.2f %.2f %.2f rg\n", $color[0]/255, $color[1]/255, $color[2]/255);
        } else {
            $op .= "0.00 0.00 0.00 rg\n"; // Default hitam
        }
        
        $op .= "$font $fontSize Tf\n";
        $op .= sprintf("%.2f %.2f Td\n", $x, $y);
        $op .= "($text) Tj\n";
        $op .= "ET\n";
        
        $this->content .= $op;
    }
    
    /**
     * Menambahkan kotak/persegi panjang ke halaman PDF
     * 
     * @param float $x Koordinat X sudut kiri bawah
     * @param float $y Koordinat Y sudut kiri bawah
     * @param float $w Lebar kotak
     * @param float $h Tinggi kotak
     * @param array|null $fillColor RGB warna isi (contoh: [13, 110, 253]), null jika transparan
     * @param array|null $strokeColor RGB warna garis tepi, null jika tanpa garis
     * @param float $lineWidth Tebal garis tepi
     */
    public function addRect($x, $y, $w, $h, $fillColor = null, $strokeColor = null, $lineWidth = 1.0) {
        $op = '';
        
        if ($lineWidth != 1.0) {
            $op .= sprintf("%.2f w\n", $lineWidth);
        }
        
        if ($fillColor && is_array($fillColor) && count($fillColor) === 3) {
            $op .= sprintf("%.2f %.2f %.2f rg\n", $fillColor[0]/255, $fillColor[1]/255, $fillColor[2]/255);
        }
        
        if ($strokeColor && is_array($strokeColor) && count($strokeColor) === 3) {
            $op .= sprintf("%.2f %.2f %.2f RG\n", $strokeColor[0]/255, $strokeColor[1]/255, $strokeColor[2]/255);
        }
        
        $op .= sprintf("%.2f %.2f %.2f %.2f re\n", $x, $y, $w, $h);
        
        if ($fillColor && $strokeColor) {
            $op .= "B\n"; // Fill and stroke
        } elseif ($fillColor) {
            $op .= "f\n"; // Fill only
        } elseif ($strokeColor) {
            $op .= "S\n"; // Stroke only
        }
        
        $this->content .= $op;
    }
    
    /**
     * Menambahkan garis ke halaman PDF
     * 
     * @param float $x1 Titik mulai X
     * @param float $y1 Titik mulai Y
     * @param float $x2 Titik akhir X
     * @param float $y2 Titik akhir Y
     * @param array $color RGB warna garis
     * @param float $width Tebal garis
     */
    public function addLine($x1, $y1, $x2, $y2, $color = [0, 0, 0], $width = 1.0) {
        $op = sprintf("%.2f w\n", $width);
        $op .= sprintf("%.2f %.2f %.2f RG\n", $color[0]/255, $color[1]/255, $color[2]/255);
        $op .= sprintf("%.2f %.2f m\n%.2f %.2f l\nS\n", $x1, $y1, $x2, $y2);
        
        $this->content .= $op;
    }
    
    /**
     * Mengompilasi dan mengembalikan seluruh string biner PDF
     * 
     * @return string
     */
    public function output() {
        $this->buffer = "%PDF-1.4\n";
        
        // Object 1: Catalog
        $this->offsets[1] = strlen($this->buffer);
        $this->buffer .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        // Object 2: Pages Parent
        $this->offsets[2] = strlen($this->buffer);
        $this->buffer .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        
        // Object 3: Page (A4)
        $this->offsets[3] = strlen($this->buffer);
        $this->buffer .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /MediaBox [0 0 595.28 841.89] /Contents 6 0 R >>\nendobj\n";
        
        // Object 4: Font Regular
        $this->offsets[4] = strlen($this->buffer);
        $this->buffer .= "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        // Object 5: Font Bold
        $this->offsets[5] = strlen($this->buffer);
        $this->buffer .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        
        // Object 6: Content Stream
        $this->offsets[6] = strlen($this->buffer);
        $stream_len = strlen($this->content);
        $this->buffer .= "6 0 obj\n<< /Length $stream_len >>\nstream\n" . $this->content . "endstream\nendobj\n";
        
        // Xref Table (Daftar offset byte untuk navigasi pembaca PDF)
        $xref_pos = strlen($this->buffer);
        $this->buffer .= "xref\n0 7\n";
        $this->buffer .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 6; $i++) {
            $this->buffer .= sprintf("%010d 00000 n \n", $this->offsets[$i]);
        }
        
        // Trailer & EOF
        $this->buffer .= "trailer\n<< /Size 7 /Root 1 0 R >>\n";
        $this->buffer .= "startxref\n$xref_pos\n%%EOF";
        
        return $this->buffer;
    }
}
