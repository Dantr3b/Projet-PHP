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
$categories = [];

// 1. Récupération des catégories depuis la base de données
$result = mysqli_query($conn, "SHOW COLUMNS FROM article LIKE 'category'");
if ($row = mysqli_fetch_assoc($result)) {
    preg_match('/^enum\((.*)\)$/', $row['Type'], $matches);
    if (isset($matches[1])) {
        $categories = array_map(function ($value) {
            return trim($value, "'");
        }, explode(',', $matches[1]));
    }
}

// 2. Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération et nettoyage des données
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $vintage = intval($_POST['vintage']);
    $region = trim($_POST['region']);
    $stock = intval($_POST['stock']);
    $author_id = $_SESSION['id']; // ID de l'auteur (vendeur connecté)

    // Vérifiez que le dossier "uploads" existe et est accessible
    $upload_dir = "../uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Crée le dossier s'il n'existe pas
    }

    // Gestion de l'image
    if (!empty($_FILES['image']['name'])) {
        $image_name = basename($_FILES['image']['name']);
        $target_path = $upload_dir . time() . "_" . $image_name;

        $file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
        if (!in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.";
        } elseif ($_FILES['image']['error'] !== 0) {
            $error = "Erreur lors du téléchargement de l'image : Code " . $_FILES['image']['error'];
        } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $error = "Erreur lors du téléchargement de l'image. Vérifiez les permissions du dossier.";
        }
    } else {
        $error = "Veuillez sélectionner une image pour l'article.";
    }

    // Validation des autres champs
    if (empty($name) || empty($description) || empty($price) || empty($category) || empty($vintage) || empty($region)) {
        $error = "Tous les champs requis doivent être remplis.";
    } elseif (!in_array($category, $categories)) {
        $error = "La catégorie sélectionnée est invalide.";
    } elseif ($price <= 0 || $stock < 0) {
        $error = "Le prix doit être supérieur à 0 et le stock ne peut pas être négatif.";
    }

    // Insertion dans la base de données si aucune erreur
    if (empty($error)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO article (name, description, price, category, vintage, region, image, stock, author_id) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssdssssis", $name, $description, $price, $category, $vintage, $region, $target_path, $stock, $author_id);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Article ajouté avec succès.";
        } else {
            $error = "Erreur lors de l'ajout de l'article : " . mysqli_error($conn);
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
    <title>Créer un article</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("../navbar.php"); ?>

    <div class="container my-5">
        <h2 class="text-center mb-4">Créer un nouvel article</h2>

        <!-- Affichage des messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Formulaire de création d'article -->
        <form method="post" action="sell.php" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nom de la bouteille</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label for="price" class="form-label">Prix (€)</label>
                <input type="number" name="price" id="price" step="0.01" class="form-control" required>
            </div>

            <div class="col-md-12">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" rows="5" class="form-control" required></textarea>
            </div>

            <div class="col-md-6">
                <label for="category" class="form-label">Catégorie</label>
                <select name="category" id="category" class="form-select" required>
                    <option value="">-- Choisir une catégorie --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php echo ucfirst(htmlspecialchars($cat)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="vintage" class="form-label">Année</label>
                <input type="number" name="vintage" id="vintage" class="form-control" min="1900" max="<?php echo date('Y'); ?>" required>
            </div>

            <div class="col-md-6">
                <label for="region" class="form-label">Région</label>
                <input type="text" name="region" id="region" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" min="0" value="0" required>
            </div>

            <div class="col-md-12">
                <label for="image" class="form-label">Image de l'article (JPG, JPEG, PNG, GIF)</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*" required>
            </div>

            <div class="col-12 text-center">
                <button type="submit" class="btn btn-dark w-100">Ajouter l'article</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
