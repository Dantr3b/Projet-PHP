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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection d'articles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 70px; /* Ajustez selon la hauteur de la navbar */
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">Cave d'Exception</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Basculer la navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="collection.php">Catalogue</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">Panier</a></li>
                    <li class="nav-item"><a class="nav-link" href="account.php">Mon Profil</a></li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'seller'): ?>
                        <li class="nav-item"><a class="nav-link" href="sellers/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h1 class="text-center">Catalogue des Vins</h1>
        <p class="text-center text-muted">Explorez notre collection des meilleurs vins et spiritueux.</p>

        <!-- Formulaire de recherche -->
        <form class="row g-3 my-4" method="get" action="collection.php">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Recherche par nom" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="category">
                    <option value="">-- Toutes les catégories --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter == $category ? 'selected' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($category)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="region" placeholder="Région (ex: Bordeaux)" value="<?php echo htmlspecialchars($region_filter); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark w-100">Rechercher</button>
            </div>
        </form>

        <!-- Affichage des articles -->
        <div class="row">
            <?php if (!empty($articles)): ?>
                <?php foreach ($articles as $article): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php 
                            $imagePath = !empty($article['image']) ? htmlspecialchars(str_replace('../', '', $article['image'])) : 'path/to/default-image.jpg';
                            ?>
                            <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($article['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($article['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($article['description'], 0, 50)) . '...'; ?></p>
                                <p class="card-text"><strong><?php echo number_format($article['price'], 2); ?> €</strong></p>
                                <a href="detail.php?id=<?php echo $article['id']; ?>" class="btn btn-dark">Voir le détail</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center">Aucun article trouvé.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>&copy; 2025 Cave d'Exception. Tous droits réservés.</p>
    </footer>

    <!-- JavaScript de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
