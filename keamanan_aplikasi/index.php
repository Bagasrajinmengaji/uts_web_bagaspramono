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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700;900&family=Space+Grotesk:wght@700&display=swap');

        :root {
            --m-black: #000000;
            --m-dark-grey: #121212;
            --m-card-bg: #1a1a1a;
            --m-text-primary: #ffffff;
            --m-text-secondary: #bbbbbb;
            
            /* BMW M Tricolor Tokens */
            --m-light-blue: #0066b1;
            --m-dark-blue: #1c69d4;
            --m-red: #e22718;
        }

        body {
            background-color: var(--m-black);
            color: var(--m-text-primary);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        /* Top Navigation Bar */
        .navbar-m {
            background-color: var(--m-black);
            border-bottom: 1px solid #222222;
            padding: 20px 0;
        }
        .navbar-brand-m {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 2px;
            color: var(--m-text-primary) !important;
            text-transform: uppercase;
        }
        .navbar-brand-m span {
            color: var(--m-red);
        }
        .nav-link-m {
            color: var(--m-text-secondary) !important;
            font-weight: 400;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            transition: color 0.2s ease;
        }
        .nav-link-m:hover {
            color: var(--m-text-primary) !important;
        }

        /* M-Stripe Line (Divider) */
        .m-stripe-line {
            height: 4px;
            width: 100%;
            background: linear-gradient(90deg, 
                var(--m-light-blue) 0%, var(--m-light-blue) 33.33%, 
                var(--m-dark-blue) 33.33%, var(--m-dark-blue) 66.66%, 
                var(--m-red) 66.66%, var(--m-red) 100%
            );
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', sans-serif;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: -1px;
        }
        .display-m {
            font-size: 3.5rem;
            line-height: 0.95;
            letter-spacing: -2px;
            font-weight: 900;
        }
        @media (max-width: 768px) {
            .display-m {
                font-size: 2.5rem;
            }
        }
        .lead-m {
            font-weight: 300;
            font-size: 1.1rem;
            line-height: 1.6;
            color: var(--m-text-secondary);
        }

        /* Button: Sharp Silhouette & Machined Look */
        .btn-m-outline {
            border: 2px solid var(--m-text-primary);
            background: transparent;
            color: var(--m-text-primary);
            border-radius: 0px;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 1.5px;
            padding: 14px 32px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-m-outline:hover {
            background-color: var(--m-text-primary);
            color: var(--m-black);
            text-decoration: none;
        }
        .btn-m-outline i {
            font-size: 1.1rem;
        }

        .btn-m-secondary {
            border: 1px solid #333333;
            background-color: var(--m-card-bg);
            color: var(--m-text-primary);
            border-radius: 0px;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 1px;
            padding: 10px 24px;
            transition: all 0.2s ease;
        }
        .btn-m-secondary:hover {
            border-color: var(--m-text-primary);
            color: var(--m-text-primary);
            text-decoration: none;
        }

        /* Hero Section & Glow Backdrops */
        .hero-section {
            padding: 100px 0 120px 0;
            position: relative;
        }
        .glow-backdrop {
            position: absolute;
            top: 15%;
            right: 5%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(28,105,212,0.15) 0%, rgba(226,39,24,0.05) 50%, rgba(0,0,0,0) 100%);
            z-index: 1;
            pointer-events: none;
            filter: blur(40px);
        }

        /* Minimalist High-Fidelity Dashboard Graphic (Right side of Hero) */
        .m-dashboard-graphic {
            background-color: var(--m-card-bg);
            border: 1px solid #222222;
            padding: 30px;
            border-radius: 0px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.8);
            position: relative;
            z-index: 2;
        }
        .m-dashboard-graphic::before {
            content: '';
            position: absolute;
            top: -1px;
            left: -1px;
            width: calc(100% + 2px);
            height: 3px;
            background: linear-gradient(90deg, var(--m-light-blue), var(--m-dark-blue), var(--m-red));
        }
        .graphic-header {
            border-bottom: 1px solid #2a2a2a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .graphic-pill {
            background-color: #262626;
            color: var(--m-text-primary);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 0px;
            display: inline-block;
        }
        .graphic-budget-bar {
            height: 6px;
            background-color: #262626;
            width: 100%;
            margin-top: 8px;
            position: relative;
        }
        .graphic-budget-fill {
            height: 100%;
            width: 82%;
            background-color: var(--m-red);
        }
        .graphic-transaction-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #222222;
            font-size: 0.85rem;
        }
        .graphic-transaction-row:last-child {
            border-bottom: none;
        }

        /* Feature Cards */
        .feature-card-m {
            background-color: var(--m-card-bg);
            border: 1px solid #222222;
            padding: 40px 30px;
            border-radius: 0px;
            height: 100%;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        .feature-card-m:hover {
            border-color: #444444;
            transform: translateY(-5px);
        }
        .feature-card-m::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background-color: var(--m-red);
            transition: width 0.3s ease;
        }
        .feature-card-m:hover::after {
            width: 100%;
        }
        .feature-icon-m {
            font-size: 2rem;
            color: var(--m-text-primary);
            margin-bottom: 25px;
            display: inline-block;
        }

        /* Footer */
        footer {
            background-color: var(--m-black);
            border-top: 1px solid #222222;
            padding: 40px 0;
            margin-top: 80px;
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-m sticky-top">
        <div class="container">
            <a class="navbar-brand-m" href="index.php">
                DOMPETKU<span>.M</span>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="border-radius: 0px;">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link nav-link-m px-3" href="#features">Spesifikasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-m px-3 mr-3" href="#engineering">Filosofi</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item text-white-50 mr-3 font-weight-light" style="font-size: 0.85rem;">
                            <i class="bi bi-cpu me-1"></i> PILOT: <strong class="text-white"><?= escape(
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
                    <div class="graphic-pill mb-3" style="background-color: var(--m-red);">
                        <i class="bi bi-speedometer2 mr-1"></i> M-Performance
                    </div>
                    <h1 class="display-m mb-4">
                        PRESETS FOR<br>FINANCIAL<br>DYNAMICS.
                    </h1>
                    <p class="lead-m mb-5">
                        Menyalurkan arus finansial Anda secara presisi. DompetKu.M bukan sekadar mencatat transaksi, melainkan mengoptimalkan setiap alokasi dana dengan kedisiplinan tingkat tinggi.
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

                <!-- Hero Right: Minimalist Dashboard Graphic -->
                <div class="col-lg-6">
                    <div class="m-dashboard-graphic">
                        <div class="graphic-header d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Sistem Telemetri</div>
                                <div class="text-white-50" style="font-size: 0.68rem;">AKTIF — USER ID #<?= $is_logged_in
                                    ? $user_id
                                    : "0" ?></div>
                            </div>
                            <span class="graphic-pill">Active Mode</span>
                        </div>

                        <!-- Budget Meter -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                                <span class="text-white-50 text-uppercase">Batas Anggaran Bulanan</span>
                                <span class="font-weight-bold text-danger">82.4%</span>
                            </div>
                            <div class="graphic-budget-bar">
                                <div class="graphic-budget-fill"></div>
                            </div>
                            <small class="text-white-50 d-block mt-1" style="font-size: 0.65rem;">WARNING: Limit pengeluaran kritis pada kategori <strong>Operasional</strong>.</small>
                        </div>

                        <!-- Telemetry Log -->
                        <div>
                            <div class="text-uppercase font-weight-bold mb-2 text-white-50" style="font-size: 0.7rem; letter-spacing: 0.5px;">Log Transaksi Terakhir</div>
                            
                            <div class="graphic-transaction-row">
                                <span class="text-white-50">28 Jun — Beli Bahan Bakar</span>
                                <span class="text-danger font-weight-bold">- Rp 350.000</span>
                            </div>
                            <div class="graphic-transaction-row">
                                <span class="text-white-50">27 Jun — Transfer Insentif</span>
                                <span class="text-success font-weight-bold">+ Rp 4.500.000</span>
                            </div>
                            <div class="graphic-transaction-row">
                                <span class="text-white-50">25 Jun — Perawatan Berkala</span>
                                <span class="text-danger font-weight-bold">- Rp 1.200.000</span>
                            </div>
                        </div>
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
                <h2 class="font-weight-bold" style="font-size: 2.2rem; letter-spacing: -1px;">ENGINEERED FOR CONTROL.</h2>
            </div>
            <div class="row">
                
                <!-- Feature 1 -->
                <div class="col-md-4 mb-4 mb-md-0">
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
                <div class="col-md-4 mb-4 mb-md-0">
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
                <div class="col-md-4">
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

            </div>
        </div>
    </section>

    <!-- Philosophy Section -->
    <section id="engineering" class="py-5">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="graphic-pill mb-2">Filosofi Performa</div>
                    <h2 class="font-weight-bold mb-4" style="font-size: 2.2rem; letter-spacing: -1px;">DISIPLIN ADALAH AKSELERASI.</h2>
                    <p class="lead-m font-weight-light mb-4">
                        Mengendalikan keuangan pribadi bukanlah tentang mempersempit ruang gerak Anda. Sebaliknya, catatan keuangan yang akurat memberi Anda visualisasi yang jelas untuk melesat lebih cepat menuju kebebasan finansial yang sejati.
                    </p>
                </div>
                <div class="col-md-6 text-center">
                    <div class="p-5 border border-secondary" style="background-color: var(--m-card-bg); border-radius: 0px;">
                        <i class="bi bi-quote fs-1 text-danger d-block mb-3"></i>
                        <blockquote class="blockquote border-0 p-0 m-0">
                            <p class="mb-3 text-white font-italic" style="font-size: 1.1rem; font-weight: 300;">
                                "Uang adalah pelayan yang baik, namun merupakan majikan yang sangat buruk. Berikan arahan presisi ke mana ia harus pergi."
                            </p>
                            <footer class="blockquote-footer bg-transparent border-0 text-uppercase font-weight-bold text-danger" style="font-size: 0.75rem; letter-spacing: 1px; padding: 0;">
                                Telemetri DompetKu.M
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
            <p class="mb-2 font-weight-bold text-uppercase" style="letter-spacing: 2px; font-size: 1.1rem;">
                DOMPETKU<span>.M</span>
            </p>
            <p class="mb-0 text-muted" style="font-size: 0.75rem; font-weight: 300; letter-spacing: 0.5px;">
                &copy; <?= date(
                    "Y",
                ) ?> DOMPETKU.M. Dibuat dengan presisi industrial dan standar keamanan tinggi. All Rights Reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap 4.6.2 Bundle JS with jQuery and Popper -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
