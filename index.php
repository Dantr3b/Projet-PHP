<?php
require_once("config.php");
session_start();


if (isset($_SESSION['id'])) {
    // Récupération des informations de l'utilisateur
    $user_id = $_SESSION['id'];
    $query_user = "SELECT username, photo, balance FROM user WHERE id = ?";
    $stmt_user = mysqli_prepare($conn, $query_user);
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $user = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);
}
// Récupération des informations de l'utilisateur
if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $query_user = "SELECT username, photo, balance FROM user WHERE id = ?";
    $stmt_user = mysqli_prepare($conn, $query_user);
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $result_user = mysqli_stmt_get_result($stmt_user);
    $user = mysqli_fetch_assoc($result_user);
    mysqli_stmt_close($stmt_user);
}

// Récupération des produits les plus vendus
$query_top_products = "
    SELECT a.id, a.name, a.image, SUM(o.quantity) AS total_sold
    FROM article a
    JOIN `order` o ON a.id = o.article_id
    GROUP BY a.id
    ORDER BY total_sold DESC
    LIMIT 10";
$result_top_products = mysqli_query($conn, $query_top_products);

// Récupération des trois meilleurs vendeurs
$query_top_sellers = "
    SELECT u.id, u.username, u.photo, u.role, SUM(o.total_price) AS total_sales
    FROM user u
    JOIN `order` o ON u.id = o.seller_id
    WHERE u.role = 'seller'
    GROUP BY u.id
    ORDER BY total_sales DESC
    LIMIT 3";
$result_top_sellers = mysqli_query($conn, $query_top_sellers);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #f8f9fa;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #ddd;
        }
        header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }
        header div {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .search-bar {
            margin: 20px auto;
            text-align: center;
        }
        .carousel, .top-sellers {
            margin: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .carousel img, .top-sellers img {
            max-width: 100px;
            border-radius: 10px;
        }
        .carousel {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            flex-direction: column;
        }
        .carousel-item {
            flex: 0 0 150px; /* Largeur minimale de 150px */
            text-align: center;
            margin: 0 10px; /* Espacement entre les éléments */
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include("navbar.php"); ?>

    <!-- Barre de recherche -->
    <div class="search-bar">
        <form method="get" action="search.php">
            <input type="text" name="query" placeholder="Rechercher un produit ou un vendeur" style="width: 300px; padding: 10px;">
            <button type="submit" style="padding: 10px;">Rechercher</button>
        </form>
    </div>

    <!-- Carrousel des produits les plus vendus -->
    <div class="carousel">
        <h2>Produits les plus vendus</h2>
        <div class="carousel-items">
        <?php if (mysqli_num_rows($result_top_products) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($result_top_products)): ?>
                    <div class="carousel-item">
                        <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <p><?php echo htmlspecialchars($product['name']); ?></p>
                        <p>Vendus : <?php echo htmlspecialchars($product['total_sold']); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Aucun produit vendu.</p>
            <?php endif; ?>

        </div>
    </div>

    <!-- Top vendeurs -->
    <div class="top-sellers">
        <h2>Top Vendeurs</h2>
        <div>
            <?php while ($seller = mysqli_fetch_assoc($result_top_sellers)): ?>
                <div>
                    <img src="uploads/<?php echo htmlspecialchars(!empty($user['photo']) ? $user['photo'] : 'defaultpp.png'); ?>" alt="Photo de <?php echo htmlspecialchars($seller['username']); ?>">
                    <p><?php echo htmlspecialchars($seller['username']); ?></p>
                    <p>Total des ventes : <?php echo number_format($seller['total_sales'], 2); ?> €</p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
