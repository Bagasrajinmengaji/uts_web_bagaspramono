<?php

session_start();

if(!isset($_SESSION['login'])){
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

/* =========================
   SIMPAN DATA GPU
========================= */

if(isset($_POST['simpan'])){

    $nama_gpu  = $_POST['nama_gpu'];
    $harga     = $_POST['harga'];
    $kebutuhan = $_POST['kebutuhan'];

    /* FOTO */
    $foto      = $_FILES['foto']['name'];
    $tmp       = $_FILES['foto']['tmp_name'];

    /* EXTENSION */
    $extensi_valid = ['png','jpg','jpeg'];

    $extensi = strtolower(
        pathinfo($foto, PATHINFO_EXTENSION)
    );

    /* CEK EXTENSI */
    if(!in_array($extensi, $extensi_valid)){

        echo "
        <script>
            alert('Format gambar harus PNG/JPG/JPEG');
        </script>
        ";

    }else{

        /* NAMA FOTO BARU */
        $nama_foto_baru =
        time().'_'.$foto;

        /* UPLOAD FOTO */
        move_uploaded_file(
            $tmp,
            'assets/img/'.$nama_foto_baru
        );

        /* INSERT DATABASE */
        mysqli_query($conn,

        "INSERT INTO gpu_services
        (
            nama_gpu,
            harga,
            kebutuhan,
            foto
        )

        VALUES

        (
            '$nama_gpu',
            '$harga',
            '$kebutuhan',
            '$nama_foto_baru'
        )"

        );

        echo "
        <script>
            alert('Layanan GPU berhasil ditambahkan');
            window.location='dashboard.php';
        </script>
        ";

    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Tambah GPU</title>

    <link rel="stylesheet" href="style.css?v=2">

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
                placeholder="Contoh: NVIDIA RTX 4090"
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
                placeholder="Contoh: 40000"
                required>

            </div>

            <!-- KEBUTUHAN -->
            <div class="input-group">

                <label>
                    Kebutuhan GPU
                </label>

                <textarea
                name="kebutuhan"
                placeholder="Deskripsi kebutuhan GPU"
                required></textarea>

            </div>

            <!-- FOTO -->
            <div class="input-group">

                <label>
                    Upload Foto GPU
                </label>

                <input
                type="file"
                name="foto"
                accept=".png,.jpg,.jpeg"
                required>

            </div>

            <!-- BUTTON -->
            <button
            type="submit"
            name="simpan"
            class="btn">

                Simpan Layanan

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