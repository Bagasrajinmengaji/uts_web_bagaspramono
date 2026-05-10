<?php

session_start();
include 'koneksi.php';

if(isset($_POST['login'])){

    $user_input = $_POST['user_input'];
    $password   = $_POST['password'];

    $query = mysqli_query($conn,

    "SELECT * FROM users
    WHERE
    (username='$user_input'
    OR email='$user_input')
    AND password='$password'"

    );

    $cek = mysqli_num_rows($query);

    if($cek > 0){

        $_SESSION['login'] = true;
        $_SESSION['user']  = $user_input;

        header("Location: dashboard.php");
        exit;

    } else {

        echo "
        <script>
            alert('Login gagal');
        </script>
        ";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Login NUSAGRID</title>

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
                Selamat Datang Kembali
            </h1>

            <p>
                Masuk untuk mengelola layanan
                Cloud GPU NVIDIA Anda.
            </p>

        </div>

        <img src="assets/img/rtx4090.png">

    </div>

    <!-- RIGHT -->
    <div class="auth-right">

        <div class="form-container">

            <h2>Login</h2>

            <p>
                Masuk ke akun Anda
            </p>

            <form method="POST">

                <div class="input-group">

                    <label>
                        Username atau Email
                    </label>

                    <input type="text"
                    name="user_input"
                    placeholder="Masukkan username atau email"
                    required>

                </div>

                <div class="input-group">

                    <label>
                        Password
                    </label>

                    <input type="password"
                    name="password"
                    placeholder="Masukkan password"
                    required>

                </div>

                <button type="submit"
                name="login"
                class="btn auth-btn">
                    Login
                </button>

            </form>

            <div class="auth-footer">

                Belum punya akun?

                <a href="register.php">
                    Register
                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>