<?php
require_once("config.php");
session_start();

// Vérifie si l'utilisateur est connecté
if (isset($_SESSION["id"])) {
    $user_id = $_SESSION["id"];
} else {
    header("Location: ../login.php"); // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
    exit();
}

// Prépare la requête pour récupérer les articles favoris de l'utilisateur
$get_favorites = "SELECT article_id FROM favorites WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $get_favorites);

if (!$stmt) {
    die("Erreur lors de la préparation de la requête : " . mysqli_error($conn));
}

// Associe l'ID de l'utilisateur à la requête
mysqli_stmt_bind_param($stmt, "i", $user_id);

// Exécute la requête
mysqli_stmt_execute($stmt);

// Récupère les résultats
$result = mysqli_stmt_get_result($stmt);

// Vérifie si des favoris existent
$favorites = [];
while ($row = mysqli_fetch_assoc($result)) {
    $favorites[] = $row['article_id'];
}

mysqli_stmt_close($stmt);

// Si aucun favori n'existe, afficher un message
if (empty($favorites)) {
    $message = "Vous n'avez pas encore ajouté d'articles à vos favoris.";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favoris</title>
</head>
<body>
    <h1>Vos Favoris</h1>

    <?php if (isset($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($favorites as $article_id): ?>
                <li>
                    <!-- Lien vers la page détail de l'article -->
                    <a href="detail.php?id=<?php echo $article_id; ?>">
                        Article ID : <?php echo htmlspecialchars($article_id); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</body>
</html>
