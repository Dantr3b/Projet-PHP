<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("config.php"); // Connexion à la base de données
session_start();

// Vérification et récupération de l'ID de l'article
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "ID d'article invalide.";
    exit();
}

// Récupération de l'utilisateur connecté
$user_id = $_SESSION['id'] ?? 0;

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

// Vérification si l'utilisateur a acheté cet article
$purchased = false;
if ($user_id > 0) {
    $stmt_purchase = mysqli_prepare($conn, "SELECT id FROM `order` WHERE seller_id = ? AND article_id = ? AND status IN ('en cours', 'livré')");
    mysqli_stmt_bind_param($stmt_purchase, "ii", $user_id, $id);
    mysqli_stmt_execute($stmt_purchase);
    $purchase_result = mysqli_stmt_get_result($stmt_purchase);

    if ($purchase_result && mysqli_fetch_assoc($purchase_result)) {
        $purchased = true; // L'utilisateur a acheté l'article
    }
    mysqli_stmt_close($stmt_purchase);
}

// Gestion de l'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = intval($_POST['quantity']);
    if ($quantity > 0 && $quantity <= $article['stock']) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] += $quantity;
        } else {
            $_SESSION['cart'][$id] = $quantity;
        }
        $success_message = "Article ajouté au panier avec succès.";
    } else {
        $error_message = "Quantité invalide ou non disponible.";
    }
}

// Récupération des commentaires et notes
$stmt_reviews = mysqli_prepare($conn, "SELECT r.comment, r.rating, r.created_at, u.username 
                                       FROM review r 
                                       JOIN User u ON r.user_id = u.id 
                                       WHERE r.article_id = ? 
                                       ORDER BY r.created_at DESC");
mysqli_stmt_bind_param($stmt_reviews, "i", $id);
mysqli_stmt_execute($stmt_reviews);
$reviews_result = mysqli_stmt_get_result($stmt_reviews);
$reviews = mysqli_fetch_all($reviews_result, MYSQLI_ASSOC);

// Gestion de l'ajout d'un commentaire et d'une note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && $purchased) {
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5 && !empty($comment)) {
        $stmt_insert_review = mysqli_prepare($conn, "INSERT INTO review (user_id, article_id, rating, comment) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_insert_review, "iiis", $user_id, $id, $rating, $comment);
        if (mysqli_stmt_execute($stmt_insert_review)) {
            $success_message = "Votre avis a été ajouté avec succès.";
            header("Location: detail.php?id=$id");
            exit();
        } else {
            $error_message = "Une erreur est survenue lors de l'ajout de votre avis.";
        }
        mysqli_stmt_close($stmt_insert_review);
    } else {
        $error_message = "Veuillez fournir une note entre 1 et 5 et un commentaire.";
    }
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

        <!-- Section des commentaires et notes -->
        <div class="mt-5">
            <h3>Commentaires et Notes</h3>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="border p-3 mb-3">
                        <h6><?php echo htmlspecialchars($review['username']); ?> - 
                            <span class="text-warning">Note : <?php echo $review['rating']; ?>/5</span>
                        </h6>
                        <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        <small class="text-muted"><?php echo $review['created_at']; ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun commentaire pour cet article.</p>
            <?php endif; ?>
        </div>
        
        <!-- Formulaire d'ajout de commentaire -->
        <?php if ($purchased): ?>
            <div class="mt-4">
                <h4>Laissez un avis</h4>
                <form method="post">
                    <div class="mb-3">
                        <label for="rating" class="form-label">Note (1-5)</label>
                        <select name="rating" id="rating" class="form-select" required>
                            <option value="">Sélectionner une note</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="comment" class="form-label">Commentaire</label>
                        <textarea name="comment" id="comment" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-primary">Envoyer</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                <p>Vous devez avoir acheté cet article pour laisser un avis.</p>
            </div>
        <?php endif; ?>


        <div class="text-center mt-5">
            <a href="collection.php" class="btn btn-secondary">Retour à la collection</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
