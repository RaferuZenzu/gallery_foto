<?php 
include 'config/database.php';
include 'includes/header.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$fotoID = $_GET['id'];
$userID = $_SESSION['UserID'];

// 1. CEK KEPEMILIKAN FOTO (SELECT AMAN)
$stmt_cek = $conn->prepare("SELECT * FROM foto WHERE FotoID = ? AND UserID = ?");
$stmt_cek->bind_param("ii", $fotoID, $userID); // "ii" karena dua-duanya ID (Integer)
$stmt_cek->execute();
$result = $stmt_cek->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    header("Location: dashboard.php");
    exit();
}

// 2. PROSES UPDATE (UPDATE AMAN)
if (isset($_POST['update'])) {
    $judul = $_POST['judul'];
    $deskripsi = $_POST['deskripsi'];

    // Siapkan query update
    $stmt_update = $conn->prepare("UPDATE foto SET JudulFoto = ?, DeskripsiFoto = ? WHERE FotoID = ? AND UserID = ?");
    
    // Bind data: Judul (s), Deskripsi (s), FotoID (i), UserID (i) -> "ssii"
    $stmt_update->bind_param("ssii", $judul, $deskripsi, $fotoID, $userID);
    
    if ($stmt_update->execute()) {
        echo "<script>alert('Data berhasil diperbarui!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data.');</script>";
    }
    
    $stmt_update->close();
}
?>

<div class="container mx-auto px-4 max-w-2xl">
    <div class="glass p-8 rounded-3xl">
        <h2 class="text-2xl font-bold mb-6">Edit Informasi Foto</h2>
        
        <div class="mb-6 rounded-xl overflow-hidden h-40">
            <img src="assets/uploads/<?php echo $data['LokasiFile']; ?>" class="w-full h-full object-cover opacity-50" alt="">
        </div>

        <form action="" method="post" class="space-y-4">
            <div>
                <label class="block text-sm text-slate-400 mb-2">Judul Foto</label>
                <input type="text" name="judul" value="<?php echo $data['JudulFoto']; ?>" class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-2">Deskripsi</label>
                <textarea name="deskripsi" class="w-full bg-slate-800 border border-slate-700 p-3 rounded-xl h-32 focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $data['DeskripsiFoto']; ?></textarea>
            </div>
            
            <div class="flex space-x-3">
                <button type="submit" name="update" class="flex-1 bg-blue-600 hover:bg-blue-700 p-3 rounded-xl font-bold transition">Simpan Perubahan</button>
                <a href="dashboard.php" class="flex-1 text-center bg-slate-700 hover:bg-slate-600 p-3 rounded-xl font-bold transition">Batal</a>
            </div>
        </form>
    </div>
</div>