<?php
require_once("config.php");
session_start();

// Récupération des informations de l'utilisateur connecté
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
        /* Styles pour la bannière */
        .banner {
            position: relative;
            background-image: url('uploads/a-black-and-white-banner-for-an-e-commer_M1Yy9Oi8Td6vzRKjaZiVwA_YfoPLKc7REWFk6cjZ1GK7Q.jpeg');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-top: -20px;
        }

        .banner h1 {
            z-index: 1;
            font-size: 3rem;
        }

        /* Produits et cartes générales */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 5px;
            width: 85%;
            margin: 0 auto;
            
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.3);
        }

        .card img {
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            object-fit: cover;
            height: 300px;

        }

        .product-card img {
            height: 300px; /* Augmenter la hauteur */
            object-fit: contain;
        }

        .card-body {
            text-align: center;
        }

        /* Témoignages */
        .card-review {
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 10px auto; /* Centrer les cartes */
            border-radius: 10px;
        }

        /* Centrage des sections */
        .centered-section {
            text-align: center;
        }
        
        .centered-section .row {
            justify-content: center;
        }

        /* Boutons Call-to-Action */
        .btn-custom {
            transition: background-color 0.3s, transform 0.3s;
        }

        .btn-custom:hover {
            background-color: #343a40;
            color: #fff;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include("navbar.php"); ?>

    <!-- Bannière -->
    <div class="banner">
    </div>

    <!-- Barre de recherche -->
    <div class="container my-5">
        <form method="get" action="search.php" class="input-group">
            <input type="text" name="query" class="form-control" placeholder="Rechercher un produit ou un vendeur">
            <button type="submit" class="btn btn-dark">Rechercher</button>
        </form>
    </div>
        <!-- Bouton Voir la Collection -->
        <div class="container my-5 text-center">
            <a href="collection.php" class="btn btn-lg btn-dark btn-custom">Voir le Catalogue</a>
        </div>
        <hr>
    <!-- Produits les plus vendus -->
    <div class="container my-5">
        <h2 class="text-center my-4">Nos Best Sellers</h2><br>
        <div class="row">
            <?php while ($product = mysqli_fetch_assoc($result_top_products)): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <a href="detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="card-img-top product-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text">Vendus : <?php echo htmlspecialchars($product['total_sold']); ?></p>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Top vendeurs -->
    <div class="container my-5 centered-section">
        <h2>Top Vendeurs</h2><br><br>
        <div class="row">
            <?php while ($seller = mysqli_fetch_assoc($result_top_sellers)): ?>
                <div class="col-md-4">
                    <div class="card">
                        <img src="uploads/<?php echo htmlspecialchars(!empty($seller['photo']) ? $seller['photo'] : 'defaultpp.png'); ?>" 
                             alt="Photo de <?php echo htmlspecialchars($seller['username']); ?>" 
                             class="card-img-top rounded-circle mx-auto mt-3" style="width: 100px; height: 100px;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($seller['username']); ?></h5>
                            <p class="card-text">Total des ventes : <?php echo number_format($seller['total_sales'], 2); ?> €</p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Témoignages -->
    <div class="container my-5 centered-section">
        <br><br><h2>Ce que disent nos clients</h2><br><br><br>
        <div class="row">
            <?php
            $query_reviews = "
                SELECT r.comment, r.rating, u.username 
                FROM review r 
                JOIN user u ON r.user_id = u.id 
                ORDER BY RAND() 
                LIMIT 3";
            $result_reviews = mysqli_query($conn, $query_reviews);
            while ($review = mysqli_fetch_assoc($result_reviews)): ?>
                <div class="col-md-4">
                    <div class="card-review">
                        <h5><?php echo htmlspecialchars($review['username']); ?></h5>
                        <p>"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                        <p class="text-warning">Note : <?php echo $review['rating']; ?>/5</p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Call-to-Action -->
    <div class="container my-5 text-center">
        <h2>Rejoignez notre communauté</h2><br>
        <p>Découvrez les meilleurs produits ou devenez un vendeur sur notre plateforme.</p>
        <a href="register.php" class="btn btn-success btn-custom">S'inscrire</a>
        <a href="collection.php" class="btn btn-primary btn-custom">Voir le Catalogue</a>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        <p>&copy; 2025 Cave d'Exception. Tous droits réservés.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
