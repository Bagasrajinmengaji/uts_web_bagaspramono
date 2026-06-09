<?php
/**
 * NusaGrid GPU Portal - Integrated Dashboard
 * Redesigned with Bootstrap 5
 */
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NusaGrid GPU Portal - Console</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #07040e;
            --bg-sidebar: #0f0a20;
            --bg-card: rgba(26, 15, 52, 0.45);
            --border-neon: rgba(157, 78, 221, 0.25);
            --border-neon-hover: rgba(0, 240, 255, 0.5);
            
            --accent-purple: #9d4edd;
            --accent-cyan: #00f0ff;
            --accent-pink: #ff007f;
            --accent-green: #39ff14;
            --accent-yellow: #ffb703;
            
            --text-main: #f3efff;
            --text-muted: #9f96b9;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-body);
            background-image: linear-gradient(135deg, #0a0515 0%, #160c30 100%);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #090514;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--accent-purple);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent-cyan);
        }

        /* Sidebar Styling */
        .sidebar-container {
            min-height: 100vh;
            background-color: var(--bg-sidebar);
            border-right: 1px solid rgba(157, 78, 221, 0.15);
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
        }

        .main-content {
            margin-left: 16.66667%; /* col-lg-2 width */
            padding: 2.5rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 991.98px) {
            .sidebar-container {
                position: static;
                min-height: auto;
                width: 100%;
                padding: 1rem 1.5rem;
            }
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
        }

        .logo-icon {
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #fff;
            box-shadow: 0 0 15px rgba(157, 78, 221, 0.4);
        }

        .logo-text h1 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
            background: linear-gradient(to right, #ffffff, var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Sidebar Nav Pills customization */
        .nav-pills .nav-link {
            color: var(--text-muted);
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 500;
            transition: all 0.3s;
            border: 1px solid transparent;
            margin-bottom: 8px;
            text-align: left;
        }

        .nav-pills .nav-link i {
            width: 24px;
            font-size: 1.1rem;
        }

        .nav-pills .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(157, 78, 221, 0.1);
        }

        .nav-pills .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(157, 78, 221, 0.2) 0%, rgba(0, 240, 255, 0.05) 100%);
            border: 1px solid var(--border-neon);
            box-shadow: 0 0 15px rgba(157, 78, 221, 0.1);
        }

        .nav-pills .nav-link.active i {
            color: var(--accent-cyan);
            filter: drop-shadow(0 0 5px rgba(0, 240, 255, 0.5));
        }

        /* Sidebar Profile Card */
        .sidebar-profile {
            margin-top: auto;
            border-top: 1px solid rgba(157, 78, 221, 0.15);
            padding-top: 1.5rem;
        }

        .profile-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 0 10px rgba(157,78,221,0.4);
        }

        .btn-logout {
            background: rgba(255, 0, 127, 0.08);
            border: 1px solid rgba(255, 0, 127, 0.25);
            color: var(--accent-pink);
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: var(--accent-pink);
            color: white;
            box-shadow: 0 0 15px rgba(255, 0, 127, 0.35);
        }

        /* Dashboard elements */
        .page-header {
            border-bottom: 1px solid rgba(157, 78, 221, 0.1);
            padding-bottom: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .page-title {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .search-input {
            background: rgba(7, 4, 14, 0.5);
            border: 1px solid var(--border-neon);
            border-radius: 30px;
            padding: 10px 16px 10px 40px;
            color: #fff;
            outline: none;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 10px rgba(0, 240, 255, 0.2);
            background: rgba(7, 4, 14, 0.7);
        }

        /* Bootstrap Card custom glass styles */
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-neon);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
            color: var(--text-main);
        }

        .glass-card:hover {
            border-color: var(--accent-purple);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(157, 78, 221, 0.15);
        }

        .stat-card-icon {
            font-size: 2.2rem;
            background: linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Bootstrap Table dark customization */
        .table-custom {
            color: var(--text-main);
        }

        .table-custom th {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1.5px solid rgba(157, 78, 221, 0.2);
            background-color: rgba(0,0,0,0.2) !important;
            padding: 14px 16px;
        }

        .table-custom td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background-color: transparent !important;
            vertical-align: middle;
        }

        .table-custom tbody tr:hover {
            background-color: rgba(157, 78, 221, 0.05) !important;
        }

        /* GPU Cards */
        .gpu-card {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-neon);
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .gpu-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent-cyan);
            box-shadow: 0 10px 25px rgba(0, 240, 255, 0.15);
        }

        .gpu-img-container {
            height: 170px;
            background-color: #0c0819;
            border-bottom: 1px solid rgba(157, 78, 221, 0.1);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .gpu-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .gpu-card:hover .gpu-img {
            transform: scale(1.05);
        }

        .gpu-card-placeholder-svg {
            width: 55px;
            height: 55px;
            color: var(--accent-purple);
            opacity: 0.6;
        }

        .gpu-price-tag {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(7, 4, 14, 0.85);
            border: 1px solid var(--accent-cyan);
            color: var(--accent-cyan);
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 20px;
            box-shadow: 0 0 8px rgba(0,240,255,0.2);
        }

        .gpu-req-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 10px;
            font-size: 0.8rem;
            color: var(--text-muted);
            white-space: pre-line;
            max-height: 90px;
            overflow-y: auto;
        }

        /* Status Badges */
        .badge-pill-status {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-lunas {
            background-color: rgba(57, 255, 20, 0.1);
            color: var(--accent-green);
            border: 1px solid rgba(57, 255, 20, 0.2);
        }

        .status-pending {
            background-color: rgba(255, 183, 3, 0.1);
            color: var(--accent-yellow);
            border: 1px solid rgba(255, 183, 3, 0.2);
        }

        .status-batal {
            background-color: rgba(255, 0, 127, 0.1);
            color: var(--accent-pink);
            border: 1px solid rgba(255, 0, 127, 0.2);
        }

        /* Bootstrap Modals custom dark styling */
        .modal-content-custom {
            background-color: #110923;
            border: 1px solid var(--accent-purple);
            border-radius: 20px;
            box-shadow: 0 10px 45px rgba(157, 78, 221, 0.35);
            color: var(--text-main);
        }

        .modal-header-custom {
            border-bottom: 1px solid rgba(157, 78, 221, 0.15);
        }

        .modal-footer-custom {
            border-top: 1px solid rgba(157, 78, 221, 0.15);
        }

        .form-label-custom {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .form-control-custom {
            background-color: rgba(7, 4, 14, 0.5);
            border: 1px solid var(--border-neon);
            color: #fff;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .form-control-custom:focus {
            background-color: rgba(7, 4, 14, 0.75);
            border-color: var(--accent-cyan);
            color: #fff;
            box-shadow: 0 0 8px rgba(0, 240, 255, 0.15);
        }

        .form-control-custom:disabled {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            border-color: rgba(255, 255, 255, 0.1);
        }

        /* File Upload */
        .upload-zone {
            border: 2px dashed var(--border-neon);
            border-radius: 10px;
            padding: 1.25rem;
            text-align: center;
            background: rgba(7, 4, 14, 0.2);
            cursor: pointer;
            position: relative;
        }

        .upload-zone:hover {
            border-color: var(--accent-cyan);
        }

        .upload-preview {
            max-width: 100%;
            max-height: 130px;
            border-radius: 8px;
            border: 1px solid var(--accent-purple);
        }

        .remove-preview-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent-pink);
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            box-shadow: 0 0 5px rgba(255,0,127,0.5);
        }

        /* Empty State inside Bootstrap card */
        .empty-card {
            text-align: center;
            padding: 4rem 2rem;
            border: 1px dashed var(--border-neon);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.01);
        }

        .empty-card i {
            font-size: 3rem;
            color: var(--accent-purple);
        }

        /* Toast Container bootstrap adjustments */
        .toast-wrapper {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1080;
        }

        /* Dynamic tabs visibility */
        .tab-section {
            display: none;
        }

        .tab-section.active {
            display: block;
        }

        footer {
            margin-top: auto;
            border-top: 1px solid rgba(157, 78, 221, 0.08);
            padding: 2rem 0 1rem;
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        footer a {
            color: var(--accent-cyan);
            text-decoration: none;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        
        <!-- SIDEBAR COLUMN -->
        <aside class="col-lg-2 col-md-3 sidebar-container d-flex flex-column">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="logo-icon">
                    <i class="fa-solid fa-cloud-bolt"></i>
                </div>
                <div class="logo-text">
                    <h1 class="mb-0">NusaGrid</h1>
                    <p class="mb-0 text-muted small text-uppercase tracking-wider">GPU Console</p>
                </div>
            </div>
            
            <nav class="nav nav-pills flex-column mb-auto" id="sidebarMenu">
                <button class="nav-link active w-100 text-start" data-tab="overview">
                    <i class="fa-solid fa-chart-line"></i> <span>Ringkasan</span>
                </button>
                <button class="nav-link w-100 text-start" data-tab="gpus">
                    <i class="fa-solid fa-microchip"></i> <span>Layanan GPU</span>
                </button>
                <button class="nav-link w-100 text-start" data-tab="users">
                    <i class="fa-solid fa-users"></i> <span>Daftar Pengguna</span>
                </button>
                <button class="nav-link w-100 text-start" data-tab="rentals">
                    <i class="fa-solid fa-receipt"></i> <span>Penyewaan GPU</span>
                </button>
            </nav>

            <div class="sidebar-profile d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="profile-avatar" id="profileAvatar">U</div>
                    <div>
                        <div class="profile-username fw-semibold text-white" id="profileUsername"><?= htmlspecialchars($_SESSION['user']['username']) ?></div>
                        <div class="small text-muted">Portal Tenant</div>
                    </div>
                </div>
                <button class="btn btn-logout py-2 w-100" id="logoutBtn">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Keluar
                </button>
            </div>
        </aside>

        <!-- MAIN PANEL COLUMN -->
        <main class="col-lg-10 col-md-9 main-content">
            <!-- Content Header -->
            <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="page-title mb-1 text-white" id="currentTabTitle">Ringkasan Portal</h2>
                    <p class="text-muted small mb-0" id="currentTabDesc">Pantau statistik server, pengguna, dan penyewaan GPU aktif.</p>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="search-wrapper" id="searchContainer" style="display:none;">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="globalSearchInput" class="search-input form-control-custom" placeholder="Cari data...">
                    </div>
                    
                    <button class="btn btn-primary" id="actionBtn">
                        <i class="fa-solid fa-receipt me-1"></i> Sewa GPU Baru
                    </button>
                </div>
            </div>

            <!-- TAB 1: OVERVIEW -->
            <section id="tab-overview" class="tab-section active">
                <!-- Stats Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card glass-card h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0 fw-bold fs-2" id="statGpuCount">0</h3>
                                    <p class="text-muted small text-uppercase tracking-wider mb-0">Model GPU</p>
                                </div>
                                <div class="stat-card-icon"><i class="fa-solid fa-server"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card glass-card h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0 fw-bold fs-2" id="statUserCount">0</h3>
                                    <p class="text-muted small text-uppercase tracking-wider mb-0">Total Pengguna</p>
                                </div>
                                <div class="stat-card-icon"><i class="fa-solid fa-user-gear"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card glass-card h-100">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0 fw-bold fs-2" id="statRentalCount">0</h3>
                                    <p class="text-muted small text-uppercase tracking-wider mb-0">Total Transaksi</p>
                                </div>
                                <div class="stat-card-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card glass-card h-100 border-success border-opacity-25">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0 fw-bold fs-2 text-white" id="statRevenue">Rp 0</h3>
                                    <p class="text-muted small text-uppercase tracking-wider mb-0">Pendapatan (Lunas)</p>
                                </div>
                                <div class="stat-card-icon text-success opacity-100"><i class="fa-solid fa-wallet"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panels row -->
                <div class="row g-4">
                    <!-- Left: Recent Rentals Table -->
                    <div class="col-lg-8">
                        <div class="card glass-card">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold mb-0 text-white" style="font-family:'Rajdhani',sans-serif; letter-spacing:0.5px;">Penyewaan Terbaru</h5>
                                    <a href="#" class="switch-tab-link text-neon small" data-target="rentals">Lihat Semua</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-custom mb-0">
                                        <thead>
                                            <tr>
                                                <th>Penyewa</th>
                                                <th>GPU</th>
                                                <th>Durasi</th>
                                                <th>Total Biaya</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentRentalsBody">
                                            <!-- Dynamically filled -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Right: GPU List Summary -->
                    <div class="col-lg-4">
                        <div class="card glass-card">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-bold mb-0 text-white" style="font-family:'Rajdhani',sans-serif; letter-spacing:0.5px;">Daftar Layanan GPU</h5>
                                    <a href="#" class="switch-tab-link text-neon small" data-target="gpus">Kelola</a>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-custom mb-0">
                                        <thead>
                                            <tr>
                                                <th>Model GPU</th>
                                                <th>Harga</th>
                                            </tr>
                                        </thead>
                                        <tbody id="quickGpuBody">
                                            <!-- Dynamically filled -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TAB 2: GPU SERVICES -->
            <section id="tab-gpus" class="tab-section">
                <div class="row g-4" id="gpusGrid">
                    <!-- Cards will be populated dynamically -->
                </div>
            </section>

            <!-- TAB 3: USERS LIST -->
            <section id="tab-users" class="tab-section">
                <div class="card glass-card">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TAB 4: RENTALS -->
            <section id="tab-rentals" class="tab-section">
                <div class="card glass-card">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Penyewa</th>
                                        <th>Model GPU</th>
                                        <th>Tanggal Sewa</th>
                                        <th>Durasi</th>
                                        <th>Total Harga</th>
                                        <th>Status Pembayaran</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="rentalsTableBody">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="mt-auto text-center">
                <p class="mb-0 text-muted">&copy; 2026 <a href="#">NusaGrid Indonesia</a>. Powered by Bootstrap 5 & High-Performance Clusters.</p>
            </footer>
        </main>
        
    </div>
</div>

<!-- ==========================================
     BOOTSTRAP MODALS
========================================== -->

<!-- 1. GPU MODAL -->
<div class="modal fade" id="gpuModal" tabindex="-1" aria-labelledby="gpuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold" id="gpuModalTitle">Tambah Layanan GPU</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="gpuForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="gpuId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="namaGpu" class="form-label form-label-custom">Nama GPU</label>
                        <input type="text" class="form-control form-control-custom" id="namaGpu" name="nama_gpu" placeholder="Contoh: NVIDIA RTX 4090" required>
                    </div>
                    <div class="mb-3">
                        <label for="hargaGpu" class="form-label form-label-custom">Harga Layanan</label>
                        <input type="text" class="form-control form-control-custom" id="hargaGpu" name="harga" placeholder="Contoh: Rp 15.000 / Jam" required>
                    </div>
                    <div class="mb-3">
                        <label for="kebutuhanGpu" class="form-label form-label-custom">Spesifikasi & Persyaratan Sistem</label>
                        <textarea class="form-control form-control-custom" id="kebutuhanGpu" name="kebutuhan" placeholder="RAM minimal, tipe CPU, driver CUDA..." rows="3"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label form-label-custom">Foto GPU</label>
                        <div class="upload-zone" id="gpuUploadZone">
                            <input type="file" id="fotoGpu" name="foto" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" accept="image/*">
                            <div class="upload-info" id="gpuUploadInfo">
                                <i class="fa-solid fa-cloud-arrow-up mb-2 text-muted fs-3"></i>
                                <p class="small text-muted mb-1">Klik atau seret file gambar ke sini</p>
                                <span style="font-size: 0.7rem;" class="text-white-50">Mendukung JPG, PNG, WEBP, GIF (Maks 5MB)</span>
                            </div>
                            <div class="image-preview-wrapper" id="gpuPreviewWrapper">
                                <img src="" id="gpuImgPreview" class="upload-preview" alt="Preview">
                                <button type="button" id="removeGpuPreview" class="remove-preview-btn"><i class="fa-solid fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="gpuSaveBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. USER MODAL -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold" id="userModalTitle">Tambah Pengguna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm">
                <input type="hidden" name="id" id="userId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label form-label-custom">Username</label>
                        <input type="text" class="form-control form-control-custom" id="username" name="username" placeholder="Masukkan username..." required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label form-label-custom">Alamat Email</label>
                        <input type="email" class="form-control form-control-custom" id="email" name="email" placeholder="Masukkan email..." required>
                    </div>
                    <div class="mb-1">
                        <label for="password" class="form-label form-label-custom" id="passLabel">Password</label>
                        <input type="password" class="form-control form-control-custom" id="password" name="password" placeholder="Masukkan password..." required>
                        <span style="font-size: 0.75rem;" class="text-muted mt-1 d-block" id="passHint"></span>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="userSaveBtn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. RENTAL MODAL -->
<div class="modal fade" id="rentalModal" tabindex="-1" aria-labelledby="rentalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title fw-bold" id="rentalModalTitle">Sewa GPU Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rentalForm">
                <input type="hidden" name="id" id="rentalId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rentalUserId" class="form-label form-label-custom">Pengguna (Penyewa)</label>
                        <select id="rentalUserId" name="user_id" class="form-select form-control-custom" required>
                            <option value="">-- Pilih Pengguna --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rentalGpuId" class="form-label form-label-custom">Pilih GPU</label>
                        <select id="rentalGpuId" name="gpu_id" class="form-select form-control-custom" required>
                            <option value="">-- Pilih GPU Service --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rentalDurasi" class="form-label form-label-custom">Durasi Sewa (Jam)</label>
                        <input type="number" class="form-control form-control-custom" id="rentalDurasi" name="durasi_jam" min="1" placeholder="Masukkan durasi dalam jam..." required>
                    </div>
                    <div class="mb-3">
                        <label for="rentalTotalDisplay" class="form-label form-label-custom">Total Harga</label>
                        <input type="text" class="form-control form-control-custom" id="rentalTotalDisplay" placeholder="Rp 0" disabled>
                        <input type="hidden" id="rentalTotal" name="total_harga" value="0">
                        <span style="font-size: 0.7rem;" class="text-muted mt-1 d-block">Harga otomatis dikalikan tarif per jam GPU.</span>
                    </div>
                    <div class="mb-1">
                        <label for="rentalStatus" class="form-label form-label-custom">Status Pembayaran</label>
                        <select id="rentalStatus" name="status_pembayaran" class="form-select form-control-custom">
                            <option value="pending">PENDING (Belum Bayar)</option>
                            <option value="lunas">LUNAS (Sudah Bayar)</option>
                            <option value="batal">BATAL (Dibatalkan)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="rentalSaveBtn">Sewa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast notifications wrapper -->
<div class="toast-wrapper d-flex flex-column gap-2" id="toastContainer"></div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- JAVASCRIPT CONSOLE CONTROLLER -->
<script>
    const API_URL = 'api.php';
    
    // State variables
    let activeTab = 'overview';
    let gpuList = [];
    let userList = [];
    let rentalList = [];

    // Bootstrap Instances
    let bsGpuModal, bsUserModal, bsRentalModal;

    // DOM Elements
    const menuButtons = document.querySelectorAll('#sidebarMenu .nav-link');
    const tabSections = document.querySelectorAll('.tab-section');
    const currentTabTitle = document.getElementById('currentTabTitle');
    const currentTabDesc = document.getElementById('currentTabDesc');
    const globalSearchInput = document.getElementById('globalSearchInput');
    const actionBtn = document.getElementById('actionBtn');

    // Forms
    const gpuForm = document.getElementById('gpuForm');
    const userForm = document.getElementById('userForm');
    const rentalForm = document.getElementById('rentalForm');

    // Bootstrap on load
    window.addEventListener('DOMContentLoaded', () => {
        // Initialize Bootstrap Modals
        bsGpuModal = new bootstrap.Modal(document.getElementById('gpuModal'));
        bsUserModal = new bootstrap.Modal(document.getElementById('userModal'));
        bsRentalModal = new bootstrap.Modal(document.getElementById('rentalModal'));

        initializeNavigation();
        loadAllData();
        setupFormListeners();
        setupImagePreviewListeners();
    });

    // API Fetch Wrapper
    async function apiFetch(url, options = {}) {
        const res = await fetch(url, options);
        if (res.status === 401) {
            showToast('Sesi Berakhir', 'Silakan login kembali.', 'danger');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1000);
            throw new Error('Unauthorized');
        }
        return res;
    }

    // Load data
    async function loadAllData() {
        showLoadingIndicators();
        await Promise.all([
            fetchGpus(),
            fetchUsers(),
            fetchRentals()
        ]);
        updateOverviewStats();
        renderAllTabViews();
    }

    function showLoadingIndicators() {
        document.getElementById('recentRentalsBody').innerHTML = `<tr><td colspan="5" style="text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> Memuat data...</td></tr>`;
        document.getElementById('quickGpuBody').innerHTML = `<tr><td colspan="2" style="text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> Memuat data...</td></tr>`;
        document.getElementById('gpusGrid').innerHTML = `<div class="col-12"><div class="empty-card"><i class="fa-solid fa-circle-notch fa-spin fs-4 mb-2"></i><h4>Memuat data GPU...</h4></div></div>`;
        document.getElementById('usersTableBody').innerHTML = `<tr><td colspan="4" style="text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> Memuat data...</td></tr>`;
        document.getElementById('rentalsTableBody').innerHTML = `<tr><td colspan="8" style="text-align:center;"><i class="fa-solid fa-spinner fa-spin"></i> Memuat data...</td></tr>`;
    }

    // ==========================================
    // DATA FETCHING API FUNCTIONS
    // ==========================================

    async function fetchGpus() {
        try {
            const res = await apiFetch(`${API_URL}?action=read_gpus`);
            const r = await res.json();
            if (r.status === 'success') {
                gpuList = r.data;
            }
        } catch (err) {
            console.error('Fetch GPU Error:', err);
            if (err.message !== 'Unauthorized') {
                showToast('Error', 'Gagal memuat daftar GPU.', 'danger');
            }
        }
    }

    async function fetchUsers() {
        try {
            const res = await apiFetch(`${API_URL}?action=read_users`);
            const r = await res.json();
            if (r.status === 'success') {
                userList = r.data;
            }
        } catch (err) {
            console.error('Fetch Users Error:', err);
            if (err.message !== 'Unauthorized') {
                showToast('Error', 'Gagal memuat daftar pengguna.', 'danger');
            }
        }
    }

    async function fetchRentals() {
        try {
            const res = await apiFetch(`${API_URL}?action=read_rentals`);
            const r = await res.json();
            if (r.status === 'success') {
                rentalList = r.data;
            }
        } catch (err) {
            console.error('Fetch Rentals Error:', err);
            if (err.message !== 'Unauthorized') {
                showToast('Error', 'Gagal memuat daftar sewa.', 'danger');
            }
        }
    }

    // ==========================================
    // UI RENDERING FUNCTIONS
    // ==========================================

    function renderAllTabViews() {
        renderOverviewTab();
        renderGpusTab();
        renderUsersTab();
        renderRentalsTab();
        populateRentalDropdowns();
    }

    function renderOverviewTab() {
        const recentRentalsBody = document.getElementById('recentRentalsBody');
        if (rentalList.length === 0) {
            recentRentalsBody.innerHTML = `<tr><td colspan="5" style="text-align:center;" class="text-muted">Belum ada transaksi sewa.</td></tr>`;
        } else {
            recentRentalsBody.innerHTML = '';
            rentalList.slice(0, 5).forEach(rental => {
                const tr = document.createElement('tr');
                const formattedPrice = parseFloat(rental.total_harga).toLocaleString('id-ID');
                tr.innerHTML = `
                    <td><strong>${escapeHTML(rental.username || 'User Deleted')}</strong></td>
                    <td>${escapeHTML(rental.nama_gpu || 'GPU Deleted')}</td>
                    <td>${rental.durasi_jam} Jam</td>
                    <td style="color: var(--accent-cyan);">Rp ${formattedPrice}</td>
                    <td><span class="badge badge-pill-status status-${rental.status_pembayaran}">${rental.status_pembayaran}</span></td>
                `;
                recentRentalsBody.appendChild(tr);
            });
        }

        const quickGpuBody = document.getElementById('quickGpuBody');
        if (gpuList.length === 0) {
            quickGpuBody.innerHTML = `<tr><td colspan="2" style="text-align:center;" class="text-muted">Belum ada GPU terdaftar.</td></tr>`;
        } else {
            quickGpuBody.innerHTML = '';
            gpuList.slice(0, 5).forEach(gpu => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${escapeHTML(gpu.nama_gpu)}</strong></td>
                    <td><span style="color: var(--accent-cyan); font-weight:600;">${escapeHTML(gpu.harga)}</span></td>
                `;
                quickGpuBody.appendChild(tr);
            });
        }
    }

    function updateOverviewStats() {
        document.getElementById('statGpuCount').textContent = gpuList.length;
        document.getElementById('statUserCount').textContent = userList.length;
        document.getElementById('statRentalCount').textContent = rentalList.length;

        let revSum = 0;
        rentalList.forEach(r => {
            if (r.status_pembayaran === 'lunas') {
                revSum += parseFloat(r.total_harga);
            }
        });
        document.getElementById('statRevenue').textContent = 'Rp ' + revSum.toLocaleString('id-ID');
    }

    function renderGpusTab() {
        const grid = document.getElementById('gpusGrid');
        if (gpuList.length === 0) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="empty-card">
                        <i class="fa-solid fa-server mb-3 fs-3"></i>
                        <h4>Layanan GPU Kosong</h4>
                        <p class="text-muted">Belum ada model GPU yang didaftarkan pada console.</p>
                        <button type="button" class="btn btn-primary" onclick="openAddGpuModal()"><i class="fa-solid fa-plus me-1"></i> Tambah GPU</button>
                    </div>
                </div>
            `;
            return;
        }

        grid.innerHTML = '';
        gpuList.forEach(gpu => {
            const col = document.createElement('div');
            col.className = 'col-lg-4 col-md-6 col-sm-12';
            
            let photoHtml = '';
            if (gpu.foto) {
                photoHtml = `<img src="${gpu.foto}" class="gpu-img" alt="${gpu.nama_gpu}">`;
            } else {
                photoHtml = `
                    <svg class="gpu-card-placeholder-svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                `;
            }

            col.innerHTML = `
                <div class="gpu-card">
                    <div class="gpu-img-container">
                        ${photoHtml}
                        <div class="gpu-price-tag">${escapeHTML(gpu.harga)}</div>
                    </div>
                    <div class="gpu-details d-flex flex-column flex-grow-1 p-3">
                        <h5 class="gpu-title mb-2 text-white fw-bold">${escapeHTML(gpu.nama_gpu)}</h5>
                        <div class="form-label-custom small mb-1">Persyaratan Sistem</div>
                        <div class="gpu-req-box mb-3 flex-grow-1">${escapeHTML(gpu.kebutuhan || 'Tidak ada spesifikasi khusus.')}</div>
                        <div class="gpu-card-actions mt-auto border-top border-secondary border-opacity-10 pt-2 text-end">
                            <button type="button" class="btn btn-edit me-1" onclick="openEditGpuModal(${gpu.id})"><i class="fa-solid fa-pencil"></i> Edit</button>
                            <button type="button" class="btn btn-danger" onclick="deleteGpu(${gpu.id})"><i class="fa-solid fa-trash"></i> Hapus</button>
                        </div>
                    </div>
                </div>
            `;
            grid.appendChild(col);
        });
    }

    function renderUsersTab() {
        const body = document.getElementById('usersTableBody');
        if (userList.length === 0) {
            body.innerHTML = `<tr><td colspan="4" style="text-align:center;" class="text-muted">Tidak ada pengguna terdaftar.</td></tr>`;
            return;
        }

        body.innerHTML = '';
        userList.forEach(user => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${user.id}</td>
                <td><strong>${escapeHTML(user.username)}</strong></td>
                <td>${escapeHTML(user.email)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-edit me-1" onclick="openEditUserModal(${user.id})"><i class="fa-solid fa-pencil"></i> Edit</button>
                    <button type="button" class="btn btn-danger" onclick="deleteUser(${user.id})"><i class="fa-solid fa-trash"></i> Hapus</button>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    function renderRentalsTab() {
        const body = document.getElementById('rentalsTableBody');
        if (rentalList.length === 0) {
            body.innerHTML = `<tr><td colspan="8" style="text-align:center;" class="text-muted">Tidak ada data penyewaan GPU.</td></tr>`;
            return;
        }

        body.innerHTML = '';
        rentalList.forEach(rental => {
            const tr = document.createElement('tr');
            const formattedPrice = parseFloat(rental.total_harga).toLocaleString('id-ID');
            
            let dateStr = rental.tanggal_sewa;
            try {
                const dateObj = new Date(rental.tanggal_sewa);
                dateStr = dateObj.toLocaleDateString('id-ID', {
                    year: 'numeric', month: 'short', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
            } catch(e) {}

            tr.innerHTML = `
                <td>#RS-${rental.id}</td>
                <td><strong>${escapeHTML(rental.username || 'User Deleted')}</strong></td>
                <td>${escapeHTML(rental.nama_gpu || 'GPU Deleted')}</td>
                <td>${dateStr}</td>
                <td>${rental.durasi_jam} Jam</td>
                <td style="color: var(--accent-cyan); font-weight:600;">Rp ${formattedPrice}</td>
                <td><span class="badge-pill-status status-${rental.status_pembayaran}">${rental.status_pembayaran}</span></td>
                <td class="text-end">
                    <button type="button" class="btn btn-edit me-1" onclick="openEditRentalModal(${rental.id})"><i class="fa-solid fa-pencil"></i> Edit</button>
                    <button type="button" class="btn btn-danger" onclick="deleteRental(${rental.id})"><i class="fa-solid fa-trash"></i> Hapus</button>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    function populateRentalDropdowns() {
        const userDropdown = document.getElementById('rentalUserId');
        const gpuDropdown = document.getElementById('rentalGpuId');
        
        userDropdown.innerHTML = '<option value="">-- Pilih Pengguna --</option>';
        gpuDropdown.innerHTML = '<option value="">-- Pilih GPU Service --</option>';

        userList.forEach(user => {
            const opt = document.createElement('option');
            opt.value = user.id;
            opt.textContent = `${user.username} (${user.email})`;
            userDropdown.appendChild(opt);
        });

        gpuList.forEach(gpu => {
            const opt = document.createElement('option');
            opt.value = gpu.id;
            opt.textContent = `${gpu.nama_gpu} - ${gpu.harga}`;
            opt.setAttribute('data-harga', gpu.harga);
            gpuDropdown.appendChild(opt);
        });
    }

    // ==========================================
    // DYNAMIC DURATION & PRICE COUNTER
    // ==========================================
    const rentalGpuIdSelect = document.getElementById('rentalGpuId');
    const rentalDurasiInput = document.getElementById('rentalDurasi');
    const rentalTotalDisplay = document.getElementById('rentalTotalDisplay');
    const rentalTotalHidden = document.getElementById('rentalTotal');

    function calculateRentalPrice() {
        const selectedGpuOpt = rentalGpuIdSelect.options[rentalGpuIdSelect.selectedIndex];
        const duration = parseInt(rentalDurasiInput.value, 10);

        if (!selectedGpuOpt || selectedGpuOpt.value === '' || isNaN(duration) || duration <= 0) {
            rentalTotalDisplay.value = 'Rp 0';
            rentalTotalHidden.value = '0';
            return;
        }

        const rawHargaText = selectedGpuOpt.getAttribute('data-harga') || '';
        const cleanRate = rawHargaText.replace(/[^0-9]/g, '');
        const rate = cleanRate ? parseFloat(cleanRate) : 0;
        
        const total = rate * duration;
        rentalTotalDisplay.value = 'Rp ' + total.toLocaleString('id-ID');
        rentalTotalHidden.value = total;
    }

    rentalGpuIdSelect.addEventListener('change', calculateRentalPrice);
    rentalDurasiInput.addEventListener('input', calculateRentalPrice);

    // ==========================================
    // NAVIGATION SYSTEM
    // ==========================================

    function initializeNavigation() {
        menuButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabName = btn.getAttribute('data-tab');
                switchTab(tabName);
            });
        });

        document.querySelectorAll('.switch-tab-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = link.getAttribute('data-target');
                switchTab(target);
            });
        });

        actionBtn.addEventListener('click', () => {
            if (activeTab === 'gpus') openAddGpuModal();
            else if (activeTab === 'users') openAddUserModal();
            else if (activeTab === 'rentals') openAddRentalModal();
            else {
                switchTab('rentals');
                openAddRentalModal();
            }
        });

        globalSearchInput.addEventListener('input', runGlobalFiltering);

        // Standalone Logout event
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            const conf = confirm('Apakah Anda yakin ingin keluar dari portal NusaGrid?');
            if (!conf) return;

            try {
                const res = await fetch(`${API_URL}?action=logout`, { method: 'POST' });
                const r = await res.json();
                if (r.status === 'success') {
                    window.location.href = 'login.php';
                }
            } catch(err) {
                showToast('Error', 'Gagal logout dari server.', 'danger');
            }
        });
    }

    function switchTab(tabName) {
        activeTab = tabName;
        
        menuButtons.forEach(b => b.classList.remove('active'));
        document.querySelector(`#sidebarMenu .nav-link[data-tab="${tabName}"]`).classList.add('active');

        tabSections.forEach(sec => sec.classList.remove('active'));
        document.getElementById(`tab-${tabName}`).classList.add('active');

        globalSearchInput.value = '';
        
        if (tabName === 'overview') {
            currentTabTitle.textContent = 'Ringkasan Portal';
            currentTabDesc.textContent = 'Pantau statistik server, pengguna, dan penyewaan GPU aktif.';
            actionBtn.innerHTML = `<i class="fa-solid fa-receipt me-1"></i> Sewa GPU Baru`;
            document.getElementById('searchContainer').style.display = 'none';
        } else if (tabName === 'gpus') {
            currentTabTitle.textContent = 'Layanan GPU';
            currentTabDesc.textContent = 'Kelola ketersediaan spesifikasi, harga instance GPU, dan foto spesifikasi.';
            actionBtn.innerHTML = `<i class="fa-solid fa-plus me-1"></i> Tambah Layanan`;
            document.getElementById('searchContainer').style.display = 'block';
            globalSearchInput.placeholder = 'Cari model GPU...';
        } else if (tabName === 'users') {
            currentTabTitle.textContent = 'Data Pengguna';
            currentTabDesc.textContent = 'Kelola data pengguna terdaftar (Username & Alamat Email).';
            actionBtn.innerHTML = `<i class="fa-solid fa-plus me-1"></i> Tambah Pengguna`;
            document.getElementById('searchContainer').style.display = 'block';
            globalSearchInput.placeholder = 'Cari username/email...';
        } else if (tabName === 'rentals') {
            currentTabTitle.textContent = 'Transaksi Penyewaan';
            currentTabDesc.textContent = 'Kelola penugasan GPU instance ke penyewa, durasi sewa, dan status pembayaran.';
            actionBtn.innerHTML = `<i class="fa-solid fa-receipt me-1"></i> Catat Sewa Baru`;
            document.getElementById('searchContainer').style.display = 'block';
            globalSearchInput.placeholder = 'Cari penyewa/GPU...';
        }
        
        runGlobalFiltering();
    }

    function runGlobalFiltering() {
        const query = globalSearchInput.value.toLowerCase().trim();

        if (activeTab === 'gpus') {
            const cards = document.querySelectorAll('.gpu-card');
            cards.forEach(card => {
                const name = card.querySelector('.gpu-title').textContent.toLowerCase();
                const spec = card.querySelector('.gpu-req-box').textContent.toLowerCase();
                const price = card.querySelector('.gpu-price-tag').textContent.toLowerCase();
                
                const col = card.parentElement;
                if (name.includes(query) || spec.includes(query) || price.includes(query)) {
                    col.style.display = '';
                } else {
                    col.style.display = 'none';
                }
            });
        } else if (activeTab === 'users') {
            const rows = document.querySelectorAll('#usersTableBody tr');
            rows.forEach(row => {
                if (row.cells.length < 3) return;
                const username = row.cells[1].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();
                
                if (username.includes(query) || email.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        } else if (activeTab === 'rentals') {
            const rows = document.querySelectorAll('#rentalsTableBody tr');
            rows.forEach(row => {
                if (row.cells.length < 7) return;
                const user = row.cells[1].textContent.toLowerCase();
                const gpu = row.cells[2].textContent.toLowerCase();
                const status = row.cells[6].textContent.toLowerCase();
                
                if (user.includes(query) || gpu.includes(query) || status.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    }

    // ==========================================
    // MODAL OPENERS
    // ==========================================

    function openAddGpuModal() {
        resetGpuForm();
        document.getElementById('gpuModalTitle').textContent = 'Tambah Layanan GPU';
        document.getElementById('gpuSaveBtn').textContent = 'Tambah Layanan';
        bsGpuModal.show();
    }

    function openEditGpuModal(id) {
        resetGpuForm();
        const gpu = gpuList.find(g => g.id == id);
        if (!gpu) return;

        document.getElementById('gpuId').value = gpu.id;
        document.getElementById('namaGpu').value = gpu.nama_gpu;
        document.getElementById('hargaGpu').value = gpu.harga;
        document.getElementById('kebutuhanGpu').value = gpu.kebutuhan || '';

        if (gpu.foto) {
            document.getElementById('gpuImgPreview').src = gpu.foto;
            document.getElementById('gpuPreviewWrapper').style.display = 'block';
            document.getElementById('gpuUploadInfo').style.display = 'none';
        }

        document.getElementById('gpuModalTitle').textContent = 'Edit Layanan GPU';
        document.getElementById('gpuSaveBtn').textContent = 'Simpan Perubahan';
        bsGpuModal.show();
    }

    function resetGpuForm() {
        gpuForm.reset();
        document.getElementById('gpuId').value = '';
        document.getElementById('gpuImgPreview').src = '';
        document.getElementById('gpuPreviewWrapper').style.display = 'none';
        document.getElementById('gpuUploadInfo').style.display = 'block';
    }

    function openAddUserModal() {
        userForm.reset();
        document.getElementById('userId').value = '';
        document.getElementById('passLabel').textContent = 'Password';
        document.getElementById('password').required = true;
        document.getElementById('passHint').textContent = '';
        
        document.getElementById('userModalTitle').textContent = 'Tambah Pengguna';
        document.getElementById('userSaveBtn').textContent = 'Tambah User';
        bsUserModal.show();
    }

    function openEditUserModal(id) {
        userForm.reset();
        const user = userList.find(u => u.id == id);
        if (!user) return;

        document.getElementById('userId').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email;
        
        document.getElementById('passLabel').textContent = 'Password Baru';
        document.getElementById('password').required = false;
        document.getElementById('passHint').textContent = 'Biarkan kolom password kosong jika tidak ingin mengubah password.';
        
        document.getElementById('userModalTitle').textContent = 'Edit Pengguna';
        document.getElementById('userSaveBtn').textContent = 'Simpan Perubahan';
        bsUserModal.show();
    }

    function openAddRentalModal() {
        rentalForm.reset();
        document.getElementById('rentalId').value = '';
        rentalTotalDisplay.value = 'Rp 0';
        rentalTotalHidden.value = '0';
        document.getElementById('rentalStatus').value = 'pending';
        
        document.getElementById('rentalModalTitle').textContent = 'Sewa GPU Baru';
        document.getElementById('rentalSaveBtn').textContent = 'Catat Sewa';
        bsRentalModal.show();
    }

    function openEditRentalModal(id) {
        rentalForm.reset();
        const rental = rentalList.find(r => r.id == id);
        if (!rental) return;

        document.getElementById('rentalId').value = rental.id;
        document.getElementById('rentalUserId').value = rental.user_id;
        document.getElementById('rentalGpuId').value = rental.gpu_id;
        document.getElementById('rentalDurasi').value = rental.durasi_jam;
        
        document.getElementById('rentalTotalHidden').value = rental.total_harga;
        document.getElementById('rentalTotalDisplay').value = 'Rp ' + parseFloat(rental.total_harga).toLocaleString('id-ID');
        document.getElementById('rentalStatus').value = rental.status_pembayaran;

        document.getElementById('rentalModalTitle').textContent = 'Edit Transaksi Sewa';
        document.getElementById('rentalSaveBtn').textContent = 'Simpan Perubahan';
        bsRentalModal.show();
    }

    // ==========================================
    // FILE PREVIEW LISTENERS
    // ==========================================

    function setupImagePreviewListeners() {
        const fileInput = document.getElementById('fotoGpu');
        const imgPreview = document.getElementById('gpuImgPreview');
        const previewWrapper = document.getElementById('gpuPreviewWrapper');
        const uploadInfo = document.getElementById('gpuUploadInfo');
        const removeBtn = document.getElementById('removeGpuPreview');

        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    previewWrapper.style.display = 'block';
                    uploadInfo.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });

        removeBtn.addEventListener('click', () => {
            fileInput.value = '';
            imgPreview.src = '';
            previewWrapper.style.display = 'none';
            uploadInfo.style.display = 'block';
        });
    }

    // ==========================================
    // FORM SUBMISSION EVENT LISTENERS
    // ==========================================

    function setupFormListeners() {
        // GPU Form submit
        gpuForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const isEdit = document.getElementById('gpuId').value !== '';
            const action = isEdit ? 'update_gpu' : 'create_gpu';
            const fd = new FormData(this);
            
            try {
                const res = await apiFetch(`${API_URL}?action=${action}`, { method: 'POST', body: fd });
                const r = await res.json();
                if (r.status === 'success') {
                    showToast('Sukses', r.message, 'success');
                    bsGpuModal.hide();
                    loadAllData();
                } else {
                    showToast('Gagal', r.message, 'danger');
                }
            } catch(err) {
                if (err.message !== 'Unauthorized') {
                    showToast('Error', 'Kesalahan koneksi saat menyimpan GPU.', 'danger');
                }
            }
        });

        // User Form submit
        userForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const isEdit = document.getElementById('userId').value !== '';
            const action = isEdit ? 'update_user' : 'create_user';
            const fd = new FormData(this);
            
            try {
                const res = await apiFetch(`${API_URL}?action=${action}`, { method: 'POST', body: fd });
                const r = await res.json();
                if (r.status === 'success') {
                    showToast('Sukses', r.message, 'success');
                    bsUserModal.hide();
                    loadAllData();
                } else {
                    showToast('Gagal', r.message, 'danger');
                }
            } catch(err) {
                if (err.message !== 'Unauthorized') {
                    showToast('Error', 'Kesalahan koneksi saat menyimpan user.', 'danger');
                }
            }
        });

        // Rental Form submit
        rentalForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const isEdit = document.getElementById('rentalId').value !== '';
            const action = isEdit ? 'update_rental' : 'create_rental';
            const fd = new FormData(this);
            
            try {
                const res = await apiFetch(`${API_URL}?action=${action}`, { method: 'POST', body: fd });
                const r = await res.json();
                if (r.status === 'success') {
                    showToast('Sukses', r.message, 'success');
                    bsRentalModal.hide();
                    loadAllData();
                } else {
                    showToast('Gagal', r.message, 'danger');
                }
            } catch(err) {
                if (err.message !== 'Unauthorized') {
                    showToast('Error', 'Kesalahan koneksi saat menyimpan sewa.', 'danger');
                }
            }
        });
    }

    // ==========================================
    // DELETE ACTIONS
    // ==========================================

    async function deleteGpu(id) {
        const gpu = gpuList.find(g => g.id == id);
        if (!gpu) return;

        const conf = confirm(`Apakah Anda yakin ingin menghapus model GPU "${gpu.nama_gpu}"?\n\nPERINGATAN: Semua riwayat transaksi sewa yang terikat pada GPU ini juga akan ikut terhapus karena relasi basis data.`);
        if (!conf) return;

        try {
            const res = await apiFetch(`${API_URL}?action=delete_gpu`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            const r = await res.json();
            if (r.status === 'success') {
                showToast('Dihapus', r.message, 'success');
                loadAllData();
            } else {
                showToast('Gagal', r.message, 'danger');
            }
        } catch(err) {
            if (err.message !== 'Unauthorized') {
                showToast('Error', 'Gagal menghubungkan ke server.', 'danger');
            }
        }
    }

    async function deleteUser(id) {
        const user = userList.find(u => u.id == id);
        if (!user) return;

        const conf = confirm(`Apakah Anda yakin ingin menghapus akun "${user.username}"?\n\nPERINGATAN: Seluruh riwayat transaksi sewa yang dilakukan oleh user ini akan dihapus secara otomatis.`);
        if (!conf) return;

        try {
            const res = await apiFetch(`${API_URL}?action=delete_user`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            const r = await res.json();
            if (r.status === 'success') {
                showToast('Dihapus', r.message, 'success');
                loadAllData();
            } else {
                showToast('Gagal', r.message, 'danger');
            }
        } catch(err) {
            if (err.message !== 'Unauthorized') {
                showToast('Error', 'Gagal menghubungkan ke server.', 'danger');
            }
        }
    }

    async function deleteRental(id) {
        const conf = confirm(`Apakah Anda yakin ingin menghapus catatan transaksi sewa #RS-${id}?`);
        if (!conf) return;

        try {
            const res = await apiFetch(`${API_URL}?action=delete_rental`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            const r = await res.json();
            if (r.status === 'success') {
                showToast('Dihapus', r.message, 'success');
                loadAllData();
            } else {
                showToast('Gagal', r.message, 'danger');
            }
        } catch(err) {
            if (err.message !== 'Unauthorized') {
                showToast('Error', 'Gagal menghubungkan ke server.', 'danger');
            }
        }
    }

    // ==========================================
    // TOAST SYSTEM HELPERS (Bootstrap Toast style)
    // ==========================================

    function showToast(title, desc, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show`;
        toast.role = 'alert';
        toast.ariaLive = 'assertive';
        toast.ariaAtomic = 'true';
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong>: ${desc}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Remove toast after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 4000);
    }

    function escapeHTML(str) {
        if (!str) return '';
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
</body>
</html>
