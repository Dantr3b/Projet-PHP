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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            padding: 20px;
        }
        .search-bar {
            margin: 20px auto;
            text-align: center;
        }
        .filters {
            margin: 20px auto;
            text-align: center;
        }
        .results {
            margin-top: 20px;
        }
        .result-item {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .result-item img {
            max-width: 50px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Recherche et Filtres</h1>

        <!-- Barre de recherche -->
        <div class="search-bar">
            <form method="get" action="search.php">
                <input type="text" name="query" placeholder="Rechercher un produit ou un vendeur" value="<?php echo htmlspecialchars($query); ?>" style="width: 300px; padding: 10px;">
                <button type="submit" style="padding: 10px;">Rechercher</button>
        </div>

        <!-- Filtres -->
        <div class="filters">
            <h3>Filtres pour les Produits</h3>
            <label for="category">Catégorie :</label>
            <select name="category" id="category">
                <option value="">-- Toutes les catégories --</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $category_filter === $category ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="min_price">Prix minimum (€) :</label>
            <input type="number" name="min_price" id="min_price" step="0.01" value="<?php echo htmlspecialchars($min_price); ?>">

            <label for="max_price">Prix maximum (€) :</label>
            <input type="number" name="max_price" id="max_price" step="0.01" value="<?php echo htmlspecialchars($max_price); ?>">

            <label for="vintage">Année :</label>
            <input type="number" name="vintage" id="vintage" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($vintage_filter); ?>">

            <h3>Filtres pour les Vendeurs</h3>
            <label for="seller_category">Catégorie de vendeur :</label>
            <select name="seller_category" id="seller_category">
                <option value="">-- Toutes les catégories --</option>
                <option value="underground" <?php echo $seller_category === 'underground' ? 'selected' : ''; ?>>Underground (moins de 5 commandes)</option>
                <option value="petit_commerce" <?php echo $seller_category === 'petit_commerce' ? 'selected' : ''; ?>>Petit commerce (5-50 commandes)</option>
                <option value="pro" <?php echo $seller_category === 'pro' ? 'selected' : ''; ?>>Pro (plus de 50 commandes)</option>
            </select>

            <button type="submit" style="padding: 10px;">Appliquer les Filtres</button>
        </form>
        </div>

        <!-- Résultats des produits -->
        <div class="results">
            <h2>Produits trouvés</h2>
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="result-item">
                        <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div>
                            <p><strong><?php echo htmlspecialchars($product['name']); ?></strong></p>
                            <p>Prix : <?php echo number_format($product['price'], 2); ?> €</p>
                            <p>Catégorie : <?php echo htmlspecialchars($product['category']); ?></p>
                            <p>Année : <?php echo htmlspecialchars($product['vintage']); ?></p>
                            <a href="detail.php?id=<?php echo $product['id']; ?>">Voir les détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun produit trouvé pour la recherche "<?php echo htmlspecialchars($query); ?>".</p>
            <?php endif; ?>
        </div>

        <!-- Résultats des vendeurs -->
        <div class="results">
            <h2>Vendeurs trouvés</h2>
            <?php if (!empty($sellers)): ?>
                <?php foreach ($sellers as $seller): ?>
                    <div class="result-item">
                        <img src="uploads/<?php echo htmlspecialchars(!empty($seller['photo']) ? $seller['photo'] : 'defaultpp.jpg'); ?>" alt="<?php echo htmlspecialchars($seller['username']); ?>">
                        <div>
                            <p><strong><?php echo htmlspecialchars($seller['username']); ?></strong></p>
                            <p>Total des commandes : <?php echo htmlspecialchars($seller['total_orders']); ?></p>
                            <a href="account.php?id=<?php echo $seller['id']; ?>">Voir le profil</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun vendeur trouvé pour la recherche "<?php echo htmlspecialchars($query); ?>".</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
