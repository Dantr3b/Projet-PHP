
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier</title>
</head>
<body>
    <a href="cart/validate.php">valider le panier</a>
    <br>
    <a href="javascript:history.back()">Retour</a>
</body>
</html>