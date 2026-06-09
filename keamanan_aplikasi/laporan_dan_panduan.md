# LAPORAN PROYEK & PANDUAN KEAMANAN APLIKASI
## DompetKu - Aplikasi Pencatatan Keuangan Sederhana Aman

Laporan ini disusun sebagai pemenuhan tugas perkuliahan, menjelaskan fitur, struktur folder, penjelasan file, serta penerapan prinsip keamanan pada aplikasi **DompetKu**.

---

## 1. Struktur Folder Proyek

Aplikasi dirancang secara modular dan bersih agar mudah dipelajari oleh mahasiswa pemula namun tetap aman. Berikut adalah bagan struktur foldernya:

```text
cybersecurity/
│
├── assets/
│   └── css/
│       └── style.css            # Desain kustom bertema biru-putih & layout
│
├── config/
│   ├── koneksi.php              # Koneksi database aman menggunakan PDO
│   └── helper.php               # Fungsi keamanan (XSS, auth guard, flash message)
│
├── database.sql                 # Script inisialisasi database & tabel MySQL
│
├── register.php                 # Halaman pendaftaran akun baru
├── login.php                    # Halaman masuk akun
├── logout.php                   # Script keluar sistem dan penghapusan sesi
│
├── dashboard.php                # Halaman utama ringkasan finansial & tabel data
├── tambah_transaksi.php         # Form tambah pemasukan / pengeluaran
├── edit_transaksi.php           # Form pembaruan transaksi (disertai auth-lock)
└── hapus_transaksi.php          # Proses penghapusan transaksi (metode POST)
```

---

## 2. Fitur Utama Aplikasi

1. **Autentikasi Pengguna**: Memungkinkan registrasi pengguna baru dan login aman untuk masuk ke dashboard pribadi.
2. **Dashboard Finansial**: Menghitung secara otomatis *Total Pemasukan*, *Total Pengeluaran*, dan *Saldo Saat Ini* (selisih keduanya) milik pengguna yang sedang login.
3. **Manajemen Transaksi (CRUD)**:
   - **Tambah**: Form input transaksi dengan tipe Pemasukan/Pengeluaran, nominal angka, tanggal, dan keterangan.
   - **Lihat**: Tabel histori transaksi dengan fitur pencarian keterangan dan filter jenis transaksi.
   - **Edit**: Formulir pengeditan data transaksi milik pribadi.
   - **Hapus**: Penghapusan transaksi secara aman (mencegah klik tak sengaja/serangan CSRF sederhana).
4. **Isolasi Data Pengguna**: Setiap pengguna hanya dapat melihat, menambah, mengubah, dan menghapus data miliknya sendiri. Data antar pengguna diisolasi penuh di tingkat database melalui kueri bersyarat `user_id`.

---

## 3. Penjelasan Fungsi Setiap File

| Nama File | Lokasi | Deskripsi / Fungsi |
| :--- | :--- | :--- |
| `database.sql` | Root | Menyimpan kueri SQL untuk inisialisasi database `dompetku`, pembuatan tabel `users` dan `transaksi`, serta relasi *Foreign Key* dengan skema *Cascade*. |
| `koneksi.php` | `config/` | Mengatur koneksi PHP ke MySQL server menggunakan library **PDO**. Konfigurasi diatur agar mematikan emulasi prepared statement untuk mencegah celah keamanan SQLi. |
| `helper.php` | `config/` | Menyimpan modul fungsi utilitas global seperti inisialisasi session aman, fungsi penangkal XSS `escape()`, pengecekan login (`auth_check` / `guest_check`), format mata uang Rupiah, dan notifikasi flash alert. |
| `style.css` | `assets/css/` | Lembar gaya CSS custom pendukung Bootstrap 5. Mengatur font modern (Plus Jakarta Sans), palet warna biru-putih dominan, efek bayangan card, form kustom, dan responsivitas tabel. |
| `register.php` | Root | Menampilkan form registrasi pengguna dan mengolah input pendaftaran. Melakukan validasi karakter username, format email, kecocokan & kekuatan password, pengecekan duplikasi, dan enkripsi hash password sebelum disimpan. |
| `login.php` | Root | Menampilkan form login. Memverifikasi input pengguna terhadap hash password database menggunakan `password_verify` dan merestrukturisasi session ID pasca login untuk keamanan. |
| `logout.php` | Root | Menghentikan sesi aktif pengguna dengan cara menghapus data array `$_SESSION`, mematikan session cookie di browser, dan menghancurkan sesi di memori server. |
| `dashboard.php` | Root | Halaman beranda utama terproteksi login yang menampilkan kartu total pemasukan, pengeluaran, sisa saldo, serta tabel daftar transaksi lengkap dengan filter pencarian. |
| `tambah_transaksi.php` | Root | Menyediakan form dan mengolah proses penyimpanan transaksi baru. Memastikan input nominal bernilai positif dan tanggal sesuai format kueri. |
| `edit_transaksi.php` | Root | Memuat data transaksi yang dipilih berdasarkan ID, memvalidasi hak kepemilikan data, menampilkan form edit, dan memproses perubahan data tersebut. |
| `hapus_transaksi.php` | Root | Memproses perintah hapus transaksi. File ini tidak memiliki tampilan visual dan mewajibkan request berupa metode POST demi alasan keamanan data. |

---

## 4. Implementasi Prinsip Keamanan Aplikasi

Berikut adalah penjelasan teknis bagaimana keenam prinsip keamanan wajib (ditambah otorisasi data) diterapkan pada DompetKu:

### A. Authentication (Autentikasi)
*   **Implementasi**: Autentikasi didukung penuh menggunakan fungsi Session bawaan PHP.
*   **Detail Kode**: Pada file `helper.php`, session dimulai dengan `session_start()`. Di setiap halaman sensitif seperti `dashboard.php`, `tambah_transaksi.php`, dan `edit_transaksi.php`, diletakkan fungsi guard `auth_check()`. Fungsi ini akan memeriksa eksistensi variabel sesi `$_SESSION['user_id']`. Jika tidak ada, pengguna akan dilempar langsung ke halaman `login.php`.
*   **Logout**: File `logout.php` menghapus cookie sesi di sisi client dan menghapus sesi di server untuk mencegah serangan *session replay*.

### B. Password Hashing (Keamanan Kredensial)
*   **Implementasi**: Sandi pengguna dilarang keras disimpan dalam bentuk teks polos (*plaintext*).
*   **Detail Kode**:
    - Saat registrasi (`register.php`), password dienkripsi menggunakan fungsi bawaan PHP `password_hash($password, PASSWORD_DEFAULT)`. Algoritma default ini otomatis menggunakan algoritma hashing modern yang kuat (seperti bcrypt) dilengkapi dengan nilai garam (*salt*) acak secara internal.
    - Saat login (`login.php`), password input dicocokkan dengan hash database menggunakan fungsi bawaan `password_verify($password, $user['password'])`. Fungsi ini sangat tahan terhadap serangan *timing attack*.

### C. SQL Injection (SQLi) Prevention
*   **Implementasi**: Seluruh query yang berinteraksi dengan database tidak menggunakan teknik penggabungan string (*string concatenation*), melainkan menggunakan **PDO Prepared Statements**.
*   **Detail Kode**:
    - Penulisan kueri menggunakan placeholder seperti `:username` atau `:user_id`.
    - Nilai input dimasukkan menggunakan binding parameter:
      ```php
      $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :identity");
      $stmt->execute(['identity' => $identity]);
      ```
    - Selain itu, pada `koneksi.php`, diatur `PDO::ATTR_EMULATE_PREPARES => false`, memaksa MySQL driver untuk menangani parameterisasi secara native sehingga data input tidak akan pernah dieksekusi sebagai perintah SQL baru oleh compiler database.

### D. Session Management (Manajemen Sesi Aman)
*   **Implementasi**: Mencegah eksploitasi sesi seperti *Session Fixation* dan pencurian sesi.
*   **Detail Kode**:
    - Di `login.php`, segera setelah kredensial divalidasi dengan sukses, dipanggil fungsi `session_regenerate_id(true)`. Langkah ini menghapus ID sesi lama dan mengeluarkan ID sesi baru yang acak, mematahkan upaya penyerang yang mencoba menyisipkan ID sesi tertentu (*Session Fixation*).
    - Di `helper.php`, konfigurasi session diatur menggunakan opsi `'cookie_httponly' => true` dan `'cookie_use_only_cookies' => true`. Atribut *HttpOnly* menginstruksikan browser untuk tidak mengizinkan script client-side (seperti JavaScript/XSS) membaca cookie sesi, mengeliminasi risiko pembajakan token login.

### E. Cross-Site Scripting (XSS) Prevention
*   **Implementasi**: Mencegah eksekusi script HTML atau JavaScript berbahaya yang disisipkan peretas pada kolom teks (seperti kolom keterangan transaksi).
*   **Detail Kode**:
    - Dibuat fungsi pembungkus khusus di `helper.php` bernama `escape()`, yang mengembalikan nilai `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`.
    - Di setiap output dinamis pada dashboard, register, login, maupun edit transaksi, variabel dicetak menggunakan helper ini:
      ```php
      <?= escape($row['keterangan']); ?>
      ```
      Ini akan mengubah karakter khusus seperti `<` menjadi `&lt;` dan `>` menjadi `&gt;` sehingga browser merendernya sebagai teks murni, bukan instruksi program yang berjalan.

### F. Input Validation (Validasi Input)
*   **Implementasi**: Memastikan format data yang dikirimkan oleh pengguna aman dan sesuai dengan kebutuhan sistem sebelum diproses.
*   **Detail Kode**:
    - **Registrasi**: Memvalidasi kesesuaian format alamat email (`FILTER_VALIDATE_EMAIL`), panjang minimal password (8 karakter), serta pembatasan karakter username (hanya alfanumerik dan underscore menggunakan regex `/^[a-zA-Z0-9_]{3,50}$/`) untuk mencegah eksploitasi sistem berkas atau penamaan aneh.
    - **Transaksi**: Kolom nominal wajib lolos pengecekan numerik positif `is_numeric($nominal) && floatval($nominal) > 0` untuk menghindari bug logika keuangan (seperti menginput nominal negatif untuk memanipulasi perhitungan total saldo). Format tanggal divalidasi ke pola YYYY-MM-DD menggunakan regex.

### G. Otorisasi Data (Broken Object Level Authorization - BOLA / ID Tampering Protection)
*   **Implementasi**: Menjamin agar pengguna tidak bisa mengotak-atik transaksi milik pengguna lain hanya dengan menebak/mengubah parameter ID di URL (misal: mengubah `id=5` menjadi `id=6` di baris alamat browser).
*   **Detail Kode**:
    - Di `edit_transaksi.php` dan `hapus_transaksi.php`, pencarian dan penghapusan data selalu mengunci parameter `user_id` yang ditarik dari sesi aktif, bukan sekadar parameter ID transaksi saja:
      ```php
      $stmt = $pdo->prepare("SELECT * FROM transaksi WHERE id = :id AND user_id = :user_id");
      ```
      Jika pengguna A mencoba membuka halaman edit untuk ID transaksi milik pengguna B, sistem akan mendeteksi baris data kosong (tidak ditemukan) dan segera melempar kembali pengguna ke dashboard dengan pesan error "hak akses ditolak".
    - Penghapusan transaksi di `hapus_transaksi.php` wajib menggunakan metode POST, sehingga mencegah eksploitasi jika ada bot perayap browser (web crawler) yang mengindeks link GET, sekaligus mempersulit serangan CSRF.
