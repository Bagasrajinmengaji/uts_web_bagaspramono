<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "nusagrid";

$conn = mysqli_connect($host, $user, $pass, $db);

if(!$conn){
    die("Koneksi gagal");
}

?>