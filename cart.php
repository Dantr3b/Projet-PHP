
<?php
require_once("config.php"); // Connexion à la base de données
require_once("carttools.php"); // Fonctions
// Initialisation du panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Récupération de l'utilisateur connecté
$user_id = $_SESSION['id'] ?? 0;

if ($user_id <= 0) {
    echo "Vous devez être connecté pour voir cet article.";
    exit();
}

// Récupération des détails de l'article
displayCart();