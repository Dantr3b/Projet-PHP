<?php
require_once("../config.php");
session_start();

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

$article_id = intval($_GET['id']);
$seller_id = $_SESSION['id'];

// Récupération des données de l'article
$stmt = mysqli_prepare($conn, "SELECT name, description, price, stock FROM article WHERE id = ? AND author_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $article_id, $seller_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$article = mysqli_fetch_assoc($result)) {
    echo "Article introuvable.";
    exit();
}

$error = "";
$success = "";

// Mise à jour des données
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    if (empty($name) || empty($description) || $price <= 0 || $stock < 0) {
        $error = "Veuillez remplir tous les champs correctement.";
    } else {
        $update_stmt = mysqli_prepare($conn, "UPDATE article SET name = ?, description = ?, price = ?, stock = ? WHERE id = ? AND author_id = ?");
        mysqli_stmt_bind_param($update_stmt, "ssdiii", $name, $description, $price, $stock, $article_id, $seller_id);

        if (mysqli_stmt_execute($update_stmt)) {
            $success = "Article mis à jour avec succès.";
        } else {
            $error = "Erreur lors de la mise à jour de l'article : " . mysqli_error($conn);
        }

        mysqli_stmt_close($update_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un article</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("../navbar.php"); ?>

    <div class="container my-5">
        <h2 class="text-center">Modifier un article</h2>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Formulaire de modification -->
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nom</label>
                <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($article['name']); ?>" required>
            </div>

            <div class="col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="5" required><?php echo htmlspecialchars($article['description']); ?></textarea>
            </div>

            <div class="col-md-6">
                <label for="price" class="form-label">Prix (€)</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" value="<?php echo htmlspecialchars($article['price']); ?>" required>
            </div>

            <div class="col-md-6">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" value="<?php echo htmlspecialchars($article['stock']); ?>" required>
            </div>

            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary w-100">Modifier</button>
                <a href="article.php" class="btn btn-secondary mt-3">Retour</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
