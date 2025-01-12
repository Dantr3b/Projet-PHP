<?php
require_once("../config.php");
session_start();

// Vérifie si l'utilisateur est connecté et s'il est admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Récupération de l'ID de l'article à modifier
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($article_id <= 0) {
    echo "ID d'article invalide.";
    exit();
}

// Récupération des détails de l'article
$query = "SELECT name, description, price, category, vintage, region, stock,author_id FROM article WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $article_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);

if (!$article) {
    echo "Article introuvable.";
    exit();
}

// Mise à jour de l'article
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $vintage = intval($_POST['vintage']);
    $region = trim($_POST['region']);
    $stock = intval($_POST['stock']);

    // Validation des données
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
        $error = "Veuillez remplir tous les champs correctement.";
    } else {
        // Met à jour l'article dans la base de données
        $update_query = "UPDATE article SET name = ?, description = ?, price = ?, category = ?, vintage = ?, region = ?, stock = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssdssiii", $name, $description, $price, $category, $vintage, $region, $stock, $article_id);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Article mis à jour avec succès.";
        } else {
            $error = "Erreur lors de la mise à jour de l'article.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'Article</title>
</head>
<body>
    <h1>Modifier l'Article</h1>

    <!-- Messages de succès ou d'erreur -->
    <?php if (!empty($message)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Formulaire pour modifier l'article -->
    <form method="post" action="edit_article.php?id=<?php echo $article_id; ?>">
        <label for="name">Nom :</label><br>
        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($article['name']); ?>" required><br><br>

        <label for="description">Description :</label><br>
        <textarea name="description" id="description" rows="5" required><?php echo htmlspecialchars($article['description']); ?></textarea><br><br>

        <label for="price">Prix (€) :</label><br>
        <input type="number" step="0.01" name="price" id="price" value="<?php echo htmlspecialchars($article['price']); ?>" required><br><br>

        <label for="category">Catégorie :</label><br>
        <input type="text" name="category" id="category" value="<?php echo htmlspecialchars($article['category']); ?>" required><br><br>

        <label for="vintage">Année :</label><br>
        <input type="number" name="vintage" id="vintage" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($article['vintage']); ?>" required><br><br>

        <label for="region">Région :</label><br>
        <input type="text" name="region" id="region" value="<?php echo htmlspecialchars($article['region']); ?>" required><br><br>

        <label for="stock">Stock :</label><br>
        <input type="number" name="stock" id="stock" min="0" value="<?php echo htmlspecialchars($article['stock']); ?>" required><br><br>

        <button type="submit">Mettre à jour</button>
    </form>

    <a href="vendor.php?id=<?php echo htmlspecialchars($article['author_id']); ?>">
                        Voir le vendeur

    <p><a href="articles.php">Retour à la gestion des articles</a></p>
</body>
</html>
