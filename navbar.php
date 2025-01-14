<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <style>body {
    padding-top: 70px; /* Ajustez en fonction de la hauteur de votre navbar */
}
<?php
require_once("config.php");
session_start();


if (isset($_SESSION['id'])) {
    // Récupération des informations de l'utilisateur
    $usernavbar_id = $_SESSION['id'];
}

$query_usernavbar = "SELECT username, photo,role, email, balance FROM user WHERE id = ?";
$stmt_usernavbar = mysqli_prepare($conn, $query_usernavbar);
mysqli_stmt_bind_param($stmt_usernavbar, "i", $usernavbar_id);
mysqli_stmt_execute($stmt_usernavbar);
$result_usernavbar = mysqli_stmt_get_result($stmt_usernavbar);
$usernavbar = mysqli_fetch_assoc($result_usernavbar);


?>
</style>
    <div class="container">
        <a class="navbar-brand" href="/Projet-PHP/index.php">Cave d'Exception</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/Projet-PHP/collection.php">Collection</a></li>
                <?php if (!isset($_SESSION['username'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/login.php">Connexion</a></li>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/register.php">Inscription</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/cart.php">Panier</a></li>
                    <?php if ($usernavbar['role'] === 'seller'): ?>
                        <li class="nav-item"><a class="nav-link" href="/Projet-PHP/sellers/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/account.php?id=<?php echo $usernavbar_id; ?>">Mon Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/logout.php">Déconnexion</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
