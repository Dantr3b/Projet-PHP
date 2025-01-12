<?php
require_once("../config.php");
session_start();

// Vérifie si l'utilisateur est connecté et s'il est admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Suppression d'un article
if (isset($_POST['delete_article'])) {
    $article_id = intval($_POST['article_id']);
    $delete_query = "DELETE FROM article WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $article_id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "L'article a été supprimé avec succès.";
    } else {
        $error = "Erreur lors de la suppression de l'article.";
    }
    mysqli_stmt_close($stmt);
}

// Récupération de tous les articles
$articles_query = "SELECT id, name, description, price, category, vintage, region, stock FROM article";
$result = mysqli_query($conn, $articles_query);

// Stocker les articles dans un tableau
$articles = [];
while ($row = mysqli_fetch_assoc($result)) {
    $articles[] = $row;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Articles</title>
</head>
<body>
    <h1>Gestion des Articles</h1>

    <!-- Messages de succès ou d'erreur -->
    <?php if (!empty($message)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Prix</th>
                <th>Catégorie</th>
                <th>Année</th>
                <th>Région</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articles as $article): ?>
                <tr>
                    <td><?php echo htmlspecialchars($article['id']); ?></td>
                    <td><?php echo htmlspecialchars($article['name']); ?></td>
                    <td><?php echo htmlspecialchars(substr($article['description'], 0, 50)) . '...'; ?></td>
                    <td><?php echo number_format($article['price'], 2); ?> €</td>
                    <td><?php echo htmlspecialchars($article['category']); ?></td>
                    <td><?php echo htmlspecialchars($article['vintage']); ?></td>
                    <td><?php echo htmlspecialchars($article['region']); ?></td>
                    <td><?php echo htmlspecialchars($article['stock']); ?></td>
                    <td>
                        <!-- Lien pour modifier -->
                        <a href="edit_article.php?id=<?php echo $article['id']; ?>">Modifier</a>

                        <!-- Formulaire pour supprimer -->
                        <form method="post" action="articles.php" style="display: inline;">
                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                            <button type="submit" name="delete_article" onclick="return confirm('Voulez-vous vraiment supprimer cet article ?');">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="../admin/dashboard.php">Retour au tableau de bord</a></p>
</body>
</html>
