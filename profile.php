<?php
include 'config/database.php';
$halaman_sekarang = 'profile.php';
include 'includes/header.php';

// Pastikan user sudah login
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];
$error_password = ""; // Variabel penampung pesan error penanganan password

if (isset($_POST['save_profile'])) {
    $namaLengkap = $_POST['nama_lengkap'];
    $username    = $_POST['username'];
    $cropped_image = $_POST['cropped_image'];
    
    // Ambil input form password keamanan baru
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    // --- FITUR BARU: LOGIKA VALIDASI DAN UBAH PASSWORD ---
    if (!empty($old_password) || !empty($new_password)) {
        if (empty($old_password) || empty($new_password)) {
            $error_password = "Untuk mengganti password, isilah password lama DAN password baru!";
        } else {
            // Ambil hash password saat ini di database untuk diverifikasi
            $stmt_chk = $conn->prepare("SELECT Password FROM user WHERE UserID = ?");
            $stmt_chk->bind_param("i", $userID);
            $stmt_chk->execute();
            $res_chk = $stmt_chk->get_result()->fetch_assoc();
            $stmt_chk->close();

            if ($res_chk) {
                // Mendukung pengecekan password hash grypt maupun string plain teks agar anti-gagal
                if (password_verify($old_password, $res_chk['Password']) || ($old_password === $res_chk['Password'])) {
                    // Enkripsi password baru dengan standar keamanan tinggi brypt
                    $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
                    
                    $stmt_up_pw = $conn->prepare("UPDATE user SET Password = ? WHERE UserID = ?");
                    $stmt_up_pw->bind_param("si", $hashed_new_password, $userID);
                    $stmt_up_pw->execute();
                    $stmt_up_pw->close();
                } else {
                    $error_password = "Konfirmasi Password Lama Salah! Perubahan ditolak.";
                }
            }
        }
    }

    // Eksekusi update profile jika tidak ada kendala/error password lama
    if (empty($error_password)) {
        // 1. PROSES UPDATE FOTO (Jika ada gambar baru)
        if (!empty($cropped_image)) {
            list($type, $data) = explode(';', $cropped_image);
            list(, $data)      = explode(',', $data);
            $data = base64_decode($data);

            $nama_file = "PP_" . $userID . "_" . time() . ".png";
            $folder_path = "assets/profiles/";

            if (!is_dir($folder_path)) {
                mkdir($folder_path, 0777, true);
            }

            if (file_put_contents($folder_path . $nama_file, $data)) {
                // Update foto secara aman
                $stmt_foto = $conn->prepare("UPDATE user SET FotoProfil = ? WHERE UserID = ?");
                $stmt_foto->bind_param("si", $nama_file, $userID);
                $stmt_foto->execute();
                $stmt_foto->close();
            }
        }

        // 2. UPDATE DATA TEKS (Nama & Username)
        $stmt_teks = $conn->prepare("UPDATE user SET NamaLengkap = ?, Username = ? WHERE UserID = ?");
        $stmt_teks->bind_param("ssi", $namaLengkap, $username, $userID);

        if ($stmt_teks->execute()) {
            // Update session agar perubahan langsung terlihat
            $_SESSION['Username'] = $username;

            echo "<script>
                alert('Profil berhasil diperbarui!');
                window.location.href = 'index.php';
            </script>";
        }
        $stmt_teks->close();
        exit();
    }
}

// 3. AMBIL DATA USER TERBARU (SELECT AMAN)
$stmt_user = $conn->prepare("SELECT * FROM user WHERE UserID = ?");
$stmt_user->bind_param("i", $userID);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<div class="container mx-auto px-4 py-10 max-w-4xl">
    <div class="glass p-10 rounded-[3rem] shadow-2xl">
        
        <?php if (!empty($error_password)): ?>
            <div class="bg-red-500/10 text-red-400 p-4 rounded-2xl mb-6 text-xs text-center border border-red-500/20">
                <?= $error_password ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" class="grid grid-cols-1 md:grid-cols-3 gap-12" autocomplete="off">
            <div class="flex flex-col items-center space-y-4">
                <div class="relative w-48 h-48 group cursor-pointer">
                    <div class="w-full h-full rounded-full border-4 border-emerald-500 overflow-hidden bg-slate-800 shadow-2xl">
                        <img id="avatarPreview" src="<?= (!empty($user['FotoProfil']) && file_exists('assets/profiles/' . $user['FotoProfil'])) ? 'assets/profiles/' . $user['FotoProfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['Username']) . '&background=random' ?>" class="w-full h-full object-cover">
                    </div>
                    <label for="fileInput" class="absolute inset-0 bg-black/60 rounded-full opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white font-bold text-xs">GANTI FOTO</label>
                    <input type="file" id="fileInput" class="hidden" accept="image/*">
                </div>
                <p class="text-[10px] text-slate-500 uppercase tracking-[0.2em] font-bold">Profile Picture</p>
            </div>

            <div class="md:col-span-2 space-y-6">
                <input type="hidden" name="cropped_image" id="cropped_image">
                <div>
                    <label class="text-xs text-slate-400 font-bold ml-2 tracking-widest flex items-center gap-2">
                        NAMA LENGKAP
                        <?php
                        // Menggunakan fungsi yang kita buat di database.php sebelumnya
                        $status = getVerificationStatus($user);
                        if ($status === 'admin'): ?>
                            <img src="assets/icons/verif2.png" class="w-4 h-4" title="Official Admin">
                        <?php elseif ($status === 'verified'): ?>
                            <img src="assets/icons/verif1.png" class="w-4 h-4" title="Verified Artist">
                        <?php endif; ?>
                    </label>
                    <input type="text" name="nama_lengkap" value="<?= $user['NamaLengkap'] ?>" class="w-full bg-slate-900 border border-slate-700 p-4 rounded-2xl mt-1 focus:border-emerald-500 outline-none transition text-white" required>
                </div>
                <div>
                    <label class="text-xs text-slate-400 font-bold ml-2 tracking-widest">USERNAME</label>
                    <input type="text" name="username" value="<?= $user['Username'] ?>" class="w-full bg-slate-900 border border-slate-700 p-4 rounded-2xl mt-1 focus:border-emerald-500 outline-none transition text-white" required>
                </div>

                <div class="p-6 bg-slate-950/50 rounded-[2rem] border border-white/5 space-y-4">
                    <h3 class="text-[10px] font-black tracking-widest text-slate-400 uppercase">🛡️ Ganti Kata Sandi Keamanan</h3>
                    
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2">Password Lama</label>
                        <input type="password" name="old_password" placeholder="Wajib masukkan password sekarang" class="w-full bg-slate-900 border border-slate-800 p-3.5 rounded-xl mt-1 focus:border-blue-500 outline-none transition text-white text-sm">
                    </div>
                    
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-2">Password Baru</label>
                        <input type="password" name="new_password" placeholder="Masukkan password baru akun" class="w-full bg-slate-900 border border-slate-800 p-3.5 rounded-xl mt-1 focus:border-blue-500 outline-none transition text-white text-sm">
                    </div>
                </div>

                <button type="submit" name="save_profile" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 p-4 rounded-2xl font-black tracking-widest hover:scale-[1.02] transition-all shadow-lg text-white">UPDATE PROFIL</button>
            </div>
        </form>
    </div>
</div>

<div id="cropModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/90 p-6">
    <div class="bg-slate-900 p-6 rounded-[2.5rem] max-w-lg w-full shadow-2xl border border-slate-800">
        <h3 class="text-white font-bold mb-4">Sesuaikan Foto Profil</h3>
        <div class="h-80 w-full bg-black rounded-2xl overflow-hidden">
            <img id="imageToCrop" class="max-w-full block">
        </div>
        <div class="flex gap-4 mt-6">
            <button type="button" onclick="closeModal()" class="flex-1 py-3 text-slate-400 font-bold">Batal</button>
            <button type="button" id="cropButton" class="flex-1 py-3 bg-emerald-600 rounded-xl font-bold text-white shadow-lg">Gunakan</button>
        </div>
    </div>
</div>

<script>
    let cropper;
    const fileInput = document.getElementById('fileInput');
    const cropModal = document.getElementById('cropModal');
    const imageToCrop = document.getElementById('imageToCrop');

    fileInput.onchange = (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                imageToCrop.src = event.target.result;
                cropModal.classList.remove('hidden');
                if (cropper) cropper.destroy();
                cropper = new Cropper(imageToCrop, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    guides: false,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: false
                });
            };
            reader.readAsDataURL(file);
        }
    };

    document.getElementById('cropButton').onclick = () => {
        const canvas = cropper.getCroppedCanvas({
            width: 500,
            height: 500
        });
        document.getElementById('avatarPreview').src = canvas.toDataURL();
        document.getElementById('cropped_image').value = canvas.toDataURL();
        closeModal();
    };

    function closeModal() {
        cropModal.classList.add('hidden');
        fileInput.value = "";
    }
</script>

<?php include 'includes/footer.php'; ?>