<?php
require_once("config.php"); // Connexion à la base de données
session_start();

// Vérification et récupération de l'ID de l'article
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialisation du panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($id <= 0) {
    echo "ID d'article invalide.";
    exit();
}

// Récupération de l'utilisateur connecté
$user_id = $_SESSION['id'] ?? 0;

if ($user_id <= 0) {
    echo "Vous devez être connecté pour voir cet article.";
    exit();
}

// Récupération des détails de l'article
$stmt = mysqli_prepare($conn, "SELECT id, name, description, price, category, vintage, region, image, stock FROM article WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);

// Si l'article n'existe pas, afficher un message
if (!$article) {
    echo "Article introuvable.";
    exit();
}

// Gestion de l'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = intval($_POST['quantity']);
    if ($quantity > 0 && $quantity <= $article['stock']) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] += $quantity; // Ajouter la quantité au panier existant
        } else {
            $_SESSION['cart'][$id] = $quantity; // Ajouter un nouvel article au panier
        }
        $success_message = "Article ajouté au panier avec succès.";
    } else {
        $error_message = "Quantité invalide ou non disponible.";
    }
}

// Vérification si l'article est déjà dans les favoris
$fav_stmt = mysqli_prepare($conn, "SELECT id FROM favorites WHERE user_id = ? AND article_id = ?");
mysqli_stmt_bind_param($fav_stmt, "ii", $user_id, $id);
mysqli_stmt_execute($fav_stmt);
$fav_result = mysqli_stmt_get_result($fav_stmt);
$is_favorited = mysqli_fetch_assoc($fav_result) ? true : false;
mysqli_stmt_close($fav_stmt);

// Gestion de l'ajout aux favoris
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_favorites']) && !$is_favorited) {
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO favorites (user_id, article_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $id);
    if (mysqli_stmt_execute($insert_stmt)) {
        $is_favorited = true; // Mettre à jour l'état
    }
    mysqli_stmt_close($insert_stmt);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <div class="row">
            <!-- Colonne pour l'image -->
            <div class="col-md-6">
                <img src="<?php echo htmlspecialchars(str_replace('../', '', $article['image'] ?? "")); ?>" 
                     alt="<?php echo htmlspecialchars($article['name']); ?>" 
                     class="img-fluid rounded">
            </div>

            <!-- Colonne pour les détails -->
            <div class="col-md-6">
                <h1><?php echo htmlspecialchars($article['name']); ?></h1>
                <p><strong>Prix :</strong> <?php echo number_format($article['price'], 2); ?> €</p>
                <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($article['category'] ?? ""); ?></p>
                <p><strong>Année :</strong> <?php echo htmlspecialchars($article['vintage'] ?? ""); ?></p>
                <p><strong>Région :</strong> <?php echo htmlspecialchars($article['region'] ?? ""); ?></p>
                <p><strong>Stock disponible :</strong> <?php echo htmlspecialchars($article['stock']); ?></p>
                <p><strong>Description :</strong></p>
                <p><?php echo nl2br(htmlspecialchars($article['description'] ?? "")); ?></p>

                <!-- Message de succès ou d'erreur -->
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php elseif (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Bouton ajouter aux favoris -->
                <form method="post" class="mb-3">
                    <?php if ($is_favorited): ?>
                        <p class="text-success">Cet article est déjà dans vos favoris.</p>
                    <?php else: ?>
                        <button type="submit" name="add_to_favorites" class="btn btn-warning">Ajouter aux favoris</button>
                    <?php endif; ?>
                </form>

                <!-- Formulaire pour ajouter au panier -->
                <form method="post">
                    <div class="input-group mb-3">
                        <label for="quantity" class="form-label me-3">Quantité :</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" 
                               value="1" min="1" max="<?php echo $article['stock']; ?>">
                        <button type="submit" name="add_to_cart" class="btn btn-success">Ajouter au panier</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="collection.php" class="btn btn-secondary">Retour à la collection</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
