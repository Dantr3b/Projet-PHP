<?php
require_once("config.php");
session_start();

// Vérification de l'utilisateur connecté
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Récupération des articles favoris
$query_favorites = "
    SELECT a.id, a.name, a.price, a.image, f.created_at 
    FROM favorites f 
    JOIN article a ON f.article_id = a.id 
    WHERE f.user_id = ? 
    ORDER BY f.created_at DESC";
$stmt_fav = mysqli_prepare($conn, $query_favorites);
mysqli_stmt_bind_param($stmt_fav, "i", $user_id);
mysqli_stmt_execute($stmt_fav);
$result_fav = mysqli_stmt_get_result($stmt_fav);
$favorites = mysqli_fetch_all($result_fav, MYSQLI_ASSOC);
mysqli_stmt_close($stmt_fav);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <h2 class="text-center">Mes Articles Favoris</h2>
        
        <?php if (!empty($favorites)): ?>
            <div class="row">
                <?php foreach ($favorites as $fav): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <img src="<?php echo htmlspecialchars(str_replace('../', '', $fav['image'])); ?>" 
                                 alt="<?php echo htmlspecialchars($fav['name']); ?>" 
                                 class="card-img-top">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo htmlspecialchars($fav['name']); ?></h5>
                                <p class="card-text"><strong><?php echo number_format($fav['price'], 2); ?> €</strong></p>
                                <p class="text-muted">Ajouté le : <?php echo date('d/m/Y', strtotime($fav['created_at'])); ?></p>
                                <a href="detail.php?id=<?php echo $fav['id']; ?>" class="btn btn-dark">Voir Détail</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center mt-4">Vous n'avez aucun article en favoris.</p>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="collection.php" class="btn btn-secondary">Retour à la Collection</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
