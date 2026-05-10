<?php

session_start();

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

if(isset($_POST['simpan'])){

    $nama_gpu  = $_POST['nama_gpu'];
    $harga     = $_POST['harga'];
    $kebutuhan = $_POST['kebutuhan'];

    mysqli_query($conn,

    "INSERT INTO gpu_services VALUES(
        '',
        '$nama_gpu',
        '$harga',
        '$kebutuhan'
    )");

    header("Location: dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Tambah Layanan GPU</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <!-- NAVBAR -->
    <div class="navbar">

        <div class="logo">
            NUSAGRID
        </div>

        <div class="nav-menu">

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="tambah.php">
                Tambah Layanan
            </a>

            <a href="logout.php"
            class="btn">
                Logout
            </a>

        </div>

    </div>

    <!-- FORM -->
    <div class="form-container">

        <h2>
            Tambah Layanan GPU
        </h2>

        <p style="text-align:center; margin-bottom:25px; color:#cbd5e1;">
            Tambahkan layanan cloud GPU baru
            ke sistem NUSAGRID
        </p>

        <form method="POST">

            <!-- NAMA GPU -->
            <div class="input-group">

                <label>
                    Nama GPU
                </label>

                <input type="text"
                name="nama_gpu"
                placeholder="Contoh: NVIDIA RTX 4090"
                required>

            </div>

            <!-- HARGA -->
            <div class="input-group">

                <label>
                    Harga
                </label>

                <input type="text"
                name="harga"
                placeholder="Contoh: Rp 30.000 / jam"
                required>

            </div>

            <!-- KEBUTUHAN -->
            <div class="input-group">

                <label>
                    Kebutuhan
                </label>

                <textarea
                name="kebutuhan"
                placeholder="Contoh: Rendering, AI Training, Deep Learning"
                required></textarea>

            </div>

            <!-- BUTTON -->
            <div style="display:flex; gap:15px;">

                <a href="dashboard.php"
                class="btn btn-dark"
                style="width:50%; text-align:center;">
                    Kembali
                </a>

                <button type="submit"
                name="simpan"
                class="btn"
                style="width:50%;">
                    Simpan
                </button>

            </div>

        </form>

    </div>

    <!-- FOOTER -->
    <div class="footer">

        © 2026 NUSAGRID - Cloud GPU Service

    </div>

</div>

</body>
</html>