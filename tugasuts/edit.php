<?php

session_start();

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

/* AMBIL ID */
$id = $_GET['id'];

/* AMBIL DATA GPU */
$data = mysqli_query($conn,
"SELECT * FROM gpu_services WHERE id='$id'");

$row = mysqli_fetch_array($data);

/* UPDATE DATA */
if(isset($_POST['update'])){

    $nama_gpu  = $_POST['nama_gpu'];
    $harga     = $_POST['harga'];
    $kebutuhan = $_POST['kebutuhan'];

    /* FOTO BARU */
    if($_FILES['foto']['name'] != ''){

        $foto_baru = $_FILES['foto']['name'];
        $tmp       = $_FILES['foto']['tmp_name'];

        $nama_foto_baru =
        time().'_'.$foto_baru;

        move_uploaded_file(
            $tmp,
            'assets/img/'.$nama_foto_baru
        );

        /* HAPUS FOTO LAMA */
        if(file_exists(
            'assets/img/'.$row['foto']
        )){

            unlink(
                'assets/img/'.$row['foto']
            );

        }

        mysqli_query($conn,

        "UPDATE gpu_services SET

        nama_gpu='$nama_gpu',
        harga='$harga',
        kebutuhan='$kebutuhan',
        foto='$nama_foto_baru'

        WHERE id='$id'"

        );

    }else{

        mysqli_query($conn,

        "UPDATE gpu_services SET

        nama_gpu='$nama_gpu',
        harga='$harga',
        kebutuhan='$kebutuhan'

        WHERE id='$id'"

        );

    }

    echo "
    <script>
        alert('Data berhasil diupdate');
        window.location='dashboard.php';
    </script>
    ";

}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Edit GPU</title>

    <link rel="stylesheet"
    href="style.css?v=10">

</head>
<body>

<div class="container">

    <!-- NAVBAR -->
    <div class="navbar">

        <div class="logo">
            NUSAGRID
        </div>

        <div class="nav-menu">

            <a href="index.php">
                Dashboard
            </a>

            <a href="dashboard.php">
                Kelola GPU
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
            Edit Layanan GPU
        </h2>

        <form
        action=""
        method="POST"
        enctype="multipart/form-data">

            <!-- NAMA GPU -->
            <div class="input-group">

                <label>
                    Nama GPU
                </label>

                <input
                type="text"
                name="nama_gpu"
                value="<?= $row['nama_gpu']; ?>"
                required>

            </div>

            <!-- HARGA -->
            <div class="input-group">

                <label>
                    Harga per Jam
                </label>

                <input
                type="number"
                name="harga"
                value="<?= $row['harga']; ?>"
                required>

            </div>

            <!-- KEBUTUHAN -->
            <div class="input-group">

                <label>
                    Kebutuhan GPU
                </label>

                <textarea
                name="kebutuhan"
                required><?= $row['kebutuhan']; ?></textarea>

            </div>

            <!-- FOTO LAMA -->
            <div class="input-group">

                <label>
                    Foto Saat Ini
                </label>

                <br><br>

                <img
                src="assets/img/<?= $row['foto']; ?>"
                alt=""
                style="
                width:220px;
                border-radius:14px;
                ">

            </div>

            <!-- FOTO BARU -->
            <div class="input-group">

                <label>
                    Upload Foto Baru
                </label>

                <input
                type="file"
                name="foto"
                accept=".png,.jpg,.jpeg">

            </div>

            <!-- BUTTON -->
            <button
            type="submit"
            name="update"
            class="btn">

                Update Data

            </button>

        </form>

    </div>

    <!-- FOOTER -->
    <div class="footer">
        © 2026 NUSAGRID Dashboard
    </div>

</div>

</body>
</html>