<?php

include 'koneksi.php';

if(isset($_POST['register'])){

    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];

    mysqli_query($conn,

    "INSERT INTO users VALUES(
        '',
        '$username',
        '$email',
        '$password'
    )");

    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Register NUSAGRID</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-wrapper">

    <!-- LEFT -->
    <div class="auth-left">

        <div>

            <div class="logo-auth">
                NUSAGRID
            </div>

            <h1>
                Buat Akun Baru
            </h1>

            <p>
                Daftar sekarang dan gunakan
                layanan Cloud GPU terbaik.
            </p>

        </div>

        <img src="assets/img/rtx4090.png">

    </div>

    <!-- RIGHT -->
    <div class="auth-right">

        <div class="form-container">

            <h2>Register</h2>

            <p>
                Buat akun baru
            </p>

            <form method="POST">

                <div class="input-group">

                    <label>
                        Username
                    </label>

                    <input type="text"
                    name="username"
                    placeholder="Masukkan username"
                    required>

                </div>

                <div class="input-group">

                    <label>
                        Email
                    </label>

                    <input type="email"
                    name="email"
                    placeholder="Masukkan email"
                    required>

                </div>

                <div class="input-group">

                    <label>
                        Password
                    </label>

                    <input type="password"
                    name="password"
                    placeholder="Buat password"
                    required>

                </div>

                <button type="submit"
                name="register"
                class="btn auth-btn">
                    Register
                </button>

            </form>

            <div class="auth-footer">

                Sudah punya akun?

                <a href="login.php">
                    Login
                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>