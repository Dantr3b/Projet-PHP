<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos articles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php
    // Vérifie si la session est déjà démarrée avant de l'initialiser
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    require_once("../config.php"); // Connexion à la base de données
    include("../navbar.php"); 
            
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
    $stmt = mysqli_prepare($conn, "SELECT id, name, description ,price, stock, created_at FROM article WHERE author_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $seller_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    ?>

    <div class="container my-5">
        <h2 class="text-center mb-4">Vos articles</h2>

        <!-- Messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Tableau des articles -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Description</th>
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
                            <td><?php echo htmlspecialchars($article['description']); ?></td>
                            <td><?php echo number_format($article['price'], 2); ?> €</td>
                            <td><?php echo $article['stock']; ?></td>
                            <td><?php echo $article['created_at']; ?></td>
                            <td>
                                <!-- Lien pour modifier -->
                                <a href="edit_article.php?id=<?php echo $article['id']; ?>" class="btn btn-primary btn-sm">Modifier</a>
                                
                                <!-- Formulaire pour supprimer -->
                                <form method="post" action="article.php" style="display:inline;">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" name="delete_article" class="btn btn-danger btn-sm" onclick="return confirm('Voulez-vous vraiment supprimer cet article ?');">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center">
            <a href="sell.php" class="btn btn-success me-2">Ajouter un nouvel article</a>
            <a href="dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
