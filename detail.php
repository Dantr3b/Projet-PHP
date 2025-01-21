<?php
// Vérifie si la session est déjà démarrée avant de l'initialiser
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("config.php"); // Connexion à la base de données

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

<?php
                    // Vérification si l'article est déjà dans les favoris
                    $favorited = false;
                    if ($user_id > 0) {
                        $stmt_fav_check = mysqli_prepare($conn, "SELECT id FROM favorites WHERE user_id = ? AND article_id = ?");
                        mysqli_stmt_bind_param($stmt_fav_check, "ii", $user_id, $id);
                        mysqli_stmt_execute($stmt_fav_check);
                        $fav_result = mysqli_stmt_get_result($stmt_fav_check);
                        if (mysqli_fetch_assoc($fav_result)) {
                            $favorited = true; // L'article est déjà dans les favoris
                        }
                        mysqli_stmt_close($stmt_fav_check);
                    }

                    // Gestion de l'ajout/suppression des favoris
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_favorite'])) {
                        if ($favorited) {
                            $stmt_remove_fav = mysqli_prepare($conn, "DELETE FROM favorites WHERE user_id = ? AND article_id = ?");
                            mysqli_stmt_bind_param($stmt_remove_fav, "ii", $user_id, $id);
                            mysqli_stmt_execute($stmt_remove_fav);
                            $favorited = false;
                        } else {
                            $stmt_add_fav = mysqli_prepare($conn, "INSERT INTO favorites (user_id, article_id, created_at) VALUES (?, ?, NOW())");
                            mysqli_stmt_bind_param($stmt_add_fav, "ii", $user_id, $id);
                            mysqli_stmt_execute($stmt_add_fav);
                            $favorited = true;
                        }
                        header("Location: detail.php?id=$id");
                        exit();
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
    <div class="card shadow-lg border-0 rounded-4 p-4">
        <div class="card-body">
            <h1 class="card-title text-dark fw-bold mb-3"><?php echo htmlspecialchars($article['name']); ?></h1>

            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-muted">Prix :</span>
                    <span class="text-success fs-4"><?php echo number_format($article['price'], 2); ?> €</span>
                </li>

                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-muted">Catégorie :</span>
                    <span class="text-dark"><?php echo htmlspecialchars($article['category'] ?? ""); ?></span>
                </li>

                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-muted">Année :</span>
                    <span class="text-dark"><?php echo htmlspecialchars($article['vintage'] ?? ""); ?></span>
                </li>

                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-muted">Région :</span>
                    <span class="text-dark"><?php echo htmlspecialchars($article['region'] ?? ""); ?></span>
                </li>

                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-muted">Stock disponible :</span>
                    <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($article['stock']); ?></span>
                </li>
            </ul>

            <div class="mt-4">
                <h5 class="fw-bold text-dark">Description :</h5>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($article['description'] ?? "")); ?></p>
            </div>
        </div>
         <!-- Message de succès ou d'erreur -->
         <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php elseif (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                
                <!-- Formulaire pour ajouter au panier -->
                <?php if ($user_id == 0): ?>
                    <p class="text-muted">Connectez-vous pour ajouter cet article à votre panier.</p>
                <?php else: ?>
                    <form method="post">
                        <div class="input-group mb-3">
                            <label for="quantity" class="form-label me-3">Quantité :</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" 
                                value="1" min="1" max="<?php echo $article['stock']; ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-success">Ajouter au panier</button>
                        </div>
                    </form>
                <?php endif; ?>
                    <!-- Bouton Ajouter aux Favoris -->
                    <form method="post">
                        <button type="submit" name="toggle_favorite" class="btn btn-outline-primary">
                            <?php echo $favorited ? 'Retirer des Favoris' : 'Ajouter aux Favoris'; ?>
                        </button>
                    </form>
            </div>
        </div>
    </div>
</div>


               

        
        <!-- Section des commentaires et notes -->
        <div class="mt-4">
            <h3 class="text-center">Commentaires et Notes</h3><br><br>
            <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="border p-3 mb-3 mx-auto" style="max-width: 600px;">
                <h6><?php echo htmlspecialchars($review['username']); ?> - 
                    <span class="text-warning">Note : <?php echo $review['rating']; ?>/5</span>
                </h6>
                <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                <small class="text-muted"><?php echo $review['created_at']; ?></small>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="text-center">Aucun commentaire pour cet article.</p>
            <?php endif; ?>
        </div>
        
        <!-- Formulaire d'ajout de commentaire -->
        <?php if ($purchased): ?>
            <div class="mt-3 mx-auto" style="max-width: 600px;">
            <h4 class="text-center">Laissez un avis</h4>
            <form method="post">
                <div class="mb-2">
                <label for="rating" class="form-label">Note (1-5)</label>
                <select name="rating" id="rating" class="form-select" required>
                    <option value="">Sélectionner une note</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                </div>
                <div class="mb-2">
                <label for="comment" class="form-label">Commentaire</label>
                <textarea name="comment" id="comment" class="form-control" rows="3" required></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary">Envoyer</button>
            </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4 mx-auto" style="max-width: 600px;">
            <p class="text-center">Vous devez avoir acheté cet article pour laisser un avis.</p>
            </div>
        <?php endif; ?>


        <div class="text-center mt-5">
            <a href="collection.php" class="btn btn-secondary">Retour à la collection</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
