<?php

include 'koneksi.php';

$data = mysqli_query($conn,
"SELECT * FROM gpu_services ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUSAGRID - Cloud GPU Service</title>

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

            <a href="">
                Beranda
            </a>

            <a href="#layanan">
                Layanan
            </a>

            <a href="register.php" class="btn">
                Register
            </a>

        </div>

    </div>

    <!-- HERO -->
    <section class="hero">

        <div class="hero-text">

            <h1>
                Cloud GPU NUSAGRID <br>
                Untuk Performa <span>Tanpa Batas</span>
            </h1>

            <p>
                NUSAGRID menyediakan layanan Cloud GPU
                berbasis NVIDIA untuk kebutuhan
                AI Training, Deep Learning,
                Rendering, Video Editing,
                dan Machine Learning.
            </p>

            <a href="login.php" class="btn">
                Login Admin
            </a>

            <a href="#layanan" class="btn btn-dark">
                GPU Tersedia
            </a>

        </div>

        <div class="hero-image">

            <img src="assets/img/RTX4090.png"
            alt="RTX 4090">

        </div>

    </section>

    <!-- GPU SERVICES -->
    <section id="layanan">

        <h2 class="section-title">
            GPU Yang Tersedia
        </h2>

        <div class="gpu-grid">

            <?php while($row = mysqli_fetch_array($data)) { ?>

            <div class="gpu-card">

                <img
                src="assets/img/<?= $row['foto']; ?>"
                alt="<?= $row['nama_gpu']; ?>">

                <h3>
                    <?= $row['nama_gpu']; ?>
                </h3>

                <p>
                    <?= $row['kebutuhan']; ?>
                </p>

                <div class="price">

                    Rp <?= number_format(
                        (int)$row['harga'],
                        0,
                        ',',
                        '.'
                    ); ?> / jam

                </div>

            </div>

            <?php } ?>

        </div>

    </section>

    <!-- FOOTER -->
    <div class="footer">
        © 2026 NUSAGRID - Cloud GPU Service
    </div>

</div>

</body>
</html>