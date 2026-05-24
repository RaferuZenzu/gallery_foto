<?php
include 'config/database.php';
include 'includes/header.php';

$keyword = $_GET['cari'] ?? '';
$filter_me = $_GET['filter'] ?? '';
$search_term = "%$keyword%";

// --- LOGIKA SIMPAN RIWAYAT (ANTI-KLONING) ---
if (!empty($keyword) && isset($_SESSION['UserID'])) {
    $u_id = $_SESSION['UserID'];
    // Cek apakah keyword yang sama persis sudah dicari dalam 1 jam terakhir
    $checkHistory = mysqli_query($conn, "SELECT * FROM pencarian 
                                        WHERE UserID = '$u_id' AND Keyword = '$keyword' 
                                        AND CreatedAt > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    
    if (mysqli_num_rows($checkHistory) == 0) {
        $stmtSave = $conn->prepare("INSERT INTO pencarian (UserID, Keyword, CreatedAt) VALUES (?, ?, NOW())");
        $stmtSave->bind_param("is", $u_id, $keyword);
        $stmtSave->execute();
    }
}
// ... sisa kode Query Utama kebawah tetap sama ...
// ----------------------------------------------

// Query Utama
$sql = "SELECT foto.*, user.Username, user.FotoProfil, user.UserID as CreatorID, 
               user.Role, user.jml_follower, user.jml_like 
        FROM foto 
        JOIN user ON foto.UserID = user.UserID ";

if ($filter_me == 'me' && isset($_SESSION['UserID'])) {
    $u_id = $_SESSION['UserID'];
    $sql .= "WHERE (foto.JudulFoto LIKE ? OR foto.DeskripsiFoto LIKE ?) AND foto.UserID = ? ";
    $stmt = $conn->prepare($sql . " ORDER BY foto.FotoID DESC");
    $stmt->bind_param("ssi", $search_term, $search_term, $u_id);
} else {
    $sql .= "WHERE (foto.JudulFoto LIKE ? OR foto.DeskripsiFoto LIKE ?) ";
    $stmt = $conn->prepare($sql . " ORDER BY foto.FotoID DESC");
    $stmt->bind_param("ss", $search_term, $search_term);
}

$stmt->execute();
$query = $stmt->get_result();

// Ambil data User Login untuk Tampilan Header
$userHeader = null;
if ($filter_me == 'me' && isset($_SESSION['UserID'])) {
    $curr_id = $_SESSION['UserID'];
    $resUser = mysqli_query($conn, "SELECT Username, Role, jml_follower, jml_like FROM user WHERE UserID = '$curr_id'");
    $userHeader = mysqli_fetch_assoc($resUser);
}
?>

<style>
    body {
        background-color: #020617;
        background-image:
            radial-gradient(at 0% 0%, rgba(30, 64, 175, 0.15) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%),
            url('https://www.transparenttextures.com/patterns/stardust.png');
        background-attachment: fixed;
    }

    .glass-card {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .floating-shape {
        position: fixed;
        z-index: -1;
        filter: blur(80px);
        border-radius: 50%;
        opacity: 0.4;
        animation: float 20s infinite alternate;
    }

    @keyframes float {
        0% {
            transform: translate(0, 0);
        }

        100% {
            transform: translate(50px, 100px);
        }
    }
</style>

<div class="floating-shape w-64 h-64 md:w-96 md:h-96 bg-blue-600 top-[-10%] left-[-10%]"></div>
<div class="floating-shape w-64 h-64 md:w-80 md:h-80 bg-emerald-500 bottom-[5%] right-[-5%]"></div>

<div class="w-full px-4 py-8 md:py-16 relative">
    <header class="text-center mb-10 md:mb-16">
        <div class="inline-block px-4 py-1 border border-blue-500/30 rounded-full text-[10px] text-blue-400 font-bold tracking-[0.3em] mb-4 uppercase">
            Digital Archive v1.0
        </div>

        <?php if ($filter_me == 'me' && $userHeader): ?>
            <div class="flex items-center justify-center gap-3 mb-2">
                <h2 class="text-3xl md:text-5xl font-bold text-white"><?= $userHeader['Username'] ?></h2>
                <?php
                if ($userHeader['Role'] === 'admin') {
                    echo '<img src="assets/icons/verif2.png" class="w-6 h-6 md:w-8 md:h-8">';
                } elseif ($userHeader['jml_follower'] >= 10000000 && $userHeader['jml_like'] >= 1000000) {
                    echo '<img src="assets/icons/verif1.png" class="w-6 h-6 md:w-8 md:h-8">';
                }
                ?>
            </div>
        <?php endif; ?>

        <h1 class="text-5xl md:text-8xl font-black mb-4 bg-gradient-to-b from-white to-slate-500 bg-clip-text text-transparent tracking-tighter italic">
            <?= ($filter_me == 'me') ? 'My Works' : 'LazerPic' ?>
        </h1>

        <p class="text-slate-500 font-medium tracking-widest uppercase text-[10px] md:text-xs">
            <?= ($filter_me == 'me') ? 'Viewing your personal collection' : 'Capture the moment | Sharpen the vision' ?>
        </p>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 md:gap-10">
        <?php while ($data = mysqli_fetch_array($query)):
            $fID = $data['FotoID'];
            $creatorID = $data['CreatorID'];

            $totalLikeCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM likefoto WHERE FotoID = '$fID'");
            $totalLike = mysqli_fetch_assoc($totalLikeCount)['total'];

            $totalKomenCount = mysqli_query($conn, "SELECT COUNT(*) as total FROM komentarfoto WHERE FotoID = '$fID'");
            $totalKomen = mysqli_fetch_assoc($totalKomenCount)['total'];

            $isLiked = false;
            if (isset($_SESSION['UserID'])) {
                $current_u_id = $_SESSION['UserID'];
                $checkLike = mysqli_query($conn, "SELECT * FROM likefoto WHERE FotoID = '$fID' AND UserID = '$current_u_id'");
                if (mysqli_num_rows($checkLike) > 0) $isLiked = true;
            }
        ?>
            <div class="group relative bg-slate-900 rounded-[2rem] p-3 transition-all duration-500 hover:-translate-y-2 border border-white/5 shadow-2xl">
                <div class="relative overflow-hidden rounded-[1.5rem] h-64 md:h-80 shadow-inner">
                    <img src="assets/uploads/<?= $data['LokasiFile']; ?>" class="w-full h-full object-cover grayscale-[30%] group-hover:grayscale-0 transition-all duration-700 group-hover:scale-110">

                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent p-6 flex flex-col justify-end opacity-0 group-hover:opacity-100 transition-all duration-500">
                        <p class="text-lg font-black text-white leading-tight mb-3"><?= $data['JudulFoto']; ?></p>

                        <a href="user_space.php?UserID=<?= $creatorID ?>" class="flex items-center gap-2 mb-4 group/user">
                            <div class="w-6 h-6 rounded-full border border-emerald-500/50 overflow-hidden bg-slate-800">
                                <?php if (!empty($data['FotoProfil'])): ?>
                                    <img src="assets/profiles/<?= $data['FotoProfil'] ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-emerald-600 text-[8px] text-white font-bold">
                                        <?= strtoupper(substr($data['Username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-emerald-400 font-bold uppercase group-hover/user:text-white transition-colors">@<?= $data['Username']; ?></span>
                                <?php
                                if ($data['Role'] === 'admin') {
                                    echo '<img src="assets/icons/verif2.png" class="w-3 h-3">';
                                } elseif ($data['jml_follower'] >= 10000000 && $data['jml_like'] >= 1000000) {
                                    echo '<img src="assets/icons/verif1.png" class="w-3 h-3">';
                                }
                                ?>
                            </div>
                        </a>

                        <div class="flex space-x-4 border-t border-white/10 pt-4">
                            <button onclick="toggleLike(<?= $fID ?>)" class="flex items-center gap-1 transition-all duration-300">
                                <span id="heart-<?= $fID ?>" class="<?= $isLiked ? 'text-red-500' : 'text-white' ?>">❤️</span>
                                <span id="like-count-<?= $fID ?>" class="text-white text-[10px] font-bold"><?= $totalLike; ?></span>
                            </button>
                            <div class="text-white text-[10px] font-bold">💬 <?= $totalKomen; ?></div>
                        </div>
                    </div>
                </div>

                <div class="p-4 flex justify-center">
                    <a href="detail.php?id=<?= $data['FotoID']; ?>"
                        class="inline-block w-full text-center py-2.5 bg-slate-800/50 hover:bg-emerald-600 rounded-xl text-[9px] font-black tracking-[0.2em] uppercase transition-all">
                        View Work
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    function toggleLike(fotoID) {
        fetch('config/proses_like_ajax.php?id=' + fotoID)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('like-count-' + fotoID).innerText = data.total_like;
                    const heart = document.getElementById('heart-' + fotoID);
                    if (data.action === 'like') {
                        heart.classList.remove('text-white');
                        heart.classList.add('text-red-500');
                    } else {
                        heart.classList.remove('text-red-500');
                        heart.classList.add('text-white');
                    }
                } else if (data.status === 'error' && data.message === 'login_required') {
                    alert('Waduh, login dulu dong biar bisa nge-like!');
                    window.location.href = 'login.php';
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Variabel untuk melacak halaman
    let page = 1;
    let loading = false;

    window.onscroll = function() {
        // Jika scroll sudah mendekati bawah (kurang 100px dari dasar)
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 100) {
            if (!loading) {
                loading = true;
                loadMoreData();
            }
        }
    };

    function loadMoreData() {
        page++;
        // Ambil konten tambahan secara background
        fetch('load_more.php?page=' + page)
            .then(response => response.text())
            .then(data => {
                if (data.trim() !== "") {
                    // Tambahkan foto baru ke dalam grid tanpa refresh
                    document.querySelector('.grid').insertAdjacentHTML('beforeend', data);
                    loading = false;
                } else {
                    // Jika tidak ada data lagi, biarkan tetap loading agar tidak request terus
                    console.log("Sudah habis fotonya!");
                }
            });
    }
</script>

<?php include 'includes/footer.php'; ?>