<?php
require_once("../config.php");
session_start();

// Vérification si l'utilisateur est connecté
$user_id = $_SESSION['id'] ?? 0;

if ($user_id <= 0) {
    echo "Vous devez être connecté pour valider votre panier.";
    exit();
}

// Vérification si le panier est vide
if (empty($_SESSION['cart'])) {
    echo "Votre panier est vide.";
    exit();
}

// Récupération des articles du panier
$cart_items = $_SESSION['cart'];
$ids = implode(',', array_keys($cart_items)); // Obtenir les IDs des articles
$query = "SELECT id, name, price, stock FROM article WHERE id IN ($ids)";
$result = mysqli_query($conn, $query);

// Stocker les détails des articles
$articles = [];
while ($row = mysqli_fetch_assoc($result)) {
    $articles[$row['id']] = $row;
}

// Calcul du total et vérification des stocks
$total = 0;
$errors = [];
foreach ($cart_items as $article_id => $quantity) {
    if (isset($articles[$article_id])) {
        $article = $articles[$article_id];
        // Vérifier si le stock est suffisant
        if ($article['stock'] < $quantity) {
            $errors[] = "L'article '{$article['name']}' n'a pas assez de stock (disponible : {$article['stock']}).";
        } else {
            $total += $article['price'] * $quantity; // Ajouter au total
        }
    } else {
        $errors[] = "L'article avec l'ID $article_id est introuvable.";
    }
}

// Gestion de la validation du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    // Insérer les détails des articles dans `order_items`
    

    


    foreach ($cart_items as $article_id => $quantity) {

        $get_seller_id = "SELECT author_id FROM article WHERE id = ?";
        $stmt = mysqli_prepare($conn, $get_seller_id);
        // Associe l'ID de l'article à la requête
        mysqli_stmt_bind_param($stmt, "i", $article_id);

        // Exécute la requête
        mysqli_stmt_execute($stmt);
        // Récupère le résultat
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $seller_id = $row['author_id']; // Récupération de l'ID de l'auteur
        } else {
            die("Auteur introuvable pour l'article ID : $article_id");
        }


        // Insérer la commande dans la table `orders`
        $insert_order_query = "INSERT INTO `order` (buyer_id, seller_id, article_id, quantity, status, total_price, created_at) 
                       VALUES (?, ?, ?, ?, 'en cours', ?, NOW())";
        $stmt = mysqli_prepare($conn, $insert_order_query);
        // Associe les paramètres à la requête
        mysqli_stmt_bind_param($stmt, "iiiid", $user_id, $seller_id, $article_id, $quantity, $total);

        // Exécute la requête
        if (mysqli_stmt_execute($stmt)) {
            echo "Commande insérée avec succès.";
        } else {
            echo "Erreur lors de l'insertion de la commande : " . mysqli_stmt_error($stmt);
        }
             
        // Réduire le stock
        $update_stock_query = "UPDATE article SET stock = stock - ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_stock_query);
        mysqli_stmt_bind_param($stmt, "ii", $quantity, $article_id);
        mysqli_stmt_execute($stmt);
    }

    // Vider le panier après validation
    $_SESSION['cart'] = [];
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Valider votre panier</title>
</head>
<body>
    <h2>Valider votre panier</h2>

    <!-- Affichage des erreurs -->
    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Affichage des articles dans le panier -->
    <?php if (!empty($cart_items)): ?>
        <table border="1" cellpadding="10">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prix unitaire</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $article_id => $quantity): ?>
                    <?php if (isset($articles[$article_id])): ?>
                        <?php $article = $articles[$article_id]; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($article['name']); ?></td>
                            <td><?php echo number_format($article['price'], 2); ?> €</td>
                            <td><?php echo $quantity; ?></td>
                            <td><?php echo number_format($article['price'] * $quantity, 2); ?> €</td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Total :</strong></td>
                    <td><strong><?php echo number_format($total, 2); ?> €</strong></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- Formulaire de validation -->
    <?php if (empty($errors)): ?>
        <form method="post" action="validate.php">
            <button type="submit">Confirmer la commande</button>
        </form>
    <?php endif; ?>

    <p><a href="../cart.php">Retour au panier</a></p>
</body>
</html>
