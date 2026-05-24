<?php
// KEAMANAN: Memastikan file ini hanya bisa diakses jika user sudah login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah koneksi sudah ada, jika belum include database
if (!isset($conn)) {
    include 'config/database.php';
}
?>

<style>
    .search-container {
        position: relative;
        width: 100%;
        max-width: 600px;
    }

    #search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(15, 15, 15, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        margin-top: 10px;
        z-index: 9999;
        display: none;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }

    .result-item {
        padding: 12px 15px;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: 0.3s;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #fff;
        text-decoration: none;
    }

    .result-item:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .result-item img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 12px;
        object-fit: cover;
    }

    .result-info {
        display: flex;
        flex-direction: column;
    }

    .result-name {
        font-weight: bold;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .result-username {
        font-size: 12px;
        color: #aaa;
    }

    .badge-verif {
        width: 16px;
        height: 16px;
    }

    .suggestion-text {
        color: #3498db;
        font-weight: 500;
    }
</style>

<div class="search-container">
    <input type="text" id="smart-search" placeholder="Search visuals..." autocomplete="off">
    <div id="search-results"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('smart-search');
    const resultsBox = document.getElementById('search-results');

    searchInput.addEventListener('input', function() {
        let query = this.value;

        if (query.length > 0) {
            // Mengambil data dari search_ajax.php yang sudah kita perbaiki tadi
            fetch(`config/search_ajax.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsBox.innerHTML = '';
                    resultsBox.style.display = 'block';

                    if (data.length > 0 && data[0].type !== 'empty') {
                        data.forEach(item => {
                            let div = document.createElement('div');
                            
                            if (item.type === 'user') {
                                // Template untuk hasil Profil User
                                let badgeHtml = '';
                                if (item.verif_status === 'admin') {
                                    badgeHtml = `<img src="assets/icons/verif2.png" class="badge-verif" title="Official Admin">`;
                                } else if (item.verif_status === 'verified') {
                                    badgeHtml = `<img src="assets/icons/verif1.png" class="badge-verif" title="Verified Artist">`;
                                }

                                div.innerHTML = `
                                    <a href="profile.php?id=${item.UserID}" class="result-item">
                                        <img src="assets/profiles/${item.FotoProfil || 'default.png'}" alt="">
                                        <div class="result-info">
                                            <span class="result-name">${item.NamaLengkap} ${badgeHtml}</span>
                                            <span class="result-username">@${item.Username}</span>
                                        </div>
                                    </a>
                                `;
                            } else if (item.type === 'suggestion') {
                                // Template untuk saran kata kunci (Pinterest Style)
                                div.innerHTML = `
                                    <div class="result-item" onclick="document.getElementById('smart-search').value='${item.Keyword}';">
                                        <span class="suggestion-text">🔍 ${item.Keyword}</span>
                                    </div>
                                `;
                            }
                            resultsBox.appendChild(div);
                        });
                    } else {
                        resultsBox.innerHTML = `<div class="result-item">Tidak ditemukan hasil.</div>`;
                    }
                });
        } else {
            resultsBox.style.display = 'none';
        }
    });

    // Menutup dropdown jika klik di luar area pencarian
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });
});
</script>