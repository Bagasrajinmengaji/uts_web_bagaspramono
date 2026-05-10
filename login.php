<?php
session_start();

include 'koneksi.php';

if(isset($_POST['login'])){

    $user_input = $_POST['user_input'];
    $password   = $_POST['password'];

    $data = mysqli_query($conn,

    "SELECT * FROM users
    WHERE
    (username='$user_input'
    OR email='$user_input')
    AND password='$password'"

    );

    $cek = mysqli_num_rows($data);

    if($cek > 0){

        $_SESSION['login'] = true;
        $_SESSION['user']  = $user_input;

        header("Location: dashboard.php");

    } else {

        echo "Login gagal";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login NUSAGRID</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h2>Login NUSAGRID</h2>

    <form method="POST">

        <input type="text"
        name="user_input"
        placeholder="Username atau Email"
        required>

        <input type="password"
        name="password"
        placeholder="Password"
        required>

        <button type="submit"
        name="login">
        Login
        </button>

    </form>

    <p>
    Belum punya akun?
    <a href="register.php">Register</a>
    </p>

</div>

</body>
</html>