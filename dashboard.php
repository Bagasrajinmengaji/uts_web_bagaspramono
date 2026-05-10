<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: login.php");
}

include 'koneksi.php';

$data = mysqli_query($conn,
"SELECT * FROM gpu_services");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard NUSAGRID</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

<h2>Dashboard NUSAGRID</h2>

<a href="tambah.php">Tambah Layanan GPU</a>
<a href="logout.php">Logout</a>

<table border="1" cellpadding="10">

<tr>
    <th>No</th>
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

<td><?= $no++; ?></td>

<td><?= $row['nama_gpu']; ?></td>

<td><?= $row['harga']; ?></td>

<td><?= $row['kebutuhan']; ?></td>

<td>
    <a href="edit.php?id=<?= $row['id']; ?>">Edit</a>

    <a href="hapus.php?id=<?= $row['id']; ?>">
    Hapus
    </a>
</td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>