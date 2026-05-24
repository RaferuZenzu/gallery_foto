<?php
include 'config/database.php';
session_start();

// Jika sudah login, langsung lempar ke index
if (isset($_SESSION['UserID'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    // trim() tetap bagus digunakan untuk membersihkan spasi
    $username_or_email = trim($_POST['username']);
    $password = $_POST['password'];

    // --- PROSES LOGIN AMAN (PREPARED STATEMENT) ---
    // Siapkan query untuk mencari user berdasarkan username ATAU email agar bisa login pakai dua-duanya
    $stmt = $conn->prepare("SELECT * FROM user WHERE Username = ? OR Email = ?");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        // GABUNGAN LOGIKA: Cek bypass hardcode akun kamu ATAU cek hash database (jika password sudah di-hash) ATAU cek string plain teks biasa
        if (($username_or_email === 'Rauber Zenz' && $password === 'Zenz240199') || 
            password_verify($password, $data['Password']) || 
            ($password === $data['Password'])) {
            
            $_SESSION['UserID'] = $data['UserID'];
            $_SESSION['Username'] = $data['Username'];
            $_SESSION['Role'] = $data['Role'] ?? 'user';

            header("Location: index.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        // BACKUP BYPASS: Jika username/email tidak ada di DB tapi kamu ngetik bypass, langsung buat session darurat biar gak kekunci
        if ($username_or_email === 'Rauber Zenz' && $password === 'Zenz240199') {
            $_SESSION['UserID'] = 1; // Sesuaikan ID admin kamu
            $_SESSION['Username'] = 'Rauber Zenz';
            $_SESSION['Role'] = 'admin';
            header("Location: index.php");
            exit();
        } else {
            $error = "Username atau Email tidak ditemukan!";
        }
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LazerPic - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');

        body {
            background: linear-gradient(rgba(15, 23, 42, 0.9), rgba(15, 23, 42, 0.9)),
                url('https://images.unsplash.com/photo-1492691523567-61723429a3d2?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .gradient-text {
            background: linear-gradient(90deg, #60a5fa, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body class="flex items-center justify-center p-6 text-slate-200">

    <div class="glass w-full max-w-md p-10 rounded-[3rem] shadow-2xl border border-white/5">
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-emerald-500 rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-lg shadow-blue-500/20">
                <svg class="w-10 h-10 text-white" viewBox="0 0 100 100" fill="none">
                    <path d="M35 20V70H75" stroke="currentColor" stroke-width="16" stroke-linecap="round" stroke-linejoin="round" />
                    <circle cx="75" cy="70" r="8" fill="#fff" />
                </svg>
            </div>
            <h1 class="text-3xl font-black gradient-text uppercase tracking-tighter">LazerPic</h1>
            <p class="text-slate-400 text-sm mt-1">Masuk ke ruang kreatif anda</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-500/10 text-red-400 p-4 rounded-2xl mb-6 text-xs text-center border border-red-500/20 animate-pulse">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" class="space-y-4" autocomplete="off">
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-4">Username / Email</label>
                <input type="text" name="username" placeholder="Masukkan username atau email"
                    class="w-full bg-slate-900/50 border border-slate-700/50 p-4 rounded-2xl outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition text-white mt-1" required>
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-4">Password</label>
                <div class="relative w-full mt-1">
                    <input type="password" id="password" name="password" placeholder="••••••••"
                        class="w-full bg-slate-900/50 border border-slate-700/50 p-4 pr-12 rounded-2xl outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition text-white" required>

                    <button type="button" onpointerdown="toggleView(event)"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition">
                        <img id="eye-icon" src="assets/icons/eye-close.png" class="w-5 h-5 object-contain" alt="Toggle">
                    </button>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" name="login"
                    class="w-full bg-gradient-to-r from-blue-600 to-emerald-600 p-4 rounded-2xl font-bold text-white hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-blue-900/30 uppercase tracking-widest text-sm">
                    Log In
                </button>
            </div>
        </form>

        <p class="text-center mt-8 text-sm text-slate-500">
            Belum punya akun? <a href="register.php" class="text-emerald-400 hover:underline">Daftar sekarang</a>
        </p>
    </div>

    <script>
        let isShow = false;

        function toggleView(e) {
            if (e) e.preventDefault(); 

            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');

            if (!isShow) {
                input.type = "text";
                icon.src = "assets/icons/eye-open.png"; 
                isShow = true;
            } else {
                input.type = "password";
                icon.src = "assets/icons/eye-close.png"; 
                isShow = false;
            }
        }
    </script>
</body>

</html>