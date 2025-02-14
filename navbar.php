<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <style>
        body {
            padding-top: 70px; /* Ajustez en fonction de la hauteur de votre navbar */
        }

        .navbar .form-control {
            width: 250px; /* Réduction de la largeur */
            height: 45px; /* Hauteur plus petite */
            font-size: 0.9rem; /* Taille de texte réduite */
        }

        .navbar .btn {
            height: 35px;
            font-size: 0.9rem;
        }
    </style>

    <?php
    // Vérifie si la session est déjà démarrée avant de l'initialiser
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once("config.php"); // Connexion à la base de données


    if (isset($_SESSION['id'])) {
        $usernavbar_id = $_SESSION['id'];
    }

    $query_usernavbar = "SELECT username, photo, role, email, balance FROM user WHERE id = ?";
    $stmt_usernavbar = mysqli_prepare($conn, $query_usernavbar);
    mysqli_stmt_bind_param($stmt_usernavbar, "i", $usernavbar_id);
    mysqli_stmt_execute($stmt_usernavbar);
    $result_usernavbar = mysqli_stmt_get_result($stmt_usernavbar);
    $usernavbar = mysqli_fetch_assoc($result_usernavbar);
    ?>
    
    <div class="container">
        <a class="navbar-brand" href="/Projet-PHP/index.php">Cave d'Exception</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="/Projet-PHP/collection.php">Collection</a></li>
                <?php if (!isset($_SESSION['username'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/login.php">Connexion</a></li>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/register.php">Inscription</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/favoris.php">Favoris</a></li>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/cart.php">Panier</a></li>
                    <?php if ($usernavbar['role'] === 'seller'): ?>
                        <li class="nav-item"><a class="nav-link" href="/Projet-PHP/sellers/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/account.php?id=<?php echo $usernavbar_id; ?>">Mon Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/logout.php">Déconnexion</a></li>
                <?php endif; ?>
            </ul>

            <!-- Barre de recherche alignée à droite -->
            <form class="d-flex ms-3 position-relative" action="/Projet-PHP/search.php" method="get">
                <input class="form-control rounded-pill shadow-sm" 
                    type="search" 
                    placeholder="Rechercher une bouteille / vendeur" 
                    name="query" 
                    style="padding-right: 15px; width: 310px; height: 35px;">
            </form>
        </div>
    </div>
</nav>
