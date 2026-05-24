<?php 
include 'config/database.php'; // 1. Koneksi dulu baru header
include 'includes/header.php'; 
?>

<div class="container mx-auto px-4 flex justify-center items-center min-h-[80vh]">
    <div class="glass p-8 rounded-2xl w-full max-w-md">
        <h2 class="text-3xl font-bold mb-6 text-center text-white">Buat Akun</h2>

        <?php
        if (isset($_POST['register'])) {
            $username = trim($_POST['username']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $email = $_POST['email'];
            $nama = $_POST['nama_lengkap'];
            $alamat = $_POST['alamat'];

            // Cek dulu apakah Username atau Email sudah ada di database
            $check = $conn->prepare("SELECT UserID FROM user WHERE Username = ? OR Email = ?");
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                echo "<div class='bg-red-500/20 text-red-400 p-3 rounded-lg mb-4 text-center border border-red-500/50'>Username atau Email sudah dipakai!</div>";
            } else {
                $stmt = $conn->prepare("INSERT INTO user (Username, Password, Email, NamaLengkap, Alamat, Role) VALUES (?, ?, ?, ?, ?, 'user')");
                $stmt->bind_param("sssss", $username, $password, $email, $nama, $alamat);

                if ($stmt->execute()) {
                    echo "<div class='bg-emerald-500/20 text-emerald-400 p-3 rounded-lg mb-4 text-center border border-emerald-500/50'>Registrasi Berhasil! <a href='login.php' class='underline font-bold'>Login sekarang</a></div>";
                } else {
                    echo "<div class='bg-red-500/20 text-red-400 p-3 rounded-lg mb-4 text-center'>Terjadi kesalahan teknis.</div>";
                }
                $stmt->close();
            }
            $check->close();
        }
        ?>

        <form action="" method="post" class="space-y-4">
            <input type="text" name="username" placeholder="Username" class="w-full bg-slate-900/50 border border-slate-700/50 p-4 rounded-2xl outline-none focus:border-blue-500 text-white transition" required>
            
            <div class="relative w-full">
                <input type="password" id="password" name="password" placeholder="••••••••"
                    class="w-full bg-slate-900/50 border border-slate-700/50 p-4 pr-12 rounded-2xl outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition text-white" required>
                <button type="button" onpointerdown="toggleView(event)" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white transition">
                    <img id="eye-icon" src="assets/icons/eye-close.png" class="w-5 h-5 object-contain" alt="Toggle">
                </button>
            </div>

            <input type="email" name="email" placeholder="Email" class="w-full bg-slate-900/50 border border-slate-700/50 p-4 rounded-2xl outline-none focus:border-blue-500 text-white transition" required>
            <input type="text" name="nama_lengkap" placeholder="Nama Lengkap" class="w-full bg-slate-900/50 border border-slate-700/50 p-4 rounded-2xl outline-none focus:border-blue-500 text-white transition" required>
            <textarea name="alamat" placeholder="Alamat" class="w-full bg-slate-900/50 border border-slate-700/50 p-4 rounded-2xl outline-none focus:border-blue-500 text-white transition resize-none h-24"></textarea>
            
            <button type="submit" name="register" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 p-4 rounded-2xl font-bold text-white shadow-lg transition-all transform hover:scale-[1.02]">Daftar Akun</button>
        </form>
    </div>
</div>

<script>
    let isShow = false;
    function toggleView(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('password');
        const icon = document.getElementById('eye-icon');
        if (!isShow) {
            input.type = "text";
            icon.src = "assets/icons/eye-open.png"; //
            isShow = true;
        } else {
            input.type = "password";
            icon.src = "assets/icons/eye-close.png";
            isShow = false;
        }
    }
</script>