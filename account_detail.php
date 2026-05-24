<?php
include 'config/database.php';
$halaman_sekarang = 'account_detail.php';
include 'includes/header.php';

// Pastikan user sudah login
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

// AMBIL DATA USER TERBARU (SELECT AMAN)
$stmt_user = $conn->prepare("SELECT * FROM user WHERE UserID = ?");
$stmt_user->bind_param("i", $userID);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
?>

<div class="container mx-auto px-4 py-10 max-w-3xl">
    <div class="glass p-10 rounded-[3rem] shadow-2xl relative overflow-hidden border border-white/5">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-black text-white tracking-widest uppercase italic bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent">Detail Akun Saya</h2>
            <p class="text-slate-500 text-xs mt-1 uppercase tracking-wider">Informasi Kredensial Pengguna Terdaftar</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12 items-center">
            <div class="flex flex-col items-center space-y-4">
                <div class="w-48 h-48 rounded-full border-4 border-emerald-500 overflow-hidden bg-slate-800 shadow-2xl">
                    <img src="<?= (!empty($user['FotoProfil']) && file_exists('assets/profiles/' . $user['FotoProfil'])) ? 'assets/profiles/' . $user['FotoProfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['Username']) . '&background=random' ?>" class="w-full h-full object-cover">
                </div>
                <div class="px-4 py-1 bg-slate-900/80 rounded-full border border-white/5 text-[10px] text-emerald-400 font-bold uppercase tracking-widest">
                    Role: <?= htmlspecialchars($user['Role'] ?? 'user') ?>
                </div>
            </div>

            <div class="md:col-span-2 space-y-6">
                <div class="bg-slate-900/40 p-5 rounded-2xl border border-slate-800/80">
                    <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">Nama Lengkap</span>
                    <div class="text-white font-bold text-lg mt-1 flex items-center gap-2">
                        <?= htmlspecialchars($user['NamaLengkap']) ?>
                        <?php
                        $status = getVerificationStatus($user);
                        if ($status === 'admin'): ?>
                            <img src="assets/icons/verif2.png" class="w-5 h-5" title="Official Admin">
                        <?php elseif ($status === 'verified'): ?>
                            <img src="assets/icons/verif1.png" class="w-5 h-5" title="Verified Artist">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-slate-900/40 p-5 rounded-2xl border border-slate-800/80">
                    <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">Username</span>
                    <div class="text-slate-200 font-mono text-base mt-1 font-bold">
                        @<?= htmlspecialchars($user['Username']) ?>
                    </div>
                </div>

                <div class="bg-slate-900/40 p-5 rounded-2xl border border-slate-800/80">
                    <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">Alamat Email</span>
                    <div class="text-emerald-400 font-medium text-base mt-1">
                        <?= htmlspecialchars($user['Email'] ?? 'Email belum diatur') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-10 pt-6 border-t border-white/5 flex justify-end">
            <a href="profile.php" class="bg-gradient-to-r from-blue-600 to-emerald-600 text-xs font-black tracking-widest text-white px-6 py-3.5 rounded-xl hover:scale-105 transition-all shadow-md uppercase">Ke Pengaturan Profil 🛠️</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>