<?php
require_once("config.php");
session_start();

// Vérification de l'article
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($article_id <= 0) {
    die("Article invalide.");
}

// Vérification de l'utilisateur connecté
$user_id = $_SESSION['id'] ?? 0;
$is_logged_in = $user_id > 0;

// Récupération des informations de l'article
$stmt = mysqli_prepare($conn, "SELECT name, description FROM article WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $article_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);

if (!$article) {
    die("Article introuvable.");
}

// Vérification si l'utilisateur peut ajouter une note/commentaire
$can_review = false;
if ($is_logged_in) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS purchased FROM `order` WHERE article_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $article_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $purchase = mysqli_fetch_assoc($result);
    $can_review = $purchase['purchased'] > 0;

    // Vérifier si l'utilisateur a déjà laissé une review
    if ($can_review) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS already_reviewed FROM review WHERE article_id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $article_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $review_check = mysqli_fetch_assoc($result);
        $can_review = $review_check['already_reviewed'] == 0;
    }
}

// Ajout d'une note/commentaire
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_review']) && $can_review) {
    $rating = intval($_POST['rating']);
    $review = trim($_POST['review']);

    if ($rating < 1 || $rating > 5) {
        $error = "La note doit être entre 1 et 5.";
    } elseif (empty($review)) {
        $error = "Le commentaire ne peut pas être vide.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO review (user_id, article_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "iiis", $user_id, $article_id, $rating, $review);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: notes.php?id=" . $article_id);
            exit();
        } else {
            $error = "Erreur lors de l'ajout du commentaire.";
        }
    }
}

// Récupération des commentaires et notes
$stmt = mysqli_prepare($conn, "SELECT r.comment, r.rating, r.created_at, u.username 
                               FROM review r 
                               JOIN User u ON r.user_id = u.id 
                               WHERE r.article_id = ? 
                               ORDER BY r.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $article_id);
mysqli_stmt_execute($stmt);
$reviews_result = mysqli_stmt_get_result($stmt);
$reviews = mysqli_fetch_all($reviews_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes et commentaires</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <h2 class="text-center mb-4"><?php echo htmlspecialchars($article['name']); ?> - Notes et Commentaires</h2>

        <!-- Section des commentaires -->
        <div class="mb-4">
            <h4>Commentaires</h4>
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="border p-3 mb-3">
                        <h5><?php echo htmlspecialchars($review['username']); ?></h5>
                        <p>Note : <strong><?php echo $review['rating']; ?>/5</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        <small class="text-muted"><?php echo $review['created_at']; ?></small>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">Aucun commentaire pour cet article.</p>
            <?php endif; ?>
        </div>

        <!-- Section d'ajout d'un commentaire -->
        <?php if ($is_logged_in && $can_review): ?>
            <div class="mb-4">
                <h4>Ajouter un commentaire</h4>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="post" action="notes.php?id=<?php echo $article_id; ?>">
                    <div class="mb-3">
                        <label for="rating" class="form-label">Note (1 à 5)</label>
                        <select name="rating" id="rating" class="form-select" required>
                            <option value="">-- Sélectionnez une note --</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="review" class="form-label">Commentaire</label>
                        <textarea name="review" id="review" rows="3" class="form-control" required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-dark">Soumettre</button>
                </form>
            </div>
        <?php elseif ($is_logged_in): ?>
            <p class="text-muted">Vous avez déjà laissé un commentaire ou n'avez pas acheté cet article.</p>
        <?php else: ?>
            <p class="text-muted">Connectez-vous pour ajouter un commentaire.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
