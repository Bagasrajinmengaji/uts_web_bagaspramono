<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "nusagrid";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if(!$conn){
    die("Koneksi gagal");
}

?>