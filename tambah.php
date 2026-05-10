<?php
include 'koneksi.php';

if(isset($_POST['simpan'])){

    $nama_gpu = $_POST['nama_gpu'];
    $harga = $_POST['harga'];
    $kebutuhan = $_POST['kebutuhan'];

    mysqli_query($conn,
    "INSERT INTO gpu_services VALUES(
        '',
        '$nama_gpu',
        '$harga',
        '$kebutuhan'
    )");

    header("Location: dashboard.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah GPU</title>
</head>
<body>

<h2>Tambah Layanan GPU</h2>

<form method="POST">

<input type="text"
name="nama_gpu"
placeholder="Nama GPU">

<input type="text"
name="harga"
placeholder="Harga">

<textarea name="kebutuhan"></textarea>

<button type="submit"
name="simpan">
Simpan
</button>

</form>

</body>
</html>