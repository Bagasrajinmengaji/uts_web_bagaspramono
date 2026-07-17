<?php
// Manggil helper untuk memantau status login
require_once "config/helper.php";

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION["user_id"]);
$user_id = $is_logged_in ? $_SESSION["user_id"] : 0;
$username = $is_logged_in ? $_SESSION["username"] : "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOMPETKU.M — High-Performance Finance Tracker</title>
    <!-- Bootstrap 4.6.2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style Sheet -->
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --m-black: #f8fafc;           /* Light bg */
            --m-dark-grey: #ffffff;       /* Section bg */
            --m-card-bg: #ffffff;         /* White cards */
            --m-text-primary: #1e293b;    /* Dark slate/navy text */
            --m-text-secondary: #64748b;  /* Muted text */
            --m-border: #e2e8f0;
            
            /* DompetKu Colors */
            --m-light-blue: #3b82f6;
            --m-dark-blue: #1d4ed8;
            --m-red: #1d4ed8;             /* Accent */
        }

        [data-theme="dark"] {
            --m-black: #0f172a;           /* Deep Slate 900 */
            --m-dark-grey: #1e293b;       /* Slate 800 */
            --m-card-bg: #1e293b;         /* Slate 800 card */
            --m-text-primary: #f8fafc;    /* Slate 50 text */
            --m-text-secondary: #94a3b8;  /* Slate 400 text */
            --m-border: #334155;          /* Slate 700 border */
            
            --m-light-blue: #38bdf8;       /* Lighter blue accent */
            --m-dark-blue: #3b82f6;
        }

        body {
            background-color: var(--m-black);
            color: var(--m-text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }

        /* Top Navigation Bar */
        .navbar-m {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 0;
        }
        .navbar-brand-m {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--m-text-primary) !important;
            text-transform: none;
            display: inline-flex;
            align-items: center;
        }
        .navbar-brand-m span {
            color: var(--m-light-blue);
        }
        .nav-link-m {
            color: var(--m-text-secondary) !important;
            font-weight: 600;
            text-transform: none;
            font-size: 0.95rem;
            letter-spacing: 0px;
            transition: color 0.2s ease;
        }
        .nav-link-m:hover {
            color: var(--m-dark-blue) !important;
        }

        /* M-Stripe Line (Divider) */
        .m-stripe-line {
            height: 3px;
            width: 100%;
            background: linear-gradient(90deg, var(--m-light-blue) 0%, var(--m-dark-blue) 100%);
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-transform: none;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--m-text-primary);
        }
        .display-m {
            font-size: 3.2rem;
            line-height: 1.1;
            letter-spacing: -1.5px;
            font-weight: 800;
            color: var(--m-text-primary);
        }
        @media (max-width: 768px) {
            .display-m {
                font-size: 2.3rem;
            }
        }
        .lead-m {
            font-weight: 400;
            font-size: 1.05rem;
            line-height: 1.6;
            color: var(--m-text-secondary);
        }

        /* Button: Modern rounded pills to match dashboard styling */
        .btn-m-outline {
            border: 2px solid var(--m-dark-blue);
            background: var(--m-dark-blue);
            color: #ffffff;
            border-radius: 10px;
            text-transform: none;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0px;
            padding: 12px 28px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(29, 78, 216, 0.15);
        }
        .btn-m-outline:hover {
            background-color: #1e40af;
            border-color: #1e40af;
            color: #ffffff;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(29, 78, 216, 0.25);
        }
        .btn-m-outline i {
            font-size: 1.1rem;
        }

        .btn-m-secondary {
            border: 1px solid var(--m-light-blue);
            background-color: transparent;
            color: var(--m-light-blue);
            border-radius: 10px;
            text-transform: none;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0px;
            padding: 8px 22px;
            transition: all 0.2s ease;
        }
        .btn-m-secondary:hover {
            background-color: var(--m-light-blue);
            color: #ffffff;
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* Hero Section & Glow Backdrops */
        .hero-section {
            padding: 80px 0 100px 0;
            position: relative;
        }
        .glow-backdrop {
            position: absolute;
            top: 15%;
            right: 5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59,130,246,0.12) 0%, rgba(29,78,216,0.04) 50%, rgba(0,0,0,0) 100%);
            z-index: 1;
            pointer-events: none;
            filter: blur(40px);
        }

        /* Minimalist High-Fidelity Dashboard Graphic (Right side of Hero) */
        .m-dashboard-graphic {
            background-color: var(--m-card-bg);
            border: 1px solid var(--m-border);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.06);
            position: relative;
            z-index: 2;
        }
        .m-dashboard-graphic::before {
            content: '';
            position: absolute;
            top: -1px;
            left: -1px;
            width: calc(100% + 2px);
            height: 4px;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            background: linear-gradient(90deg, var(--m-light-blue), var(--m-dark-blue));
        }
        .graphic-header {
            border-bottom: 1px solid var(--m-border);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .graphic-pill {
            background-color: #eff6ff;
            color: var(--m-dark-blue);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 8px;
            display: inline-block;
            border: 1px solid #dbeafe;
        }
        .graphic-budget-bar {
            height: 6px;
            background-color: var(--m-border);
            width: 100%;
            margin-top: 8px;
            position: relative;
            border-radius: 3px;
            overflow: hidden;
        }
        .graphic-budget-fill {
            height: 100%;
            width: 82%;
            background-color: #ef4444; /* Keep red warning for critical budget */
            border-radius: 3px;
        }
        .graphic-transaction-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--m-border);
            font-size: 0.85rem;
            color: var(--m-text-secondary);
        }
        .graphic-transaction-row:last-child {
            border-bottom: none;
        }

        /* Feature Cards */
        .feature-card-m {
            background-color: var(--m-card-bg);
            border: 1px solid var(--m-border);
            padding: 40px 30px;
            border-radius: 16px;
            height: 100%;
            transition: all 0.2s ease;
            position: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.01);
        }
        .feature-card-m:hover {
            border-color: var(--m-light-blue);
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(59, 130, 246, 0.08);
        }
        .feature-card-m::after {
            display: none;
        }
        .feature-icon-m {
            font-size: 2.2rem;
            color: var(--m-dark-blue);
            margin-bottom: 20px;
            display: inline-block;
        }

        /* Footer */
        footer {
            background-color: #1e293b;
            border-top: 1px solid #0f172a;
            padding: 50px 0;
            margin-top: 80px;
            color: #ffffff;
        }

        [data-theme="dark"] .navbar-m {
            background-color: rgba(15, 23, 42, 0.9) !important;
            border-bottom-color: var(--m-border) !important;
        }
        [data-theme="dark"] .graphic-pill {
            background-color: rgba(59, 130, 246, 0.15) !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
            color: var(--m-light-blue) !important;
        }
        [data-theme="dark"] footer {
            background-color: #0f172a !important;
            border-top-color: var(--m-border) !important;
        }
        [data-theme="dark"] .text-dark {
            color: var(--m-text-primary) !important;
        }
        [data-theme="dark"] .m-dashboard-graphic {
            box-shadow: 0 20px 40px rgba(0,0,0,0.3) !important;
        }

        /* Resets Light Mode untuk landing page */
        [data-theme="light"] .navbar-m {
            background-color: rgba(255, 255, 255, 0.9) !important;
            border-bottom-color: #e2e8f0 !important;
        }
        [data-theme="light"] .graphic-pill {
            background-color: #eff6ff !important;
            border-color: #bfdbfe !important;
            color: #1d4ed8 !important;
        }
        [data-theme="light"] footer {
            background-color: #1e293b !important;
            border-top-color: #0f172a !important;
            color: #ffffff !important;
        }
        [data-theme="light"] .text-dark {
            color: #1e293b !important;
        }
        [data-theme="light"] .m-dashboard-graphic {
            box-shadow: 0 20px 40px rgba(0,0,0,0.06) !important;
        }
        [data-theme="light"] .feature-card-m {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
        }
        [data-theme="light"] .feature-card-m:hover {
            border-color: #bfdbfe !important;
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-m sticky-top">
        <div class="container">
            <a class="navbar-brand-m" href="index.php">
                <i class="bi bi-wallet2 mr-2 text-primary"></i>Dompet<span>Ku</span>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="border-radius: 10px;">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link nav-link-m px-3" href="#features">Fitur Utama</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-m px-3 mr-3" href="#engineering">Filosofi</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item text-secondary mr-3 font-weight-light" style="font-size: 0.85rem;">
                            <i class="bi bi-cpu me-1 text-primary"></i> PILOT: <strong class="text-dark"><?= escape(
                                $username,
                            ) ?></strong>
                        </li>
                        <li class="nav-item">
                            <a class="btn-m-secondary" href="dashboard.php">Cockpit</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn-m-secondary" href="login.php">Masuk</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- M-Stripe Accent Line -->
    <div class="m-stripe-line"></div>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="glow-backdrop"></div>
        <div class="container">
            <div class="row align-items-center">
                
                <!-- Hero Left: High-Contrast Heading -->
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="graphic-pill mb-3">
                        <i class="bi bi-speedometer2 mr-1"></i> Keamanan & Presisi
                    </div>
                    <h1 class="display-m mb-4">
                        Kendalikan Arus<br>Keuangan Anda<br>Dengan Presisi.
                    </h1>
                    <p class="lead-m mb-5">
                        Menyalurkan arus finansial Anda secara presisi. DompetKu bukan sekadar mencatat transaksi, melainkan mengoptimalkan setiap alokasi dana dengan kedisiplinan tingkat tinggi.
                    </p>
                    
                    <div>
                        <?php if ($is_logged_in): ?>
                            <a href="dashboard.php" class="btn-m-outline">
                                Masuk Cockpit <i class="bi bi-arrow-right-short ml-2"></i>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn-m-outline">
                                Mulai Konfigurasi <i class="bi bi-arrow-right-short ml-2"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Hero Right: Real Dashboard Screenshot Preview -->
                <div class="col-lg-6">
                    <div class="position-relative shadow-lg rounded border border-light" style="border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15); transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        <img src="assets/dashboard_preview.png" alt="Gambaran Dashboard DompetKu" class="img-fluid w-100" style="border-radius: 15px;">
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Feature Cards Section -->
    <section id="features" class="py-5" style="background-color: var(--m-dark-grey);">
        <div class="container py-4">
            <div class="mb-5">
                <div class="graphic-pill mb-2">Spesifikasi Fitur</div>
                <h2 class="font-weight-bold text-dark" style="font-size: 2.2rem; letter-spacing: -1px;">Didesain Untuk Kendali Penuh.</h2>
            </div>
            <div class="row">
                
                <!-- Feature 1 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card-m">
                        <div class="feature-icon-m">
                            <i class="bi bi-sliders"></i>
                        </div>
                        <h4 class="mb-3" style="font-size: 1.25rem;">Kategori Kustom</h4>
                        <p class="text-muted font-weight-light mb-0" style="font-size: 0.9rem;">
                            Klasifikasikan aliran dana Anda secara mandiri. Tambah, edit, dan hapus kategori transaksi khusus yang sesuai dengan karakter berkendara finansial Anda.
                        </p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card-m">
                        <div class="feature-icon-m">
                            <i class="bi bi-speedometer"></i>
                        </div>
                        <h4 class="mb-3" style="font-size: 1.25rem;">Batas Anggaran</h4>
                        <p class="text-muted font-weight-light mb-0" style="font-size: 0.9rem;">
                            Tentukan batas maksimal pengeluaran bulanan per kategori. Sistem akan memberikan indikator visual presisi (kuning & merah) ketika konsumsi mendekati limit.
                        </p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card-m">
                        <div class="feature-icon-m">
                            <i class="bi bi-file-earmark-spreadsheet"></i>
                        </div>
                        <h4 class="mb-3" style="font-size: 1.25rem;">Ekspor XLSX & Impor</h4>
                        <p class="text-muted font-weight-light mb-0" style="font-size: 0.9rem;">
                            Ekspor seluruh riwayat transaksi Anda ke file Excel (.xlsx) murni secara instan, atau impor data dari dokumen dengan parser berkas berkecepatan tinggi.
                        </p>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card-m">
                        <div class="feature-icon-m">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h4 class="mb-3" style="font-size: 1.25rem;">Keamanan Terjamin</h4>
                        <p class="text-muted font-weight-light mb-0" style="font-size: 0.9rem;">
                            Sistem terlindungi dari serangan SQL Injection, XSS, dan kebocoran data sesi, serta dilengkapi hashing kata sandi tingkat tinggi dan otentikasi aman Google SSO.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Philosophy Section -->
    <section id="engineering" class="py-5" style="background-color: var(--m-black);">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="graphic-pill mb-2">Filosofi Performa</div>
                    <h2 class="font-weight-bold mb-4 text-dark" style="font-size: 2.2rem; letter-spacing: -1px;">Disiplin Adalah Akselerasi.</h2>
                    <p class="lead-m font-weight-light mb-4 text-secondary">
                        Mengendalikan keuangan pribadi bukanlah tentang mempersempit ruang gerak Anda. Sebaliknya, catatan keuangan yang akurat memberi Anda visualisasi yang jelas untuk melesat lebih cepat menuju kebebasan finansial yang sejati.
                    </p>
                </div>
                <div class="col-md-6 text-center">
                    <div class="p-5 border border-light shadow-sm" style="background-color: var(--m-card-bg); border-radius: 16px;">
                        <i class="bi bi-quote fs-1 text-danger d-block mb-3"></i>
                        <blockquote class="blockquote border-0 p-0 m-0">
                            <p class="mb-3 text-dark font-italic" style="font-size: 1.1rem; font-weight: 300;">
                                "Uang adalah pelayan yang baik, namun merupakan majikan yang sangat buruk. Berikan arahan presisi ke mana ia harus pergi."
                            </p>
                            <footer class="blockquote-footer bg-transparent border-0 text-uppercase font-weight-bold text-primary" style="font-size: 0.75rem; letter-spacing: 1px; padding: 0;">
                                Telemetri DompetKu
                            </footer>
                        </blockquote>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p class="mb-2 font-weight-bold text-uppercase text-white" style="letter-spacing: 2px; font-size: 1.1rem;">
                DOMPETKU<span class="text-primary">.M</span>
            </p>
            <p class="mb-3 text-white-50" style="font-size: 0.85rem; font-weight: 400; letter-spacing: 0.5px;">
                <i class="bi bi-whatsapp text-success mr-2"></i>Contact Person (WhatsApp): 
                <a href="https://wa.me/6281270143139" target="_blank" rel="noopener noreferrer" class="text-white font-weight-bold" style="text-decoration: none; border-bottom: 1px dashed rgba(255,255,255,0.4); padding-bottom: 1px;">
                    0812-7014-3139
                </a>
            </p>
            <p class="mb-0 text-white-50" style="font-size: 0.75rem; font-weight: 300; letter-spacing: 0.5px;">
                &copy; <?= date(
                    "Y",
                ) ?> DompetKu. Dibuat dengan presisi industrial dan standar keamanan tinggi. All Rights Reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap 4.6.2 Bundle JS with jQuery and Popper -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
