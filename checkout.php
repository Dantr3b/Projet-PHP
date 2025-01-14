<?php
require_once("config.php");
session_start();

// Vérification de l'utilisateur connecté
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Vérification du panier
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Récupération des articles du panier
$cart_items = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $query = "SELECT id, name, price, stock FROM article WHERE id IN ($ids)";
    $result = mysqli_query($conn, $query);

    while ($row = mysqli_fetch_assoc($result)) {
        $article_id = $row['id'];
        $quantity = $_SESSION['cart'][$article_id];

        // Vérification du stock
        if ($quantity > $row['stock']) {
            $error = "La quantité demandée pour l'article " . htmlspecialchars($row['name']) . " dépasse le stock disponible.";
            break;
        }

        $row['quantity'] = $quantity;
        $row['subtotal'] = $row['price'] * $quantity;
        $total_price += $row['subtotal'];
        $cart_items[] = $row;
    }
}

// Traitement du formulaire de validation
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($error)) {
    $address = trim($_POST['address']);
    $payment_method = trim($_POST['payment_method']);

    if (empty($address) || empty($payment_method)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        // Enregistrement de la commande
        foreach ($cart_items as $item) {
            $article_id = $item['id'];
            $quantity = $item['quantity'];
            $subtotal = $item['subtotal'];

            // Mise à jour du stock
            $new_stock = $item['stock'] - $quantity;
            $update_stock_query = "UPDATE article SET stock = ? WHERE id = ?";
            $update_stock_stmt = mysqli_prepare($conn, $update_stock_query);
            mysqli_stmt_bind_param($update_stock_stmt, "ii", $new_stock, $article_id);
            mysqli_stmt_execute($update_stock_stmt);
            mysqli_stmt_close($update_stock_stmt);

            // Création de la commande
            $stmt = mysqli_prepare($conn, "INSERT INTO `order` (article_id, seller_id, quantity, total_price, status) VALUES (?, ?, ?, ?, 'en cours')");
            mysqli_stmt_bind_param($stmt, "iiid", $article_id, $user_id, $quantity, $subtotal);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        // Rediriger vers une page de confirmation
        header("Location: confirmation.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de commande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <h2 class="text-center mb-4">Validation de commande</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Liste des articles du panier -->
        <div class="mb-4">
            <h4>Votre panier</h4>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Article</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo number_format($item['price'], 2); ?> €</td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo number_format($item['subtotal'], 2); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total :</strong></td>
                        <td><strong><?php echo number_format($total_price, 2); ?> €</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Formulaire de validation -->
        <form method="post" class="row g-3">
            <div class="col-md-12">
                <label for="address" class="form-label">Adresse de livraison</label>
                <textarea name="address" id="address" rows="3" class="form-control" required></textarea>
            </div>
            <div class="col-md-12">
                <label for="payment_method" class="form-label">Méthode de paiement</label>
                <select name="payment_method" id="payment_method" class="form-select" required>
                    <option value="">-- Choisissez une méthode --</option>
                    <option value="credit_card">Carte de crédit</option>
                    <option value="paypal">PayPal</option>
                    <option value="bank_transfer">Virement bancaire</option>
                </select>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-dark w-100">Valider la commande</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="cart.php" class="btn btn-secondary">Retour au panier</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
