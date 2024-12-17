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
        $categories = array_map(function($value) {
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

    // Gestion de l'image
    if (!empty($_FILES['image']['name'])) {
        $image_name = basename($_FILES['image']['name']);
        $target_path = "../uploads/" . time() . "_" . $image_name;

        $file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
        if (!in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.";
        } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $error = "Erreur lors du téléchargement de l'image.";
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
    <title>Créer un article</title>
</head>
<body>
    <h2>Créer un article</h2>

    <!-- Affichage des messages -->
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <!-- Formulaire de création d'article -->
    <form method="post" action="sell.php" enctype="multipart/form-data">
        <label for="name">Nom de l'article :</label><br>
        <input type="text" name="name" id="name" required><br><br>

        <label for="description">Description :</label><br>
        <textarea name="description" id="description" rows="5" required></textarea><br><br>

        <label for="price">Prix :</label><br>
        <input type="number" name="price" id="price" step="0.01" required><br><br>

        <label for="category">Catégorie :</label><br>
        <select name="category" id="category" required>
            <option value="">-- Choisir une catégorie --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>">
                    <?php echo ucfirst(htmlspecialchars($cat)); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="vintage">Année :</label><br>
        <input type="number" name="vintage" id="vintage" min="1900" max="<?php echo date('Y'); ?>" required><br><br>

        <label for="region">Région :</label><br>
        <input type="text" name="region" id="region" required><br><br>

        <label for="stock">Stock :</label><br>
        <input type="number" name="stock" id="stock" min="0" value="0" required><br><br>

        <label for="image">Image de l'article (JPG, JPEG, PNG, GIF) :</label><br>
        <input type="file" name="image" id="image" accept="image/*" required><br><br>

        <button type="submit">Ajouter l'article</button>
    </form>

    <p><a href="dashboard.php">Retour au tableau de bord</a></p>
</body>
</html>
