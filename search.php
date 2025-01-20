<?php
require_once("config.php");
session_start();

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// Récupération des valeurs des filtres et de la recherche
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$vintage_filter = isset($_GET['vintage']) ? intval($_GET['vintage']) : 0;
$seller_category = isset($_GET['seller_category']) ? trim($_GET['seller_category']) : '';

$products = [];
$sellers = [];

// Récupération des catégories disponibles
$categories = [];
$result_categories = mysqli_query($conn, "SHOW COLUMNS FROM article LIKE 'category'");
if ($row = mysqli_fetch_assoc($result_categories)) {
    preg_match('/^enum\((.*)\)$/', $row['Type'], $matches);
    if (isset($matches[1])) {
        $categories = array_map(function ($value) {
            return trim($value, "'");
        }, explode(',', $matches[1]));
    }
}

// Recherche des produits avec filtres
if (!empty($query) || $category_filter || $min_price > 0 || $max_price > 0 || $vintage_filter > 0) {
    $product_query = "
        SELECT id, name, price, image, category, vintage
        FROM article
        WHERE (name LIKE ? OR description LIKE ?)";
    
    $params = ["ss"];
    $values = ["%$query%", "%$query%"];
    
    if (!empty($category_filter)) {
        $product_query .= " AND category = ?";
        $params[0] .= "s";
        $values[] = $category_filter;
    }
    if ($min_price > 0) {
        $product_query .= " AND price >= ?";
        $params[0] .= "d";
        $values[] = $min_price;
    }
    if ($max_price > 0) {
        $product_query .= " AND price <= ?";
        $params[0] .= "d";
        $values[] = $max_price;
    }
    if ($vintage_filter > 0) {
        $product_query .= " AND vintage = ?";
        $params[0] .= "i";
        $values[] = $vintage_filter;
    }
    
    $stmt = mysqli_prepare($conn, $product_query);
    mysqli_stmt_bind_param($stmt, $params[0], ...$values);
    mysqli_stmt_execute($stmt);
    $result_products = mysqli_stmt_get_result($stmt);
    $products = mysqli_fetch_all($result_products, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// Recherche des vendeurs avec filtres
if (!empty($query) || $seller_category) {
    $seller_query = "
        SELECT u.id, u.username, u.photo, COUNT(o.id) AS total_orders
        FROM user u
        LEFT JOIN `order` o ON u.id = o.seller_id
        WHERE u.username LIKE ?";
    
    $params = ["s"];
    $values = ["%$query%"];
    
    if (!empty($seller_category)) {
        if ($seller_category === 'underground') {
            $seller_query .= " GROUP BY u.id HAVING total_orders < 5";
        } elseif ($seller_category === 'petit_commerce') {
            $seller_query .= " GROUP BY u.id HAVING total_orders BETWEEN 5 AND 50";
        } elseif ($seller_category === 'pro') {
            $seller_query .= " GROUP BY u.id HAVING total_orders > 50";
        } else {
            $seller_query .= " GROUP BY u.id";
        }
    } else {
        $seller_query .= " GROUP BY u.id";
    }
    
    $stmt = mysqli_prepare($conn, $seller_query);
    mysqli_stmt_bind_param($stmt, $params[0], ...$values);
    mysqli_stmt_execute($stmt);
    $result_sellers = mysqli_stmt_get_result($stmt);
    $sellers = mysqli_fetch_all($result_sellers, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche avec Filtres</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .result-item img {
            max-width: 100px;
            border-radius: 10px;
        }
        .card-seller img {
            width: 100px; 
            height: 100px; 
            object-fit: cover; 
            border-radius: 50%; 
            margin-bottom: 10px;
        }
        .card-product img {
            max-height: 150px;
            object-fit: contain;
        }
        .card-product, .card-seller {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .filters {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .filters select, .filters input {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    
<?php include("navbar.php"); ?>

    <div class="container mt-5">
        <h1 class="text-center mb-4">Recherche et Filtres</h1>

        <!-- Barre de recherche -->
        <form method="get" action="search.php" class="input-group mb-4">
            <input type="text" name="query" class="form-control" placeholder="Rechercher un produit ou un vendeur" value="<?php echo htmlspecialchars($query); ?>">
            <button type="submit" class="btn btn-dark">Rechercher</button>
        </form>

        <!-- Filtres -->
        <div class="filters p-4 mb-4">
            <form method="get" action="search.php">
                <h3>Filtres pour les Produits</h3>
                <div class="row">
                    <div class="col-md-3">
                        <label for="category">Catégorie :</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">-- Toutes les catégories --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="min_price">Prix minimum (€) :</label>
                        <input type="number" name="min_price" id="min_price" step="0.01" class="form-control" value="<?php echo htmlspecialchars($min_price); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="max_price">Prix maximum (€) :</label>
                        <input type="number" name="max_price" id="max_price" step="0.01" class="form-control" value="<?php echo htmlspecialchars($max_price); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="vintage">Année :</label>
                        <input type="number" name="vintage" id="vintage" min="1900" max="<?php echo date('Y'); ?>" class="form-control" value="<?php echo htmlspecialchars($vintage_filter); ?>">
                    </div>
                </div>

                <h3 class="mt-4">Filtres pour les Vendeurs</h3>
                <div class="row">
                    <div class="col-md-4">
                        <label for="seller_category">Catégorie de vendeur :</label>
                        <select name="seller_category" id="seller_category" class="form-select">
                            <option value="">-- Toutes les catégories --</option>
                            <option value="underground" <?php echo $seller_category === 'underground' ? 'selected' : ''; ?>>Underground (moins de 5 commandes)</option>
                            <option value="petit_commerce" <?php echo $seller_category === 'petit_commerce' ? 'selected' : ''; ?>>Petit commerce (5-50 commandes)</option>
                            <option value="pro" <?php echo $seller_category === 'pro' ? 'selected' : ''; ?>>Pro (plus de 50 commandes)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Appliquer les Filtres</button>
            </form>
        </div>

        <!-- Résultats des produits -->
        <div class="results mb-5">
            <h2 class="mb-4">Produits trouvés</h2>
            <div class="row">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card card-product p-3">
                                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="card-img-top">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text">Prix : <?php echo number_format($product['price'], 2); ?> €</p>
                                    <p class="card-text">Catégorie : <?php echo htmlspecialchars($product['category']); ?></p>
                                    <p class="card-text">Année : <?php echo htmlspecialchars($product['vintage']); ?></p>
                                    <a href="detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary w-100">Voir les détails</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">Aucun produit trouvé pour la recherche "<?php echo htmlspecialchars($query); ?>".</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Résultats des vendeurs -->
        <div class="results mb-5">
    <h2 class="mb-4">Vendeurs trouvés</h2>
    <div class="row">
        <?php if (!empty($sellers)): ?>
            <?php foreach ($sellers as $seller): ?>
                <div class="col-md-4 mb-4">
                    <div class="card card-seller p-3">
                        <a href="profile.php?id=<?php echo $seller['id']; ?>" class="text-decoration-none text-dark">
                            <img src="uploads/<?php echo htmlspecialchars(!empty($seller['photo']) ? $seller['photo'] : 'defaultpp.jpg'); ?>" 
                                alt="<?php echo htmlspecialchars($seller['username']); ?>" 
                                class="rounded-circle mx-auto d-block mb-3" 
                                style="width: 100px; height: 100px;">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo htmlspecialchars($seller['username']); ?></h5>
                                <p class="card-text">Total des commandes : <?php echo htmlspecialchars($seller['total_orders']); ?></p>
                                <a href="profile.php?id=<?php echo $seller['id']; ?>" class="btn btn-outline-primary w-100">Voir le profil</a>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center">Aucun vendeur trouvé pour la recherche "<?php echo htmlspecialchars($query); ?>".</p>
        <?php endif; ?>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
