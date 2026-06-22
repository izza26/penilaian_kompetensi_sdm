<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Penilaian Kompetensi SDM</title>

    <link rel="stylesheet" href="../assets/css/login.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-container">

    <!-- LEFT SIDE -->
    <div class="left-panel">

        <div class="overlay"></div>

        <div class="left-content">

            <img src="../assets/img/logo-museum.png" alt="Museum Geologi" class="logo">

            <h1>Sistem Penilaian Kompetensi SDM</h1>

            <p>
                Museum Geologi Bandung
            </p>

            <span>
                Platform penilaian kompetensi pegawai berbasis evidence dan standar kompetensi.
            </span>

        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="right-panel">

        <div class="login-box">

            <h2>Selamat Datang</h2>

            <p>
                Silakan masuk menggunakan akun Anda.
            </p>

            <form action="proses_login.php" method="POST">

                <div class="input-group">
                    <label>Username</label>
                    <input
                        type="text"
                        name="username"
                        placeholder="Masukkan username"
                        required
                    >
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                    >
                </div>

                <button type="submit" class="login-btn">
                    Masuk
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>