<?php
require_once("config.php"); // Connexion à la base de données
session_start();

// Vérification de l'utilisateur connecté
$user_id = $_SESSION['id'] ?? 0;
if ($user_id <= 0) {
    echo "Vous devez être connecté pour accéder au panier.";
    exit();
}

// Initialisation du panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Gestion de la suppression d'un article du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $article_id = intval($_POST['article_id']);
    if (isset($_SESSION['cart'][$article_id])) {
        unset($_SESSION['cart'][$article_id]);
        $success_message = "Article retiré du panier avec succès.";
    }
}

// Gestion de la mise à jour des quantités dans le panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $article_id => $quantity) {
        $article_id = intval($article_id);
        $quantity = intval($quantity);
        if ($quantity > 0) {
            $_SESSION['cart'][$article_id] = $quantity;
        } else {
            unset($_SESSION['cart'][$article_id]);
        }
    }
    $success_message = "Quantités mises à jour avec succès.";
}

// Récupération des articles du panier depuis la base de données
$cart_items = [];
if (!empty($_SESSION['cart'])) {
    $article_ids = implode(',', array_keys($_SESSION['cart']));
    $query = "SELECT id, name, price, image, stock FROM article WHERE id IN ($article_ids)";
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $row['quantity'] = $_SESSION['cart'][$row['id']];
        $cart_items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <h1 class="text-center mb-4">Mon Panier</h1>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success text-center">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($cart_items)): ?>
            <form method="post">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Prix Unitaire</th>
                            <th>Quantité</th>
                            <th>Sous-Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_price = 0;
                        foreach ($cart_items as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total_price += $subtotal;
                        ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars(str_replace('../', '', $item['image'])); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         width="100" class="img-fluid rounded">
                                </td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo number_format($item['price'], 2); ?> €</td>
                                <td>
                                    <input type="number" name="quantities[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>" class="form-control">
                                </td>
                                <td><?php echo number_format($subtotal, 2); ?> €</td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="article_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_from_cart" class="btn btn-danger btn-sm">
                                            Retirer
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total :</strong></td>
                            <td colspan="2"><strong><?php echo number_format($total_price, 2); ?> €</strong></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="text-center">
                    <button type="submit" name="update_cart" class="btn btn-primary">Mettre à jour le panier</button>
                    <a href="checkout.php" class="btn btn-success">Passer à la caisse</a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <p>Votre panier est vide.</p>
            </div>
            <div class="text-center">
                <a href="collection.php" class="btn btn-secondary">Retour à la collection</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
