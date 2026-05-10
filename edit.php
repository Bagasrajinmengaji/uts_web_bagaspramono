<?php
include 'koneksi.php';

$id = $_GET['id'];

$data = mysqli_query($conn,
"SELECT * FROM gpu_services WHERE id='$id'");

$row = mysqli_fetch_array($data);

if(isset($_POST['update'])){

    $nama_gpu = $_POST['nama_gpu'];
    $harga = $_POST['harga'];
    $kebutuhan = $_POST['kebutuhan'];

    mysqli_query($conn,
    "UPDATE gpu_services SET
    nama_gpu='$nama_gpu',
    harga='$harga',
    kebutuhan='$kebutuhan'
    WHERE id='$id'");

    header("Location: dashboard.php");
}
?>

<form method="POST">

<input type="text"
name="nama_gpu"
value="<?= $row['nama_gpu']; ?>">

<input type="text"
name="harga"
value="<?= $row['harga']; ?>">

<textarea name="kebutuhan">
<?= $row['kebutuhan']; ?>
</textarea>

<button type="submit"
name="update">
Update
</button>

</form>