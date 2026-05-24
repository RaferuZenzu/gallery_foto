<?php
// 1. Pastikan session jalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Sertakan koneksi dan fungsi logika verifikasi agar tidak error
include_once 'config/database.php';

// Pastikan fungsi getVerificationStatus ada supaya tidak fatal error
if (!function_exists('getVerificationStatus')) {
    function getVerificationStatus($user)
    {
        if ($user['Role'] === 'admin') return 'admin';
        if ((isset($user['IsVerified']) && $user['IsVerified'] == 1) ||
            ($user['jml_follower'] >= 10000000)
        ) return 'verified';
        return 'none';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAZERPIC - Capture the Moment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap');

        body {
            background: #020617;
            color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .search-active {
            width: 100% !important;
            max-width: 800px !important;
            transition: all 0.4s ease;
        }

        #search-results {
            display: none;
            background: #0f172a;
            /* Slate 900 Solid */
            border: 1px solid rgba(59, 130, 246, 0.3);
            /* Border Biru Transparan */
            border-top: none;
            border-radius: 0 0 20px 20px;
            z-index: 9999 !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        #search-results.active {
            display: block !important;
        }

        /* Biar teks di dalem dropdown cerah */
        .search-item-text {
            color: #f8fafc !important;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <nav class="glass sticky top-0 z-[100] px-6 md:px-10 py-4 mb-2 shadow-2xl">
        <div class="w-full flex items-center justify-between">
            <div class="flex items-center space-x-4 flex-1">
                <a href="mywork.php" class="flex items-center group" title="My Work - Userspace Saya">
                    <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-emerald-500 shadow-[0_0_15px_rgba(59,130,246,0.5)] group-hover:rotate-6 transition-all duration-300">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 100 100" fill="none">
                            <path d="M35 20V70H75" stroke="currentColor" stroke-width="16" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="75" cy="70" r="8" fill="#fff" />
                        </svg>
                    </div>
                </a>
                <div class="relative flex-1 max-w-sm transition-all duration-500" id="search-wrapper">
                    <form action="index.php" method="get" id="search-form">
                        <input type="text" name="cari" id="main-search" autocomplete="off" placeholder="Search visuals..."
                            class="w-full bg-slate-900/50 border border-slate-700/50 py-2.5 px-5 rounded-2xl text-xs focus:border-blue-500 outline-none text-slate-200 focus:bg-slate-900 transition-all">
                    </form>
                    <div id="search-results" class="absolute left-0 w-full md:w-[650px] bg-slate-900/98 backdrop-blur-3xl border border-white/10 rounded-3xl mt-4 hidden z-[999] shadow-2xl max-h-[500px] overflow-y-auto p-6">
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-white transition">Explore</a>

                <?php if (isset($_SESSION['UserID'])):
                    // Ambil data user terbaru
                    $u_id = $_SESSION['UserID'];
                    $q_h = mysqli_query($conn, "SELECT * FROM user WHERE UserID = '$u_id'");
                    $u_h = mysqli_fetch_assoc($q_h);
                    $status_h = getVerificationStatus($u_h);
                ?>
                    <a href="album.php" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-white transition">Album</a>
                    <a href="upload.php" class="bg-blue-600 px-5 py-2 rounded-xl hover:bg-blue-500 text-white text-[10px] font-black uppercase shadow-lg transition">Upload</a>

                    <div class="flex items-center gap-4 border-l border-white/10 pl-6">
                        <div class="hidden md:flex flex-col items-end">
                            <span class="text-white font-bold text-sm flex items-center gap-1 uppercase tracking-tighter">
                                <?= htmlspecialchars($u_h['Username']) ?>
                                <?php if ($status_h === 'admin'): ?>
                                    <img src="assets/icons/verif2.png" class="w-4 h-4">
                                <?php elseif ($status_h === 'verified'): ?>
                                    <img src="assets/icons/verif1.png" class="w-4 h-4">
                                <?php endif; ?>
                            </span>
                            <span class="text-[9px] text-slate-500 font-black tracking-[0.2em]"><?= strtoupper($u_h['Role']) ?></span>
                        </div>

                        <a href="profile.php" class="w-10 h-10 rounded-full border-2 border-white/10 overflow-hidden hover:border-emerald-500 transition-all">
                            <img src="assets/profiles/<?= !empty($u_h['FotoProfil']) ? $u_h['FotoProfil'] : 'default.png' ?>" class="w-full h-full object-cover">
                        </a>

                        <a href="logout.php" class="text-slate-400 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="text-[10px] font-black uppercase tracking-widest text-slate-400">Login</a>
                    <a href="register.php" class="border border-emerald-500/40 text-emerald-400 px-4 py-2 rounded-xl text-[10px] font-black uppercase">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('main-search');
            const resultsBox = document.getElementById('search-results');
            const wrapper = document.getElementById('search-wrapper');

            if (!searchInput || !resultsBox) return;

            searchInput.addEventListener('input', function() {
                let q = this.value; 
                let qTrim = q.trim();
                
                if (qTrim.length > 0) {
                    fetch('config/search_ajax.php?q=' + encodeURIComponent(qTrim))
                        .then(res => res.json())
                        .then(data => {
                            resultsBox.innerHTML = '';
                            
                            // Animasi dropdown smooth masuk
                            resultsBox.classList.remove('hidden');
                            resultsBox.classList.add('active');
                            resultsBox.style.opacity = '0';
                            resultsBox.style.transform = 'translateY(-8px) scale(0.99)';
                            resultsBox.style.transition = 'all 0.15s cubic-bezier(0.4, 0, 0.2, 1)';
                            resultsBox.offsetHeight; 
                            resultsBox.style.opacity = '1';
                            resultsBox.style.transform = 'translateY(0) scale(1)';

                            let html = '';
                            let uniqueSuggestions = new Set(); 

                            // --- ALGORITMA TOKENISASI PINTEREST / GOOGLE SEARCH STYLE ---
                            if (data.keywords.length > 0) {
                                data.keywords.forEach(keyword => {
                                    let words = keyword.split(/\s+/); // Pecah kalimat jadi per kata
                                    let lowerQ = qTrim.toLowerCase();
                                    
                                    let targetIndex = -1;
                                    // Cari di kata sebelah mana input user COCOK
                                    for (let i = 0; i < words.length; i++) {
                                        if (words[i].toLowerCase().includes(lowerQ)) {
                                            targetIndex = i;
                                            break;
                                        }
                                    }

                                    if (targetIndex !== -1) {
                                        let finalSuggestion = "";
                                        
                                        // JIKA USER KETIK HURUF AWAL / KATA DEPAN (Misal ketik "p" untuk Porsche)
                                        if (targetIndex === 0) {
                                            // Ambil kata itu sendiri + maksimal 1 kata di belakangnya agar tidak kepanjangan
                                            let endSlice = Math.min(targetIndex + 2, words.length);
                                            finalSuggestion = words.slice(targetIndex, endSlice).join(' ');
                                        } 
                                        // JIKA USER KETIK KATA TENGAH/SPESIFIK (Misal ketik "918")
                                        else {
                                            // KATA DI DEPAN OTOMATIS DIBUANG! Ambil dari kata yang cocok + 1 kata setelahnya
                                            let endSlice = Math.min(targetIndex + 2, words.length);
                                            finalSuggestion = words.slice(targetIndex, endSlice).join(' ');
                                        }

                                        // Bersihkan string dan pastikan tidak duplikat di dropdown
                                        finalSuggestion = finalSuggestion.trim();
                                        let suggestionLower = finalSuggestion.toLowerCase();

                                        if (finalSuggestion !== "" && !uniqueSuggestions.has(suggestionLower)) {
                                            uniqueSuggestions.add(suggestionLower);

                                            // PROSES HIGHLIGHT WARNA: Ketikan = Putih, Sisa Tebakan = Abu-abu
                                            let regex = new RegExp(`(${escapeRegExp(qTrim)})`, 'gi');
                                            let highlightedText = finalSuggestion.replace(regex, `<span class="text-white">$1</span>`);

                                            html += `
                                            <div class="flex items-center gap-3 p-3 hover:bg-white/[0.04] cursor-pointer rounded-2xl transition-all duration-150 group" 
                                                 onclick="fillSearchAndSubmit('${escapeJs(finalSuggestion)}')">
                                                <div class="w-7 h-7 flex items-center justify-center bg-slate-800 rounded-full text-slate-500 text-xs group-hover:bg-blue-600 group-hover:text-white transition-all duration-200">🔍</div>
                                                <span class="text-sm tracking-wide font-semibold text-slate-500 group-hover:text-slate-400 transition-colors">
                                                    ${highlightedText}
                                                </span>
                                            </div>`;
                                        }
                                    }
                                });
                            }

                            // --- TAMPILAN DATA KREATOR ---
                            if (data.users.length > 0) {
                                if (html !== '') html += `<div class="border-t border-white/5 my-2 mx-2"></div>`;
                                data.users.forEach(user => {
                                    let regex = new RegExp(`(${escapeRegExp(qTrim)})`, 'gi');
                                    let highlightedUser = user.name.replace(regex, `<span class="text-white">$1</span>`);
                                    let foto = `assets/profiles/${user.img}`;
                                    let verifIcon = user.role === 'admin' ? '✅' : '';
                                    
                                    html += `
                                    <div class="flex items-center gap-3 p-3 hover:bg-emerald-500/[0.06] cursor-pointer rounded-2xl transition-all duration-150" 
                                         onclick="location.href='user_space.php?UserID=${user.id}'">
                                        <img src="${foto}" class="w-8 h-8 rounded-full object-cover border border-white/10" onerror="this.src='assets/profiles/default.png'">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-500">
                                                @${highlightedUser} <span class="ml-1">${verifIcon}</span>
                                            </span>
                                            <span class="text-[9px] text-emerald-400 font-bold uppercase tracking-wider mt-0.5">Creator</span>
                                        </div>
                                    </div>`;
                                });
                            }

                            if (html === '') {
                                html = `<div class="p-8 text-center text-slate-500 text-xs italic tracking-wide">Tidak ada hasil untuk "${escapeHtml(qTrim)}"</div>`;
                            }

                            resultsBox.innerHTML = html;
                        });
                } else {
                    resultsBox.style.opacity = '0';
                    resultsBox.style.transform = 'translateY(-8px) scale(0.99)';
                    setTimeout(() => {
                        resultsBox.classList.remove('active');
                        resultsBox.classList.add('hidden');
                    }, 150);
                }
            });

            // --- FUNCTIONS HELPER ---
            window.fillSearchAndSubmit = function(value) {
                searchInput.value = value;
                document.getElementById('search-form').submit();
            }

            function escapeRegExp(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            function escapeHtml(text) {
                return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            }

            function escapeJs(text) {
                return text.replace(/'/g, "\\'");
            }

            // Tutup Dropdown jika klik di luar area Search Wrapper
            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) {
                    resultsBox.style.opacity = '0';
                    resultsBox.style.transform = 'translateY(-8px) scale(0.99)';
                    setTimeout(() => {
                        resultsBox.classList.remove('active');
                        resultsBox.classList.add('hidden');
                    }, 180);
                }
            });
        });
    </script>