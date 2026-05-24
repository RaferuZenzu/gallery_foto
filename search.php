<?php
include 'config/database.php';
include 'includes/header.php';

// Ambil keyword dari URL
$hashtag = $_GET['hashtag'] ?? '';
$search  = $_GET['q'] ?? '';
$keyword = !empty($hashtag) ? $hashtag : $search;
$search_term = "%$keyword%"; // Siapkan untuk query LIKE

// 1. QUERY CERDAS DENGAN PREPARED STATEMENT
$query_text = "SELECT foto.*, user.Username, COUNT(likefoto.LikeID) as total_like 
               FROM foto 
               JOIN user ON foto.UserID = user.UserID 
               LEFT JOIN likefoto ON foto.FotoID = likefoto.FotoID 
               WHERE foto.JudulFoto LIKE ? 
               OR foto.DeskripsiFoto LIKE ? 
               GROUP BY foto.FotoID 
               ORDER BY total_like DESC";

$stmt = $conn->prepare($query_text);
$stmt->bind_param("ss", $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-10 text-center">
        <h2 class="text-3xl font-bold text-white mb-2">
            <?= !empty($hashtag) ? "Hashtag: #" . htmlspecialchars($hashtag) : "Hasil Pencarian: '" . htmlspecialchars($keyword) . "'" ?>
        </h2>
        <p class="text-slate-400">Ditemukan <?= $result->num_rows ?> foto yang relevan</p>
    </div>

    <div class="columns-1 md:columns-2 lg:columns-4 gap-6 space-y-6">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="break-inside-avoid glass rounded-2xl overflow-hidden group hover:scale-[1.02] transition-all duration-300 border border-slate-700/50">
                    <a href="detail.php?id=<?= $row['FotoID'] ?>" class="block relative">
                        <img src="assets/uploads/<?= $row['LokasiFile'] ?>" class="w-full h-auto" alt="<?= htmlspecialchars($row['JudulFoto']) ?>">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4">
                            <h3 class="text-white font-bold text-sm"><?= htmlspecialchars($row['JudulFoto']) ?></h3>
                            <p class="text-blue-400 text-xs">@<?= htmlspecialchars($row['Username']) ?></p>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="w-full text-center py-20 glass rounded-[2rem] block">
                <p class="text-slate-500 italic">Wah, belum ada foto dengan kata kunci ini, Rafri.</p>
                <a href="index.php" class="text-blue-400 mt-4 inline-block hover:underline">Kembali Jelajahi</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$stmt->close();
include 'includes/footer.php'; 
?>