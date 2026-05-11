<?php

session_start();

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$data = mysqli_query($conn,
"SELECT * FROM gpu_services ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard NUSAGRID</title>

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

            <a href="logout.php" class="btn">
                Logout
            </a>

        </div>

    </div>

    <!-- DASHBOARD -->
    <div class="dashboard">

        <div class="dashboard-top">

            <div>

                <h2>
                    Dashboard GPU
                </h2>

                <p>
                    Selamat datang,
                    <?= $_SESSION['user']; ?>
                </p>

            </div>

            <a href="tambah.php" class="btn">
                + Tambah Layanan
            </a>

        </div>

        <!-- TABLE -->
        <table>

            <tr>

                <th>No</th>
                <th>Foto</th>
                <th>Nama GPU</th>
                <th>Harga</th>
                <th>Kebutuhan</th>
                <th>Aksi</th>

            </tr>

            <?php

            $no = 1;

            while($row = mysqli_fetch_array($data)){

            ?>

            <tr>

                <td>
                    <?= $no++; ?>
                </td>

                <td>

                    <img
                    src="assets/img/<?= $row['foto']; ?>"
                    alt="<?= $row['nama_gpu']; ?>">

                </td>

                <td>
                    <?= $row['nama_gpu']; ?>
                </td>

                <td>

                    Rp <?= number_format(
                        (int)$row['harga'],
                        0,
                        ',',
                        '.'
                    ); ?> / jam

                </td>

                <td>
                    <?= $row['kebutuhan']; ?>
                </td>

                <td>

                    <a
                    href="edit.php?id=<?= $row['id']; ?>"
                    class="action-btn edit">

                        Edit

                    </a>

                    <a
                    href="hapus.php?id=<?= $row['id']; ?>"
                    class="action-btn delete"
                    onclick="return confirm('Yakin hapus data?')">

                        Hapus

                    </a>

                </td>

            </tr>

            <?php } ?>

        </table>

    </div>

    <!-- FOOTER -->
    <div class="footer">
        © 2026 NUSAGRID Dashboard
    </div>

</div>

</body>
</html>