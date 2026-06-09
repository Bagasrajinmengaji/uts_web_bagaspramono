<?php

session_start();

include 'koneksi.php';

if(isset($_POST['login'])){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $cek = mysqli_query($conn,

    "SELECT * FROM users
    WHERE username='$username'
    AND password='$password'"

    );

    if(mysqli_num_rows($cek) > 0){

        $_SESSION['login'] = true;
        $_SESSION['user']  = $username;

        header("Location: dashboard.php");
        exit;

    }else{

        $error = true;

    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Login Admin - NUSAGRID</title>

    <link rel="stylesheet" href="style.css?v=5">

</head>
<body>

<div class="login-wrapper">

    <div class="login-box">

        <h2>
            Login Admin
        </h2>

        <p class="login-subtitle">
            Selamat datang di Dashboard NUSAGRID
        </p>

        <?php if(isset($error)) { ?>

        <div class="error-message">
            Username atau password salah
        </div>

        <?php } ?>

        <form action="" method="POST">

            <!-- USERNAME -->
            <div class="input-group">

                <label>
                    Username
                </label>

                <input
                type="text"
                name="username"
                placeholder="Masukkan username"
                required>

            </div>

            <!-- PASSWORD -->
            <div class="input-group">

                <label>
                    Password
                </label>

                <input
                type="password"
                name="password"
                placeholder="Masukkan password"
                required>

            </div>

            <!-- BUTTON -->
            <button
            type="submit"
            name="login"
            class="btn login-btn">

                Login

            </button>

        </form>

        <div class="login-footer">
            © 2026 NUSAGRID
        </div>

    </div>

</div>

</body>
</html>