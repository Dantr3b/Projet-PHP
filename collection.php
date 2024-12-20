<?php
require_once("config.php"); // Connexion à la base de données
session_start();

$error = "";
$articles = [];

// Récupération des catégories pour le filtre
$categories = [];
$result = mysqli_query($conn, "SHOW COLUMNS FROM article LIKE 'category'");
if ($row = mysqli_fetch_assoc($result)) {
    preg_match('/^enum\((.*)\)$/', $row['Type'], $matches);
    if (isset($matches[1])) {
        $categories = array_map(function ($value) {
            return trim($value, "'");
        }, explode(',', $matches[1]));
    }
}

// Récupération des filtres
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : "";
$region_filter = isset($_GET['region']) ? trim($_GET['region']) : "";
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$vintage_filter = isset($_GET['vintage']) && !empty($_GET['vintage']) ? intval($_GET['vintage']) : null;

// Construction de la requête SQL
$query = "SELECT id, name, description, price, category, vintage, region, image FROM article WHERE 1=1";
$params = [];
$types = "";

// Recherche par nom
if (!empty($search)) {
    $query .= " AND name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Filtre par catégorie
if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

// Filtre par région
if (!empty($region_filter)) {
    $query .= " AND region LIKE ?";
    $params[] = "%" . $region_filter . "%";
    $types .= "s";
}

// Filtre par prix
if ($min_price > 0) {
    $query .= " AND price >= ?";
    $params[] = $min_price;
    $types .= "d";
}
if ($max_price > 0) {
    $query .= " AND price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

// Filtre par année (vintage) uniquement si une année est saisie
if (!is_null($vintage_filter)) {
    $query .= " AND vintage = ?";
    $params[] = $vintage_filter;
    $types .= "i";
}

// Exécution de la requête préparée
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$articles = mysqli_fetch_all($result, MYSQLI_ASSOC);

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Collection d'articles</title>
</head>
<body>
    <h2>Collection d'articles</h2>

    <!-- Formulaire de recherche et de filtres -->
    <form method="get" action="collection.php">
        <label for="search">Rechercher par nom :</label>
        <input type="text" name="search" id="search" placeholder="Nom de l'article" value="<?php echo htmlspecialchars($search); ?>"><br><br>

        <label for="category">Catégorie :</label>
        <select name="category" id="category">
            <option value="">-- Toutes les catégories --</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                    <?php echo ucfirst(htmlspecialchars($category)); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="region">Région :</label>
        <input type="text" name="region" id="region" placeholder="Exemple : Bordeaux" value="<?php echo htmlspecialchars($region_filter); ?>"><br><br>

        <label for="min_price">Prix minimum :</label>
        <input type="number" name="min_price" id="min_price" step="0.01" value="<?php echo htmlspecialchars($min_price); ?>"><br><br>

        <label for="max_price">Prix maximum :</label>
        <input type="number" name="max_price" id="max_price" step="0.01" value="<?php echo htmlspecialchars($max_price); ?>"><br><br>

        <label for="vintage">Année :</label>
        <input type="number" name="vintage" id="vintage" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($vintage_filter ?? ""); ?>"><br><br>

        <button type="submit">Rechercher</button>
        <a href="collection.php">Réinitialiser</a>
    </form>

    <hr>

    <!-- Affichage des articles -->
    <?php if (!empty($articles)): ?>
        <table border="1" cellpadding="10">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Prix</th>
                    <th>Catégorie</th>
                    <th>Année</th>
                    <th>Région</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td>
                            <a href="detail.php?id=<?php echo $article['id']; ?>">
                                <?php echo htmlspecialchars($article['name'] ?? ""); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars(substr($article['description'] ?? "", 0, 50)) . '...'; ?></td>
                        <td><?php echo number_format($article['price'], 2); ?> €</td>
                        <td><?php echo htmlspecialchars($article['category'] ?? ""); ?></td>
                        <td><?php echo htmlspecialchars($article['vintage'] ?? ""); ?></td>
                        <td><?php echo htmlspecialchars($article['region'] ?? ""); ?></td>
                        <td>
                            <a href="detail.php?id=<?php echo $article['id']; ?>">
                                <img src="<?php echo htmlspecialchars(str_replace('../', '', $article['image'] ?? "")); ?>" alt="<?php echo htmlspecialchars($article['name'] ?? ""); ?>" width="100">
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    <?php else: ?>
        <p>Aucun article trouvé.</p>
    <?php endif; ?>
</body>
</html>
