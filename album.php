<?php
include 'config/database.php';
include 'includes/header.php';

// 1. DEFINISIKAN VARIABEL AWAL
$uid = $_SESSION['UserID'] ?? null;
$pesan = "";
$albumID = $_GET['id'] ?? null;

// Pastikan user sudah login
if (!$uid) {
    header("Location: login.php");
    exit();
}

// 2. PROSES TAMBAH ALBUM
if (isset($_POST['tambah_album'])) {
    $nama = $_POST['nama_album'];
    $desc = $_POST['deskripsi'];
    $tgl  = date('Y-m-d');

    $stmt = $conn->prepare("INSERT INTO album (NamaAlbum, Deskripsi, TanggalDibuat, UserID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nama, $desc, $tgl, $uid);

    if ($stmt->execute()) {
        $pesan = "sukses_album";
    } else {
        $pesan = "gagal";
    }
    $stmt->close();
}

// 3. PROSES QUICK UPLOAD FOTO
if (isset($_POST['quick_upload'])) {
    $judul = $_POST['judul_foto'];
    $deskripsi = $_POST['deskripsi_foto'];
    $tgl = date('Y-m-d');
    
    $foto = $_FILES['file_foto']['name'];
    $tmp = $_FILES['file_foto']['tmp_name'];
    $lokasi = 'assets/uploads/';
    $nama_baru = time() . '_' . $foto;

    if (move_uploaded_file($tmp, $lokasi . $nama_baru)) {
        $stmt = $conn->prepare("INSERT INTO foto (JudulFoto, DeskripsiFoto, TanggalUnggah, LokasiFile, AlbumID, UserID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssii", $judul, $deskripsi, $tgl, $nama_baru, $albumID, $uid);
        
        if ($stmt->execute()) {
            $pesan = "sukses_foto";
        } else {
            $pesan = "gagal";
        }
        $stmt->close();
    }
}
?>

<style>
    /* Paksa sembunyikan scrollbar */
    .no-scrollbar::-webkit-scrollbar {
        display: none !important;
    }
    .no-scrollbar {
        -ms-overflow-style: none !important;
        scrollbar-width: none !important;
    }
    
    textarea {
        overflow-y: auto;
        word-wrap: break-word;
        transition: none !important; 
    }
</style>

<div class="container mx-auto px-4 py-10">
    <?php if ($albumID): ?>
        <a href="album.php" class="inline-flex items-center text-emerald-400 hover:text-white transition mb-6 group">
            <span class="mr-2 group-hover:-translate-x-1 transition-transform">←</span> Kembali ke Daftar Album
        </a>
    <?php endif; ?>

    <div class="mb-10">
        <h1 class="text-4xl font-black text-white tracking-tighter uppercase italic">
            <?php
            if ($albumID) {
                $stmt_info = $conn->prepare("SELECT NamaAlbum FROM album WHERE AlbumID = ?");
                $stmt_info->bind_param("i", $albumID);
                $stmt_info->execute();
                $info = $stmt_info->get_result()->fetch_assoc();
                echo "Isi Album: <span class='text-emerald-500'>" . ($info['NamaAlbum'] ?? 'Tidak Ditemukan') . "</span>";
            } else {
                echo "Manajemen <span class='text-blue-500'>Album</span>";
            }
            ?>
        </h1>
        <p class="text-slate-500"><?= $albumID ? "Melihat koleksi foto di dalam folder ini." : "Kelola koleksi foto kamu dalam folder yang rapi." ?></p>
    </div>

    <?php if (!$albumID): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="glass p-8 rounded-[2rem] h-fit border border-white/5 bg-slate-900/40 backdrop-blur-xl">
                <h2 class="text-2xl font-bold mb-6 text-emerald-400 text-center">Buat Album Baru</h2>
                <?php if ($pesan == "sukses_album"): ?>
                    <div class="bg-emerald-500/10 text-emerald-400 p-4 rounded-2xl mb-6 text-sm border border-emerald-500/20 text-center">✅ Album Berhasil Dibuat!</div>
                <?php endif; ?>

                <form action="" method="post" class="space-y-4">
                    <input type="text" name="nama_album" placeholder="Nama Album" class="w-full bg-slate-800/50 border border-slate-700/50 p-4 rounded-2xl text-white outline-none focus:border-emerald-500" required>
                    
                    <textarea name="deskripsi" placeholder="Deskripsi..." 
                        class="w-full bg-slate-800/50 border border-slate-700/50 p-4 rounded-2xl text-white outline-none focus:border-emerald-500 resize-none no-scrollbar"
                        style="min-height: 100px; max-height: 350px;"
                        oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px';"></textarea>
                    
                    <button type="submit" name="tambah_album" class="w-full bg-blue-600 hover:bg-emerald-600 p-4 rounded-2xl font-bold text-white transition shadow-lg shadow-blue-900/20">SIMPAN ALBUM</button>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php
                    $stmt_alb = $conn->prepare("SELECT * FROM album WHERE UserID = ? ORDER BY AlbumID DESC");
                    $stmt_alb->bind_param("i", $uid);
                    $stmt_alb->execute();
                    $albums = $stmt_alb->get_result();
                    while ($a = $albums->fetch_assoc()):
                    ?>
                        <div class="glass p-6 rounded-[2rem] border border-white/5 hover:border-emerald-500/50 transition group bg-slate-900/40">
                            <div class="flex flex-col h-full justify-between">
                                <div>
                                    <div class="text-emerald-500 mb-4">📂</div>
                                    <h3 class="text-xl font-bold text-white group-hover:text-emerald-400 transition"><?= $a['NamaAlbum'] ?></h3>
                                    <p class="text-slate-500 text-xs mt-2 italic"><?= $a['Deskripsi'] ?></p>
                                </div>
                                <div class="flex gap-4 mt-6">
                                    <a href="album.php?id=<?= $a['AlbumID'] ?>" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white text-center py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-widest transition">BUKA</a>
                                    </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
            <div class="lg:col-span-1">
                <div class="glass p-6 rounded-[2rem] border border-cyan-500/20 bg-slate-900/60 sticky top-10">
                    <h2 class="text-xl font-black text-cyan-400 mb-6 italic uppercase tracking-tighter">Quick Upload</h2>
                    
                    <?php if ($pesan == "sukses_foto"): ?>
                        <div class="bg-cyan-500/10 text-cyan-400 p-3 rounded-xl mb-4 text-[10px] border border-cyan-500/20 text-center font-bold">✅ FOTO BERHASIL DITAMBAHKAN!</div>
                    <?php endif; ?>

                    <form action="" method="post" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="album_id" value="<?= $albumID ?>">
                        
                        <div>
                            <label class="text-[10px] text-slate-500 font-bold uppercase ml-2 mb-1 block">Judul Foto</label>
                            <input type="text" name="judul_foto" placeholder="Beri judul..." class="w-full bg-slate-800/50 border border-slate-700/50 p-3 rounded-xl text-white text-sm outline-none focus:border-cyan-500" required>
                        </div>

                        <div>
                            <label class="text-[10px] text-slate-500 font-bold uppercase ml-2 mb-1 block">Deskripsi</label>
                            <textarea name="deskripsi_foto" placeholder="Cerita singkat..." 
                                class="w-full bg-slate-800/50 border border-slate-700/50 p-3 rounded-xl text-white text-sm outline-none focus:border-cyan-500 resize-none no-scrollbar block"
                                style="min-height: 80px; max-height: 300px;"
                                oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px';"></textarea>
                        </div>

                        <div>
                            <label class="text-[10px] text-slate-500 font-bold uppercase ml-2 mb-1 block">Pilih File</label>
                            <input type="file" name="file_foto" class="text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-cyan-500 file:text-black hover:file:bg-white transition cursor-pointer" required>
                        </div>

                        <button type="submit" name="quick_upload" class="w-full bg-cyan-400 hover:bg-white text-black font-black py-3 rounded-xl text-xs transition-all active:scale-95 shadow-lg shadow-cyan-500/10 uppercase tracking-widest">Upload ke Album Ini</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-3">
                <?php
                $stmt_fotos = $conn->prepare("SELECT * FROM foto WHERE AlbumID = ? ORDER BY FotoID DESC");
                $stmt_fotos->bind_param("i", $albumID);
                $stmt_fotos->execute();
                $fotos = $stmt_fotos->get_result();
                if ($fotos->num_rows > 0):
                ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        <?php while ($f = $fotos->fetch_assoc()): ?>
                            <div class="group relative bg-slate-900 rounded-[2rem] p-3 transition-all duration-500 hover:-translate-y-2 border border-white/5">
                                <div class="relative overflow-hidden rounded-[1.5rem] h-64 shadow-inner">
                                    <img src="assets/uploads/<?= $f['LokasiFile']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black p-4 flex flex-col justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                        <p class="text-white font-bold text-sm"><?= $f['JudulFoto'] ?></p>
                                        <div class="flex gap-2 mt-2">
                                            <a href="detail.php?id=<?= $f['FotoID'] ?>" class="flex-1 text-center py-2 bg-emerald-600 text-[9px] font-bold uppercase rounded-lg text-white">Detail</a>
                                            <a href="hapus.php?type=foto&id=<?= $f['FotoID'] ?>&album_id=<?= $albumID ?>" onclick="return confirm('Hapus foto?')" class="bg-red-600/20 hover:bg-red-600 p-2 rounded-lg text-white transition"><i class="fas fa-trash text-[10px]"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div id="album-content" class="w-full min-h-[450px] border-2 border-dashed border-white/10 rounded-[40px] flex flex-col items-center justify-center p-10 text-center space-y-8 bg-slate-900/20 backdrop-blur-sm">
                        <div class="space-y-2">
                            <div class="text-5xl mb-4 text-slate-700">📸</div>
                            <p class="text-gray-400 italic text-lg font-medium">Album ini masih kosong, Raftel.</p>
                            <p class="text-gray-600 text-[10px] uppercase tracking-[0.3em]">Gunakan panel di samping untuk menambah foto.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>