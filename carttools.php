<?php
require_once("config.php"); // Connexion à la base de données
session_start();

// Initialisation du panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ajouter un article au panier
// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $article_id = intval($_POST['id']);
    $quantity = intval($_POST['quantity']);

    if ($article_id <= 0 || $quantity <= 0) {
        echo json_encode(["success" => false, "message" => "Données invalides."]);
        exit();
    }

    // Initialisation du panier
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Ajouter ou mettre à jour l'article dans le panier
    if (isset($_SESSION['cart'][$article_id])) {
        $_SESSION['cart'][$article_id] += $quantity;
    } else {
        $_SESSION['cart'][$article_id] = $quantity;
    }

    // Réponse JSON de succès
    echo json_encode(["success" => true]);
    exit();
}






// Afficher le contenu du panier
function displayCart() {
    global $conn; // Utiliser la connexion à la base de données

    if (!empty($_SESSION['cart'])) {
        $ids = implode(',', array_keys($_SESSION['cart'])); // Convertir les IDs du panier en liste
        $query = "SELECT id, name FROM article WHERE id IN ($ids)";
        $result = mysqli_query($conn, $query);

        // Stocker les noms des articles dans un tableau associatif
        $articles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $articles[$row['id']] = $row['name'];
        }

        // Afficher les articles du panier
        foreach ($_SESSION['cart'] as $article_id => $quantity) {
            $name = isset($articles[$article_id]) ? $articles[$article_id] : "Article inconnu";
            echo "Nom de l'article : $name - Quantité : $quantity<br>";
        }
    } else {
        echo "Votre panier est vide.";
    }
}

?>

<!-- Exemple d'uttilisation:


// Appel de la fonction pour ajouter un article
addToCart(101, 2); // Ajoute l'article avec l'ID 101 et une quantité de 2
addToCart(102, 1); // Ajoute l'article avec l'ID 102 et une quantité de 1

// Affichage du contenu du panier
displayCart(); // Affiche les articles et leurs quantités actuelles -->


