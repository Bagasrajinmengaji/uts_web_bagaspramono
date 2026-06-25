<?php
// Manggil helper untuk memantau status login
require_once 'config/helper.php';

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DompetKu - Catat & Rancang Finansialmu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style Font & Theme -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            /* Warna latar putih lembut yang tidak terlalu cerah (soft off-white) */
            background-color: #f1f5f9;
            color: #1e293b;
        }
        
        /* Navigasi */
        .navbar-custom {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* Hero Section dengan gradasi biru muda lembut */
        .hero-section {
            padding: 90px 0 70px 0;
            background: linear-gradient(180deg, #e0f2fe 0%, #f1f5f9 100%);
        }
        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -1px;
            color: #0f172a;
        }
        .hero-subtitle {
            font-size: 1.1rem;
            color: #475569;
            line-height: 1.6;
        }
        .illustration-img {
            max-width: 100%;
            height: auto;
            filter: drop-shadow(0 15px 25px rgba(56, 189, 248, 0.15));
            animation: floatAnimation 6s ease-in-out infinite;
        }
        
        /* Efek melayang pada gambar ilustrasi */
        @keyframes floatAnimation {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
            100% { transform: translateY(0px); }
        }

        /* Quotes Card */
        .quote-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 5px solid #38bdf8; /* Accent Biru Muda */
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            height: 100%;
        }
        .quote-text {
            font-size: 1rem;
            font-style: italic;
            color: #334155;
            line-height: 1.5;
        }
        .quote-author {
            font-weight: 700;
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Fitur List */
        .feature-icon {
            width: 48px;
            height: 48px;
            background-color: #e0f2fe; /* Biru Muda lembut */
            color: #0284c7;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 16px;
        }

        /* Custom Button */
        .btn-custom-main {
            padding: 14px 28px;
            font-size: 1.05rem;
            border-radius: 12px;
            border: none;
            font-weight: 700;
            background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%); /* Biru muda */
            color: #ffffff;
            transition: all 0.3s ease;
        }
        .btn-custom-main:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(56, 189, 248, 0.3);
            color: #ffffff;
        }
        
        .dropdown-menu-custom {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 8px;
            min-width: 220px;
        }
        .dropdown-item-custom {
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .dropdown-item-custom:hover {
            background-color: #f1f5f9;
        }
    </style>
</head>
<body>

    <!-- Navigasi -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-custom fixed-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-primary" href="index.php">
                <i class="bi bi-wallet2 me-2"></i> DompetKu
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center gap-2 mt-2 mt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link text-dark font-bold px-3" href="#fitur">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-dark font-bold px-3" href="#manfaat">Manfaat</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item text-muted px-2">
                            <i class="bi bi-person-circle"></i> Halo, <?= escape($username); ?>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm px-3" href="dashboard.php">Dashboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-light border btn-sm px-3 font-bold" href="login.php">Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm px-3 font-bold" href="register.php">Daftar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Teks Utama -->
                <div class="col-lg-6 text-center text-lg-start">
                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill font-bold mb-3" style="font-size: 0.85rem;">
                        <i class="bi bi-shield-check me-1"></i> Aman, Praktis & Terpercaya
                    </span>
                    <h1 class="hero-title mb-3">
                        Kendalikan Uangmu,<br>Rancang Masa Depanmu
                    </h1>
                    <p class="hero-subtitle mb-4">
                        Mencatat keuangan bukan sekadar membatasi pengeluaran Anda. Ini adalah tentang memberi tahu uang Anda ke mana harus mengalir, alih-alih bertanya-tanya ke mana ia menghilang secara misterius.
                    </p>
                    
                    <!-- Tombol Aksi Utama (Ayo Catat Keuangan Mu) -->
                    <div class="d-inline-block">
                        <?php if ($is_logged_in): ?>
                            <a href="dashboard.php" class="btn btn-custom-main px-4 py-3 shadow-sm d-flex align-items-center">
                                <i class="bi bi-grid-1x2-fill me-2"></i> Ayo Catat Keuangan Mu (Ke Dashboard)
                            </a>
                        <?php else: ?>
                            <div class="btn-group shadow">
                                <button type="button" class="btn btn-custom-main px-4 py-3 font-bold dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-wallet2 me-2"></i> Ayo Catat Keuangan Mu
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom shadow-lg mt-2">
                                    <li>
                                        <a class="dropdown-item dropdown-item-custom text-primary" href="login.php">
                                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In (Masuk Akun)
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item dropdown-item-custom text-success" href="register.php">
                                            <i class="bi bi-person-plus me-2"></i> Register (Buat Akun)
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Gambar Ilustrasi Uang (Asset Hasil Generate) -->
                <div class="col-lg-6 text-center">
                    <img src="assets/landing_money_illustration.png" alt="Ilustrasi Keuangan DompetKu" class="illustration-img">
                </div>
            </div>
        </div>
    </section>

    <!-- Fitur Unggulan -->
    <section id="fitur" class="py-5 bg-white border-top border-bottom">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="font-bold mb-2">Mengapa Memilih DompetKu?</h2>
                <p class="text-muted col-lg-6 mx-auto">Kami menyediakan alat bantu pencatatan finansial terlengkap dengan tingkat keamanan tinggi untuk mahasiswa maupun profesional.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card p-4 h-100 border border-light shadow-sm">
                        <div class="feature-icon">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h5 class="font-bold mb-2">Keamanan Berlapis</h5>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Dilindungi dengan pengamanan sesi cookie yang ketat, validasi input berlapis, serta kueri PDO terproteksi dari ancaman SQL Injection.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100 border border-light shadow-sm">
                        <div class="feature-icon">
                            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                        </div>
                        <h5 class="font-bold mb-2">Ekspor Multi-Format</h5>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Unduh kuitansi per transaksi atau laporan keuangan terfilter secara massal ke dalam berkas Excel (XLS), Word (DOCX), atau PDF dengan satu kali klik.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100 border border-light shadow-sm">
                        <div class="feature-icon">
                            <i class="bi bi-cloud-arrow-up-fill"></i>
                        </div>
                        <h5 class="font-bold mb-2">Impor Cerdas Terpadu</h5>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Unggah berkas CSV, Word, atau PDF untuk memulihkan atau menyalin data transaksi secara instan dengan parser biner kustom.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kata-kata Penting Mencatat Keuangan (Manfaat) -->
    <section id="manfaat" class="py-5" style="background-color: #f8fafc;">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="font-bold mb-2">Pentingnya Mencatat Keuangan</h2>
                <p class="text-muted col-lg-6 mx-auto">Disiplin finansial bukanlah pembatasan, melainkan kebebasan sesungguhnya untuk mengalokasikan masa depan.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="quote-card">
                        <p class="quote-text mb-4">
                            "Mencatat keuangan harian adalah kunci utama mendeteksi kebocoran finansial sekecil apapun. Tanpa catatan, rupiah kita akan menguap begitu saja tanpa kejelasan."
                        </p>
                        <hr class="border-slate my-3">
                        <div class="quote-author">Deteksi Kebocoran Finansial</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="quote-card" style="border-left-color: #0284c7;">
                        <p class="quote-text mb-4">
                            "Ketika kita rajin mencatat pemasukan dan pengeluaran, kita sedang menanam kebiasaan disiplin. Disiplin finansial hari ini adalah jaminan ketenangan di masa tua nanti."
                        </p>
                        <hr class="border-slate my-3">
                        <div class="quote-author">Investasi Kedisiplinan</div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="quote-card" style="border-left-color: #0ea5e9;">
                        <p class="quote-text mb-4">
                            "Rencana besar tanpa evaluasi hanyalah angan-angan. Evaluasi terbaik untuk rencana keuangan Anda berasal dari data catatan pengeluaran riil yang tercatat rapi."
                        </p>
                        <hr class="border-slate my-3">
                        <div class="quote-author">Evaluasi Target Nyata</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center py-4 bg-white border-top">
        <div class="container">
            <p class="mb-1 font-bold text-primary"><i class="bi bi-wallet2 me-1"></i> DompetKu</p>
            <p class="mb-0 text-muted" style="font-size: 0.85rem;">&copy; <?= date('Y'); ?> DompetKu. Dibuat untuk Keamanan Finansial dan Kemudahan Catatan Keuangan Pribadi Anda.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
