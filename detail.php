<?php
include 'config/database.php';
include 'includes/header.php';

function buatHashtag($teks)
{
    return preg_replace('/#(\w+)/', '<a href="search.php?hashtag=$1" class="text-blue-400 hover:underline font-bold">#$1</a>', $teks);
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>location.href='index.php';</script>";
    exit;
}

$fotoID = $_GET['id'];
$userID = $_SESSION['UserID'] ?? null;
$role   = $_SESSION['Role'] ?? 'user';

// 1. QUERY DATA FOTO (MENGGUNAKAN PREPARED STATEMENTS)
$stmt_post = $conn->prepare("SELECT foto.*, user.Username, user.FotoProfil, user.UserID 
    FROM foto 
    JOIN user ON foto.UserID = user.UserID 
    WHERE FotoID = ?");
$stmt_post->bind_param("i", $fotoID); // "i" jika FotoID adalah Integer, gunakan "s" jika String
$stmt_post->execute();
$query_post = $stmt_post->get_result();

if ($query_post->num_rows == 0) {
    echo "<script>alert('Foto tidak ditemukan!'); location.href='index.php';</script>";
    exit;
}

$data = $query_post->fetch_assoc();

// 2. HITUNG LIKE (MENGGUNAKAN PREPARED STATEMENTS)
$stmt_count = $conn->prepare("SELECT * FROM likefoto WHERE FotoID = ?");
$stmt_count->bind_param("i", $fotoID);
$stmt_count->execute();
$countLike = $stmt_count->get_result()->num_rows;

$isLiked = false;
if ($userID) {
    $stmt_cek = $conn->prepare("SELECT * FROM likefoto WHERE FotoID = ? AND UserID = ?");
    $stmt_cek->bind_param("ii", $fotoID, $userID);
    $stmt_cek->execute();
    if ($stmt_cek->get_result()->num_rows > 0) $isLiked = true;
}
?>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        <div class="glass rounded-[2rem] overflow-hidden shadow-2xl h-fit">
            <img src="assets/uploads/<?php echo $data['LokasiFile']; ?>" class="w-full h-auto" alt="">
            <div class="p-6 flex justify-between items-center bg-slate-900/40">
                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-2">
                        <a href="like.php?id=<?= $fotoID ?>" class="text-2xl <?= $isLiked ? 'text-red-500' : 'text-slate-400' ?> hover:scale-125 transition">
                            <?= $isLiked ? '❤️' : '🤍' ?>
                        </a>
                        <span class="font-bold text-xl"><?= $countLike ?></span>
                    </div>
                    <a href="assets/uploads/<?= $data['LokasiFile']; ?>"
                        download="<?= $data['JudulFoto']; ?>"
                        class="group flex items-center space-x-2 bg-slate-700/50 hover:bg-emerald-500 px-4 py-2 rounded-xl transition-all duration-300 border border-slate-600 hover:border-emerald-400 shadow-lg"
                        title="Download Foto">
                        <span class="text-xl group-hover:animate-bounce">📥</span>
                        <span class="text-xs font-bold text-slate-300 group-hover:text-white uppercase tracking-wider">Save</span>
                    </a>
                    <?php if ($role === 'admin' || $data['UserID'] == $userID): ?>
                        <a href="hapus.php?id=<?= $fotoID ?>"
                            onclick="return confirm('Hapus foto ini selamanya?')"
                            class="bg-red-500/20 text-red-400 px-4 py-2 rounded-xl text-sm hover:bg-red-600 hover:text-white transition flex items-center">
                            🗑️ Hapus Foto
                        </a>
                    <?php endif; ?>
                </div>
                <span class="text-slate-500 text-sm"><?= $data['TanggalUnggah'] ?></span>
            </div>
        </div>

        <div class="flex flex-col space-y-6">
            <div class="glass p-8 rounded-[2rem]">
                <h1 class="text-4xl font-bold mb-2 gradient-text"><?= $data['JudulFoto'] ?></h1>
                <div class="flex items-center space-x-3 mb-4">
                    <a href="user_space.php?UserID=<?= $data['UserID'] ?>" class="flex items-center space-x-3 group">
                        <img src="assets/profiles/<?= !empty($data['FotoProfil']) ? $data['FotoProfil'] : 'default.png' ?>"
                            class="w-10 h-10 rounded-full object-cover border-2 border-emerald-500/30 group-hover:border-emerald-400 transition-all">
                        <p class="text-emerald-400 font-medium italic group-hover:text-emerald-300">
                            Oleh: @<?= $data['Username'] ?>
                        </p>
                    </a>
                </div>
                <p class="text-slate-300 leading-relaxed"><?= nl2br(buatHashtag($data['DeskripsiFoto'])) ?></p>
            </div>

            <div class="glass p-6 rounded-[2rem] flex-1 min-h-[300px] flex flex-col">
                <h3 class="font-bold mb-6 border-b border-slate-700 pb-2 text-white">Komentar Terbaru</h3>

                <div id="container-komentar" class="space-y-4 overflow-y-auto max-h-[350px] pr-2 flex-1">
                    <?php
                    $komentar = mysqli_query($conn, "SELECT komentarfoto.*, user.Username, user.FotoProfil, user.UserID 
                        FROM komentarfoto 
                        JOIN user ON komentarfoto.UserID = user.UserID 
                        WHERE FotoID = '$fotoID' 
                        ORDER BY KomentarID DESC");

                    if (mysqli_num_rows($komentar) == 0): ?>
                        <p id="no-comment" class='text-slate-500 italic text-center py-10'>Belum ada komentar.</p>
                    <?php else: ?>
                        <?php while ($k = mysqli_fetch_array($komentar)): ?>
                            <div class="bg-slate-800/40 p-4 rounded-2xl border border-slate-700/50 flex justify-between items-start group">
                                <div class="flex space-x-4 items-center">
                                    <a href="user_space.php?UserID=<?= $k['UserID'] ?>" class="flex-shrink-0">
                                        <?php if (!empty($k['FotoProfil'])): ?>
                                            <img src="assets/profiles/<?= $k['FotoProfil'] ?>" class="w-10 h-10 rounded-full object-cover border-2 border-slate-700">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center border-2 border-slate-600 text-white font-bold">
                                                <?= strtoupper(substr($k['Username'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    </a>

                                    <div class="flex flex-col">
                                        <a href="user_space.php?UserID=<?= $k['UserID'] ?>" class="text-[11px] text-blue-400 font-extrabold uppercase">@<?= $k['Username'] ?></a>
                                        <p class="text-sm text-slate-200 mt-0.5"><?= $k['IsiKomentar'] ?></p>
                                    </div>
                                </div>

                                <?php if ($role === 'admin' || $k['UserID'] == $userID): ?>
                                    <a href="hapus_komentar.php?id=<?= $k['KomentarID'] ?>&foto=<?= $fotoID ?>"
                                        onclick="return confirm('Hapus komentar ini?')"
                                        class="opacity-0 group-hover:opacity-100 transition-opacity text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded-lg hover:bg-red-500 hover:text-white">
                                        Hapus
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>

                <?php if ($userID): ?>
                    <div class="mt-6 flex space-x-2">
                        <input type="hidden" id="fotoID" value="<?= $fotoID ?>">
                        <input type="hidden" id="currentUserID" value="<?= $userID ?>">
                        <input type="hidden" id="currentUserRole" value="<?= $role ?>">
                        <input type="text" id="isi_komentar" placeholder="Tulis komentar..."
                            class="flex-1 bg-slate-900 border border-slate-700 p-4 rounded-2xl outline-none focus:border-blue-500 transition text-white" required>

                        <button type="button" id="btnKirim" class="bg-blue-600 px-6 rounded-2xl font-bold hover:bg-blue-700 transition text-white">
                            Kirim
                        </button>
                    </div>
                <?php else: ?>
                    <div class="mt-6 p-4 bg-slate-900/50 rounded-2xl border border-dashed border-slate-700 text-center text-slate-500 text-sm">
                        Ingin ikut berdiskusi? <a href="login.php" class="text-blue-400 hover:underline">Login sekarang</a>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnKirim = document.getElementById('btnKirim');
        const inputKomen = document.getElementById('isi_komentar');
        const container = document.getElementById('container-komentar');

        function eksekusiKirim() {
            const fID = document.getElementById('fotoID').value;
            const teks = inputKomen.value.trim();
            const loginID = document.getElementById('currentUserID').value;
            const loginRole = document.getElementById('currentUserRole').value;

            if (teks === "") return;

            btnKirim.disabled = true;
            btnKirim.innerText = "...";

            const formData = new FormData();
            formData.append('id', fID);
            formData.append('isi_komentar', teks);

            fetch('proses_komentar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    btnKirim.disabled = false;
                    btnKirim.innerText = "Kirim";

                    if (data.status === 'success') {
                        const noMsg = document.getElementById('no-comment');
                        if (noMsg) noMsg.remove();

                        let htmlPP = data.foto ?
                            `<img src="assets/profiles/${data.foto}" class="w-10 h-10 rounded-full object-cover border-2 border-slate-700">` :
                            `<div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center border-2 border-slate-600 text-white font-bold">${data.username.charAt(0).toUpperCase()}</div>`;

                        let tombolHapus = '';
                        // Sesuaikan pengecekan role di JS agar sinkron
                        if (loginRole === 'admin' || data.userID_baru == loginID) {
                            tombolHapus = `
                                <a href="hapus_komentar.php?id=${data.komentarID_baru}&foto=${fID}" 
                                   onclick="return confirm('Hapus komentar ini?')"
                                   class="text-xs bg-red-500/20 text-red-400 px-2 py-1 rounded-lg hover:bg-red-500 hover:text-white transition">
                                   Hapus
                                </a>`;
                        }

                        let htmlBaru = `
                            <div class="bg-slate-800/40 p-4 rounded-2xl border border-slate-700/50 flex justify-between items-start group">
                                <div class="flex space-x-4 items-center">
                                    <a href="user_space.php?UserID=${data.userID_baru}" class="flex-shrink-0">${htmlPP}</a>
                                    <div class="flex flex-col">
                                        <a href="user_space.php?UserID=${data.userID_baru}" class="text-[11px] text-blue-400 font-extrabold uppercase">@${data.username}</a>
                                        <p class="text-sm text-slate-200 mt-0.5">${data.isi}</p>
                                    </div>
                                </div>
                                ${tombolHapus}
                            </div>`;

                        container.insertAdjacentHTML('afterbegin', htmlBaru);
                        inputKomen.value = '';
                    }
                })
                .catch(err => {
                    btnKirim.disabled = false;
                    btnKirim.innerText = "Kirim";
                });
        }

        if (btnKirim) {
            btnKirim.addEventListener('click', (e) => {
                e.preventDefault();
                eksekusiKirim();
            });
        }

        if (inputKomen) {
            inputKomen.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    eksekusiKirim();
                }
            });
        }
    });
</script>