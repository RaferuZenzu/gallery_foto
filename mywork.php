<?php
include 'config/database.php';
include 'includes/header.php';

// Mendeteksi nama file yang sedang berjalan (mywork.php atau user_space.php)
$current_file = basename($_SERVER['PHP_SELF']);

// --- LOGIKA TOMBOL VERIFIKASI ADMIN ---
if (isset($_POST['toggle_verif']) && $_SESSION['Role'] == 'admin') {
    $target_id = $_POST['target_user_id'];
    $current_verif = $_POST['current_verif'];
    
    // Cek Role Target: Admin dilarang verifikasi sesama Admin
    $check_role = mysqli_query($conn, "SELECT Role FROM user WHERE UserID = '$target_id'");
    $target_role = mysqli_fetch_assoc($check_role)['Role'];

    if ($target_role !== 'admin') {
        $new_status = ($current_verif == 1) ? 0 : 1;
        $stmt_update = $conn->prepare("UPDATE user SET IsVerified = ? WHERE UserID = ?");
        $stmt_update->bind_param("ii", $new_status, $target_id);
        if ($stmt_update->execute()) {
            // Mengembalikan ke halaman asalnya dengan dinamis
            $redirect_url = ($current_file == 'mywork.php') ? 'mywork.php' : 'user_space.php?UserID=' . $target_id;
            echo "<script>alert('Status Verifikasi Berhasil Diubah!'); window.location.href='$redirect_url';</script>";
        }
    } else {
        echo "<script>alert('Akses Ditolak: Tidak bisa memodifikasi status verifikasi sesama Admin!');</script>";
    }
}

// KUNCI UTAMA MYWORK: Karena diakses via My Work, target_user_id otomatis dikunci ke akun sendiri ($_SESSION['UserID'])
$viewer_id = $_SESSION['UserID'] ?? null;
$target_user_id = $_GET['UserID'] ?? $viewer_id ?? '';
$album_id_filter = $_GET['album'] ?? '';

if (empty($target_user_id)) {
    echo "<script>location.href='index.php';</script>";
    exit;
}

// Cek apakah halaman yang sedang dibuka adalah akun milik kita sendiri
$is_my_profile = ($target_user_id == $viewer_id);

// 1. Ambil data user & Statistik secara AMAN
$stmt_user = $conn->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM follow WHERE FollowingID = u.UserID) as followers_count,
    (SELECT COUNT(*) FROM follow WHERE FollowerID = u.UserID) as following_count,
    (SELECT COUNT(*) FROM likefoto l JOIN foto f ON l.FotoID = f.FotoID WHERE f.UserID = u.UserID) as total_likes
    FROM user u WHERE u.UserID = ?");
$stmt_user->bind_param("i", $target_user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

if (!$user_data) {
    echo "<div class='py-20 text-center text-white font-bold'>User tidak ditemukan.</div>";
    include 'includes/footer.php';
    exit;
}

// 2. Cek apakah Viewer sudah follow Target
$is_following = false;
if ($viewer_id) {
    $stmt_check = $conn->prepare("SELECT 1 FROM follow WHERE FollowerID = ? AND FollowingID = ?");
    $stmt_check->bind_param("ii", $viewer_id, $target_user_id);
    $stmt_check->execute();
    $is_following = $stmt_check->get_result()->num_rows > 0;
}

// 3. Ambil list album secara AMAN
$stmt_alb_list = $conn->prepare("SELECT * FROM album WHERE UserID = ?");
$stmt_alb_list->bind_param("i", $target_user_id);
$stmt_alb_list->execute();
$query_album_list = $stmt_alb_list->get_result();

// 4. Query Foto Filter secara AMAN
if (!empty($album_id_filter)) {
    $stmt_foto = $conn->prepare("SELECT * FROM foto WHERE UserID = ? AND AlbumID = ? ORDER BY FotoID DESC");
    $stmt_foto->bind_param("ii", $target_user_id, $album_id_filter);
} else {
    $stmt_foto = $conn->prepare("SELECT * FROM foto WHERE UserID = ? ORDER BY FotoID DESC");
    $stmt_foto->bind_param("i", $target_user_id);
}
$stmt_foto->execute();
$query_foto = $stmt_foto->get_result();
?>

<div class="w-full px-6 py-12">
    <div class="max-w-5xl mx-auto mb-16 p-8 rounded-[3rem] bg-slate-900/50 border border-white/5 flex flex-col md:flex-row items-center gap-8 shadow-2xl relative overflow-hidden">
        <div class="w-32 h-32 rounded-full border-4 border-emerald-500 p-1 overflow-hidden relative z-10">
            <?php
            $fotoPath = "assets/profiles/" . $user_data['FotoProfil'];
            $displayFoto = (!empty($user_data['FotoProfil']) && file_exists($fotoPath)) ? $fotoPath : 'assets/profiles/default.png';
            ?>
            <img src="<?= $displayFoto ?>" class="w-full h-full rounded-full object-cover">
        </div>
        
        <div class="flex-1 text-center md:text-left z-10">
            <h2 class="text-5xl font-black italic text-white uppercase tracking-tighter flex items-center justify-center md:justify-start gap-3">
                <?= htmlspecialchars($user_data['Username']) ?>
                <?php if ($user_data['Role'] === 'admin'): ?>
                    <img src="assets/icons/verif2.png" class="w-10 h-10" title="Admin Exclusive">
                <?php elseif ($user_data['IsVerified'] == 1): ?>
                    <img src="assets/icons/verif1.png" class="w-10 h-10" title="Verified User">
                <?php endif; ?>
            </h2>
            
            <div class="flex justify-center md:justify-start gap-6 mt-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                <div><span class="text-emerald-400 text-lg block"><?= number_format($user_data['followers_count']) ?></span> Followers</div>
                <div><span class="text-emerald-400 text-lg block"><?= number_format($user_data['following_count']) ?></span> Following</div>
                <div><span class="text-emerald-400 text-lg block"><?= number_format($user_data['total_likes']) ?></span> Total Likes</div>
            </div>
            <p class="text-slate-400 mt-2 italic">Gallery and content control center.</p>
        </div>

        <div class="flex flex-col gap-3 z-10">
            <?php if ($viewer_id && !$is_my_profile): ?>
                <a href="config/proses_follow.php?target=<?= $target_user_id ?>" 
                   class="px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-tighter text-center transition-all <?= $is_following ? 'bg-slate-800 text-slate-400 border border-white/10' : 'bg-white text-black hover:bg-emerald-500' ?>">
                   <?= $is_following ? 'Unfollow' : 'Follow User' ?>
                </a>
            <?php endif; ?>

            <?php if ($is_my_profile): ?>
                <a href="account_detail.php" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl text-[10px] font-black uppercase tracking-tighter text-center transition-all hover:scale-[1.03] shadow-md shadow-blue-900/20">account_detail</a>

                <a href="upload.php" class="px-8 py-3 bg-emerald-500 text-black rounded-xl text-[10px] font-black uppercase tracking-tighter text-center transition-all hover:bg-emerald-400">Upload Foto</a>
                <a href="album.php" class="px-8 py-3 bg-slate-800 text-slate-400 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-tighter text-center transition-all hover:bg-slate-700">Buat Album</a>
            <?php endif; ?>

            <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] == 'admin'): ?>
                <?php if ($user_data['Role'] !== 'admin'): ?>
                    <form method="POST">
                        <input type="hidden" name="target_user_id" value="<?= $target_user_id ?>">
                        <input type="hidden" name="current_verif" value="<?= $user_data['IsVerified'] ?>">
                        <button type="submit" name="toggle_verif"
                            class="w-full px-6 py-3 <?= ($user_data['IsVerified'] == 1) ? 'bg-purple-900 text-purple-200' : 'bg-purple-600 text-white' ?> text-[10px] font-black rounded-xl transition-all shadow-lg uppercase tracking-widest text-center border border-purple-400/30">
                            <?= ($user_data['IsVerified'] == 1) ? 'Remove Verified Status' : 'Give Verified (Purple)' ?>
                        </button>
                    </form>
                <?php endif; ?>

                <button onclick="activateRemoveMode()" class="px-8 py-3 bg-red-600/20 text-red-500 border border-red-500/30 rounded-xl text-[10px] font-black uppercase hover:bg-red-600 hover:text-white transition-all">
                    MANAGE ALBUMS (HAPUS)
                </button>

                <a href="config/proses_hapus_album.php?action=all&user_id=<?= $target_user_id ?>" 
                   onclick="return confirm('PERINGATAN KERAS: Hapus seluruh album dan foto user ini tanpa sisa? Tindakan ini tidak bisa dibatalkan!')"
                   class="px-8 py-2 bg-red-900/40 text-red-400 border border-red-900/50 rounded-xl text-[9px] font-black uppercase hover:bg-red-700 hover:text-white transition-all text-center">
                    Delete All Content
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-16">
        <h3 class="text-center text-slate-500 font-bold tracking-[0.4em] uppercase text-[10px] mb-8 text-emerald-400">Personal Albums</h3>

        <form action="config/proses_hapus_album.php" method="POST">
            <input type="hidden" name="user_id" value="<?= $target_user_id ?>">

            <div class="flex flex-wrap justify-center gap-4">
                <a href="<?= ($current_file == 'mywork.php') ? 'mywork.php' : 'user_space.php?UserID='.$target_user_id ?>"
                    class="px-6 py-3 rounded-2xl text-xs font-black italic transition-all <?= empty($album_id_filter) ? 'bg-emerald-500 text-black shadow-lg shadow-emerald-500/20' : 'bg-slate-900 text-emerald-400 border border-emerald-500/30' ?>">
                    ALL
                </a>

                <?php while ($alb = $query_album_list->fetch_assoc()): ?>
                    <div class="relative group">
                        <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] == 'admin'): ?>
                            <div class="absolute -top-2 -left-2 z-20 checkbox-remove-container hidden">
                                <input type="checkbox" name="album_ids[]" value="<?= $alb['AlbumID'] ?>"
                                    class="w-6 h-6 accent-red-500 cursor-pointer shadow-xl">
                            </div>
                        <?php endif; ?>

                        <a href="<?= ($current_file == 'mywork.php') ? 'mywork.php?album='.$alb['AlbumID'] : 'user_space.php?UserID='.$target_user_id.'&album='.$alb['AlbumID'] ?>"
                            class="px-6 py-3 rounded-2xl text-xs font-black italic flex items-center gap-2 transition-all <?= ($album_id_filter == $alb['AlbumID']) ? 'bg-emerald-500 text-black shadow-lg shadow-emerald-500/20' : 'bg-slate-900 text-emerald-400 border border-emerald-500/30' ?>">
                            📁 <?= htmlspecialchars($alb['NamaAlbum']) ?>
                        </a>
                    </div>
                <?php endwhile; ?>

                <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] == 'admin'): ?>
                    <button type="submit" name="action" value="selected" id="confirmDelete"
                        class="px-8 py-3 bg-red-600 border border-red-500 text-white rounded-2xl text-[10px] font-black hover:bg-red-500 transition-all uppercase italic hidden shadow-lg shadow-red-600/40">
                        Confirm Delete Selected Albums
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
        <?php if ($query_foto->num_rows == 0): ?>
            <div class="col-span-full text-center py-20 text-slate-600 italic">User ini belum mengunggah foto.</div>
        <?php endif; ?>

        <?php while ($f = $query_foto->fetch_assoc()): ?>
            <div class="relative group aspect-square rounded-[2rem] overflow-hidden bg-slate-900 border border-white/5 shadow-xl">
                <img src="assets/uploads/<?= $f['LokasiFile'] ?>" class="w-full h-full object-cover grayscale-[30%] group-hover:grayscale-0 transition-all duration-700">
                <div class="absolute inset-0 bg-black/80 opacity-0 group-hover:opacity-100 transition-all flex flex-col justify-center items-center p-4 text-center">
                    <p class="text-white font-black italic mb-4 uppercase tracking-tighter"><?= htmlspecialchars($f['JudulFoto']) ?></p>
                    <div class="flex gap-2">
                        <a href="detail.php?id=<?= $f['FotoID'] ?>" class="px-4 py-2 bg-blue-600 rounded-lg text-[10px] font-extrabold uppercase hover:bg-blue-500 transition-colors">VIEW</a>
                        <?php if (isset($_SESSION['Role']) && $_SESSION['Role'] == 'admin'): ?>
                            <a href="config/proses_hapus_album.php?action=single&id=<?= $f['FotoID'] ?>&user_id=<?= $target_user_id ?>" 
                               onclick="return confirm('Hapus foto ini?')" 
                               class="px-4 py-2 bg-red-600 rounded-lg text-[10px] font-extrabold uppercase hover:bg-red-500 transition-colors">DELETE</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    window.activateRemoveMode = function() {
        const btnConfirm = document.getElementById('confirmDelete');
        if (btnConfirm) btnConfirm.classList.toggle('hidden');
        const checkboxes = document.querySelectorAll('.checkbox-remove-container');
        checkboxes.forEach(el => el.classList.toggle('hidden'));
    };
</script>

<?php
$stmt_user->close();
$stmt_alb_list->close();
$stmt_foto->close();
include 'includes/footer.php';
?>