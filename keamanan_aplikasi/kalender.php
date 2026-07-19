<?php
// kalender.php
require_once "config/koneksi.php";
require_once "config/helper.php";

// Pastikan user sudah login
auth_check();

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// -------------------------------------------------------------------------
// AJAX Endpoint: Mengembalikan detail transaksi untuk tanggal tertentu (GET request)
// -------------------------------------------------------------------------
if (isset($_GET["action"]) && $_GET["action"] === "get_details") {
    header("Content-Type: application/json");
    $target_date = isset($_GET["date"]) ? trim($_GET["date"]) : "";

    // Validasi format tanggal sederhana (YYYY-MM-DD)
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $target_date)) {
        echo json_encode(["status" => "error", "message" => "Format tanggal tidak valid."]);
        exit();
    }

    try {
        // Ambil transaksi milik user pada tanggal tersebut beserta nama kategori & nama dompetnya
        $stmt = $pdo->prepare(
            "SELECT t.id, t.nominal, t.jenis, t.keterangan, t.tanggal, 
                    COALESCE(k.nama_kategori, 'Tanpa Kategori') AS nama_kategori,
                    COALESCE(d.nama_dompet, 'Tanpa Dompet') AS nama_dompet
             FROM transaksi t
             LEFT JOIN kategori k ON t.id_kategori = k.id_kategori
             LEFT JOIN dompet d ON t.id_dompet = d.id_dompet
             WHERE t.user_id = :user_id AND t.tanggal = :tanggal
             ORDER BY t.created_at DESC"
        );
        $stmt->execute([
            "user_id" => $user_id,
            "tanggal" => $target_date
        ]);
        $details = $stmt->fetchAll();

        // Format rupiah untuk visualisasi di JSON response
        $formatted_details = [];
        foreach ($details as $row) {
            $formatted_details[] = [
                "id" => $row["id"],
                "nominal" => format_rupiah($row["nominal"]),
                "jenis" => $row["jenis"],
                "keterangan" => escape($row["keterangan"]),
                "nama_kategori" => escape($row["nama_kategori"]),
                "nama_dompet" => escape($row["nama_dompet"]),
                "badge_class" => $row["jenis"] === "Pemasukan" ? "bg-success" : "bg-danger"
            ];
        }

        echo json_encode(["status" => "success", "data" => $formatted_details]);
        exit();
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(["status" => "error", "message" => "Terjadi kesalahan server saat memuat detail."]);
        exit();
    }
}

// -------------------------------------------------------------------------
// Load data agregasi alur kas harian untuk FullCalendar events
// -------------------------------------------------------------------------
$calendar_events = [];
try {
    $stmt_events = $pdo->prepare(
        "SELECT tanggal, jenis, SUM(nominal) AS total_nominal
         FROM transaksi
         WHERE user_id = :user_id
         GROUP BY tanggal, jenis"
    );
    $stmt_events->execute(["user_id" => $user_id]);
    $events_data = $stmt_events->fetchAll();

    foreach ($events_data as $row) {
        $is_income = ($row["jenis"] === "Pemasukan");
        $prefix = $is_income ? "+" : "-";
        
        // Custom background & text colors berdasarkan Light/Dark mode akan disesuaikan oleh FullCalendar & CSS
        // Kita set warna event secara dinamis di sini
        $color = $is_income ? "#10b981" : "#ef4444"; // Hijau Emerald & Merah Crimson

        $calendar_events[] = [
            "title" => $prefix . " " . number_format($row["total_nominal"], 0, ",", "."),
            "start" => $row["tanggal"],
            "color" => $color,
            "allDay" => true,
            // Simpan metadata tambahan untuk aksi klik
            "extendedProps" => [
                "date" => $row["tanggal"],
                "jenis" => $row["jenis"]
            ]
        ];
    }
} catch (\PDOException $e) {
    error_log($e->getMessage());
    $error_msg = "Gagal memuat data alur kas kalender.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Alur Kas - DompetKu</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- FullCalendar v6 CSS & JS (Ubah menggunakan bundel terpadu index.global.min.js) -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        #calendar-container {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .modal-table th {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary bg-gradient shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php"><i class="bi bi-wallet2 me-2"></i> DompetKu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-3">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="budgeting.php">Anggaran</a></li>
                    <li class="nav-item"><a class="nav-link" href="target_tabungan.php">Target Tabungan</a></li>
                    <li class="nav-item"><a class="nav-link" href="dompet.php">Dompet</a></li>
                    <li class="nav-item"><a class="nav-link active font-bold" href="kalender.php">Kalender</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-1" href="admin_dashboard.php">
                            <span class="badge bg-warning text-dark" style="font-size: 0.7rem; padding: 3px 7px; border-radius: 6px;">
                                <i class="bi bi-shield-fill me-1"></i>Admin
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item text-white me-3 d-flex align-items-center gap-2">
                        <?php if (!empty($_SESSION["foto_profile"]) && file_exists(__DIR__ . "/uploads/profile/" . $_SESSION["foto_profile"])): ?>
                            <img src="uploads/profile/<?= escape($_SESSION["foto_profile"]) ?>" alt="Avatar" class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-5"></i>
                        <?php endif; ?>
                        <span>Halo, <strong><?= escape($username) ?></strong></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm me-2 d-flex align-items-center gap-1" href="https://t.me/Bagas_Dompetku_bot" target="_blank">
                            <i class="bi bi-telegram"></i> Bot Telegram
                        </a>
                    </li>
                    <li class="nav-item"><a class="btn btn-light btn-sm text-primary" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php display_flash_message(); ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4"><?= $error_msg ?></div>
        <?php endif; ?>

        <!-- Informasi & Panduan Kalender -->
        <div class="card p-4 shadow-sm border-0 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="font-bold mb-1"><i class="bi bi-calendar3 text-primary me-2"></i>Kalender Alur Kas</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">
                        Pantau sirkulasi finansial harian Anda. Klik pada tanggal tertentu untuk melihat rincian transaksi lengkap di hari itu.
                    </p>
                </div>
                <div class="col-md-4 mt-3 mt-md-0 d-flex justify-content-md-end gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-dot bg-success"></span>
                        <small class="fw-semibold">Total Pemasukan</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-dot bg-danger"></span>
                        <small class="fw-semibold">Total Pengeluaran</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kalender Render Container -->
        <div class="card p-4 shadow-sm border-0" id="calendar-container">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal Detail Transaksi Harian -->
    <div class="modal fade" id="modalDetailTransaksi" tabindex="-1" aria-labelledby="modalDetailTransaksiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0" style="border-radius:18px;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title font-bold d-flex align-items-center gap-2" id="modalDetailTransaksiLabel">
                        <i class="bi bi-card-list text-primary"></i> Rincian Transaksi Tanggal <span id="target-modal-date" class="text-primary font-bold"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="table-responsive">
                        <table class="table align-middle table-hover modal-table mb-0">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>Kategori</th>
                                    <th>Keterangan</th>
                                    <th>Dompet</th>
                                    <th class="text-end">Nominal</th>
                                    <th class="text-center" width="120">Jenis</th>
                                </tr>
                            </thead>
                            <tbody id="detail-rows-container">
                                <!-- Baris transaksi dimuat secara dinamis via JS Fetch API -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-secondary w-100 py-2" style="border-radius:10px;" data-bs-modal="dismiss" data-bs-dismiss="modal">Tutup Detail</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- FullCalendar Logic -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const calendarEl = document.getElementById("calendar");
            
            // Konfigurasi Inisialisasi FullCalendar
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: "dayGridMonth",
                locale: "id", // Gunakan lokalisasi bahasa Indonesia
                headerToolbar: {
                    left: "prev,next today",
                    center: "title",
                    right: "dayGridMonth,dayGridWeek"
                },
                events: <?= json_encode($calendar_events) ?>,
                
                // Saat tanggal atau hari diklik
                dateClick: function(info) {
                    showTransactionDetails(info.dateStr);
                },
                
                // Saat event total nominal diklik
                eventClick: function(info) {
                    info.jsEvent.preventDefault();
                    const targetDate = info.event.extendedProps.date;
                    showTransactionDetails(targetDate);
                }
            });
            
            calendar.render();
            
            // Mengambil detail data via AJAX
            function showTransactionDetails(dateStr) {
                // Konversi tanggal ke format Indonesia yang rapi (contoh: 17 Juli 2026)
                const dateObj = new Date(dateStr);
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                const formattedDateIndo = dateObj.toLocaleDateString('id-ID', options);
                
                document.getElementById("target-modal-date").innerText = formattedDateIndo;
                
                const rowsContainer = document.getElementById("detail-rows-container");
                rowsContainer.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Memuat rincian...</td></tr>';
                
                // Tampilkan Modal
                const myModal = new bootstrap.Modal(document.getElementById('modalDetailTransaksi'));
                myModal.show();
                
                // Fetch detail
                fetch(`kalender.php?action=get_details&date=${dateStr}`)
                    .then(response => response.json())
                    .then(res => {
                        if (res.status === "success") {
                            if (res.data.length === 0) {
                                rowsContainer.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada transaksi yang tercatat pada hari ini.</td></tr>';
                            } else {
                                let html = "";
                                res.data.forEach((item, index) => {
                                    const sign = item.jenis === "Pemasukan" ? "+" : "-";
                                    const textClass = item.jenis === "Pemasukan" ? "text-success" : "text-danger";
                                    
                                    html += `
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td><span class="badge bg-light text-dark py-2 px-3 border" style="font-size:0.78rem;">${item.nama_kategori}</span></td>
                                            <td class="fw-semibold text-wrap">${item.keterangan}</td>
                                            <td><i class="bi bi-wallet2 text-secondary me-1"></i>${item.nama_dompet}</td>
                                            <td class="text-end fw-bold ${textClass}">${sign} ${item.nominal}</td>
                                            <td class="text-center">
                                                <span class="badge ${item.badge_class} py-2 px-3 text-uppercase" style="font-size: 0.72rem; border-radius: 8px;">
                                                    ${item.jenis}
                                                </span>
                                            </td>
                                        </tr>
                                    `;
                                });
                                rowsContainer.innerHTML = html;
                            }
                        } else {
                            rowsContainer.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${res.message}</td></tr>`;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        rowsContainer.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle-fill me-1"></i> Gagal terhubung ke server.</td></tr>';
                    });
            }
        });
    </script>
</body>
</html>
