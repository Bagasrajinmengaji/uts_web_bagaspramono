<?php

include 'koneksi.php';

$id = $_GET['id'];

mysqli_query($conn,
"DELETE FROM gpu_services
WHERE id='$id'");

header("Location: dashboard.php");

?>