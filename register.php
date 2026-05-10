<?php
include 'koneksi.php';

if(isset($_POST['register'])){

    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    mysqli_query($conn, "INSERT INTO users VALUES(
        '',
        '$username',
        '$email',
        '$password'
    )");

    header("Location: login.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register NUSAGRID</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h2>Register NUSAGRID</h2>

    <form method="POST">

        <input type="text"
        name="username"
        placeholder="Username"
        required>

        <input type="email"
        name="email"
        placeholder="Email"
        required>

        <input type="password"
        name="password"
        placeholder="Password"
        required>

        <button type="submit"
        name="register">
        Register
        </button>

    </form>

    <p>
    Sudah punya akun?
    <a href="login.php">Login</a>
    </p>

</div>

</body>
</html>