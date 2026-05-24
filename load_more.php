<?php
include 'config/database.php';
session_start();

$page = $_GET['page'] ?? 1;
$limit = 12; // Sekali scroll narik 12 foto
$offset = ($page - 1) * $limit;

$sql = "SELECT foto.*, user.Username, user.FotoProfil 
        FROM foto 
        JOIN user ON foto.UserID = user.UserID 
        ORDER BY foto.FotoID DESC LIMIT $limit OFFSET $offset";
$query = mysqli_query($conn, $sql);

while ($data = mysqli_fetch_array($query)): 
    // COPAS bagian <div> grid-item kamu yang ada di index.php ke sini
    // (Mulai dari <div class="group relative..."> sampai tutupnya)
    ?>
    <div class="group relative bg-slate-900 rounded-[2rem] p-3 ...">
        </div>
<?php endwhile; ?>