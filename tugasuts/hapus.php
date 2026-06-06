<?php

include 'koneksi.php';

$id = $_GET['id'];

/* AMBIL FOTO DARI DATABASE */
$data = mysqli_query($conn,
"SELECT * FROM gpu_services WHERE id='$id'");

$row = mysqli_fetch_array($data);

/* HAPUS FOTO DI FOLDER */
if(file_exists("assets/img/".$row['foto'])){

    unlink("assets/img/".$row['foto']);

}

/* HAPUS DATA DATABASE */
mysqli_query($conn,
"DELETE FROM gpu_services WHERE id='$id'");

/* KEMBALI KE DASHBOARD */
header("Location: dashboard.php");

?>