<?php
include 'config/database.php';
include 'includes/header.php';
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

if (isset($_POST['upload'])) {
    $judul = $_POST['judul'];
    $deskripsi = $_POST['deskripsi'];
    $tanggal = date('Y-m-d');
    
    // Logika Tanpa Album: Jika kosong, set variabel php ke NULL murni
    $albumID = !empty($_POST['album_id']) ? $_POST['album_id'] : null;

    $filename = $_FILES['foto']['name'];
    $tmp_name = $_FILES['foto']['tmp_name'];
    $new_filename = time() . "_" . $filename;

    if (move_uploaded_file($tmp_name, 'assets/uploads/' . $new_filename)) {
        // 1. SIAPKAN QUERY AMAN
        $stmt = $conn->prepare("INSERT INTO foto (JudulFoto, DeskripsiFoto, TanggalUnggah, LokasiFile, AlbumID, UserID) VALUES (?, ?, ?, ?, ?, ?)");
        
        // 2. BIND PARAMETER
        $stmt->bind_param("ssssii", $judul, $deskripsi, $tanggal, $new_filename, $albumID, $userID);

        if ($stmt->execute()) {
            echo "<script>alert('Foto berhasil diupload!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan data ke database.');</script>";
        }
        $stmt->close();
    }
}
?>

<style>
    /* Sembunyikan scrollbar untuk textarea deskripsi */
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

<div class="container mx-auto px-4 max-w-2xl py-10">
    <div class="glass p-10 rounded-[2.5rem]">
        <h2 class="text-3xl font-bold mb-8 gradient-text text-center">Upload Karya</h2>

        <form action="" method="post" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm text-slate-400 mb-2">Pilih Album (Opsional)</label>
                <select name="album_id" class="w-full bg-slate-900 border border-slate-700 p-4 rounded-2xl outline-none focus:border-blue-500 transition text-white">
                    <option value="">-- Tanpa Album --</option>
                    <?php
                    // Amankan juga query dropdown album
                    $stmt_album = $conn->prepare("SELECT * FROM album WHERE UserID = ?");
                    $stmt_album->bind_param("i", $userID);
                    $stmt_album->execute();
                    $albumQuery = $stmt_album->get_result();
                    while ($a = $albumQuery->fetch_assoc()): ?>
                        <option value="<?= $a['AlbumID'] ?>"><?= $a['NamaAlbum'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm text-slate-400 mb-2">File Foto</label>
                <input type="file" name="foto" class="w-full text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer" required>
            </div>

            <input type="text" name="judul" placeholder="Judul Foto" class="w-full bg-slate-900 border border-slate-700 p-4 rounded-2xl outline-none focus:border-blue-500 transition text-white" required>
            
            <textarea
                name="deskripsi"
                placeholder="Deskripsi foto..."
                class="w-full bg-slate-900 border border-slate-700 p-4 rounded-2xl outline-none focus:border-blue-500 transition no-scrollbar resize-none block text-white"
                style="min-height: 120px; max-height: 400px;"
                oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px';"></textarea>

            <button type="submit" name="upload" class="w-full bg-gradient-to-r from-blue-600 to-emerald-600 p-4 rounded-2xl font-bold transition shadow-lg text-white">Publish</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>