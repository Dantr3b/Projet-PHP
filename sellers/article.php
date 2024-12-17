<?php
require_once("../config.php"); // Connexion à la base de données
session_start();

// Vérification de l'authentification et du rôle "seller"
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

$error = "";
$success = "";

// Suppression d'un article
if (isset($_POST['delete_article'])) {
    $article_id = intval($_POST['article_id']);
    $seller_id = $_SESSION['id'];

    // Vérifie que l'article appartient bien au vendeur connecté
    $stmt = mysqli_prepare($conn, "DELETE FROM article WHERE id = ? AND author_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $article_id, $seller_id);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Article supprimé avec succès.";
    } else {
        $error = "Erreur lors de la suppression de l'article.";
    }
    mysqli_stmt_close($stmt);
}

// Récupération des articles du vendeur
$seller_id = $_SESSION['id'];
$stmt = mysqli_prepare($conn, "SELECT id, name, price, stock, created_at FROM article WHERE author_id = ?");
mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vos articles</title>
</head>
<body>
    <h2>Vos articles</h2>

    <!-- Messages -->
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <!-- Tableau des articles -->
    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Créé le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($article = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $article['id']; ?></td>
                    <td><?php echo htmlspecialchars($article['name']); ?></td>
                    <td><?php echo number_format($article['price'], 2); ?> €</td>
                    <td><?php echo $article['stock']; ?></td>
                    <td><?php echo $article['created_at']; ?></td>
                    <td>
                        <!-- Lien pour modifier -->
                        <a href="edit_article.php?id=<?php echo $article['id']; ?>">Modifier</a> |
                        
                        <!-- Formulaire pour supprimer -->
                        <form method="post" action="articles.php" style="display:inline;">
                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                            <button type="submit" name="delete_article" onclick="return confirm('Voulez-vous vraiment supprimer cet article ?');">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <p><a href="sell.php">Ajouter un nouvel article</a></p>
    <p><a href="dashboard.php">Retour au tableau de bord</a></p>
</body>
</html>
