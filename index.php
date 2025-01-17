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
        .carousel-item {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .product-card {
        text-align: center;
        margin: 0 10px;
        width: 200px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .product-card img {
        margin-top: 20px;
        max-height: 150px;
        object-fit: contain;
        margin-bottom: 30px;
        border-radius: 10px;
        background-color: white;
        box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .product-card:hover {
        transform: translateY(-10px); /* Déplace légèrement la carte vers le haut */
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3); /* Accentue l'ombre */
    }
    .product-card:hover img {
        transform: scale(1.1); /* Agrandit légèrement l'image */
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3); /* Ajoute une ombre à l'image */
    }
        .banner {
            margin-top: -15px;
            background-image: url('uploads/Capture\ d’écran\ 2025-01-14\ à\ 12.31.19.png');
            background-size: cover;
            background-position: center;
            height: 300px;
        }
        .banner h1 {
            color: transparent;
            font-size: 36px;
        }
        .card-review {
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 10px;
            border-radius: 10px;
        }

        .top-sellers img {
            height: 100px;
            width: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .top-seller-card {
            text-align: center;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin: 10px;
        }

        .top-seller-card img{
            height: 50px;
            width: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include("navbar.php"); ?>

    <div class="container-fluid p-0">
        <div class="banner d-flex align-items-center justify-content-center">
            <h1>Bienvenue sur Notre Plateforme</h1>
        </div>
    </div>
    <br>

    <!-- Barre de recherche -->
    <div class="container search-bar mt-4">
        <form method="get" action="search.php" class="input-group">
            <input type="text" name="query" class="form-control" placeholder="Rechercher un produit ou un vendeur">
            <button type="submit" class="btn btn-dark">Rechercher</button>
        </form>
    </div>
        <br>
    <!-- Produits les plus vendus -->
<div class="container top-section">
    <h2 class="text-center my-4">Produits les plus vendus</h2>
    <?php if (mysqli_num_rows($result_top_products) > 0): ?>
        <?php 
        $products = mysqli_fetch_all($result_top_products, MYSQLI_ASSOC);
        $product_count = count($products);
        ?>

        <?php if ($product_count > 3): ?>
            <!-- Carrousel si plus de 3 produits -->
            <div id="topProductsCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php 
                    $product_chunks = array_chunk($products, 3); // Diviser les produits en groupes de 3
                    foreach ($product_chunks as $index => $chunk): 
                    ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="d-flex justify-content-center">
                                <?php foreach ($chunk as $product): ?>
                                    <a href="detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                        <div class="product-card">
                                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <p>Vendus : <?php echo htmlspecialchars($product['total_sold']); ?></p>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Contrôles du carrousel -->
                <button class="carousel-control-prev" type="button" data-bs-target="#topProductsCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Précédent</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#topProductsCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Suivant</span>
                </button>
            </div>
        <?php else: ?>
            <!-- Affichage normal si 3 produits ou moins -->
            <div class="d-flex justify-content-center">
                <?php foreach ($products as $product): ?>
                    <a href="detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                        <div class="product-card">
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                            <p>Vendus : <?php echo htmlspecialchars($product['total_sold']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-center">Aucun produit vendu pour le moment.</p>
    <?php endif; ?>
</div>


    <!-- Top vendeurs -->
<div class="container top-section text-center">
    <h2 class="text-center my-4">Top Vendeurs</h2>
    <div class="d-flex justify-content-center flex-wrap">
        <?php if (mysqli_num_rows($result_top_sellers) > 0): ?>
            <?php while ($seller = mysqli_fetch_assoc($result_top_sellers)): ?>
                <div class="top-seller-card m-3">
                    <img src="uploads/<?php echo htmlspecialchars(!empty($seller['photo']) ? $seller['photo'] : 'defaultpp.png'); ?>" 
                         alt="Photo de <?php echo htmlspecialchars($seller['username']); ?>">
                    <h5><?php echo htmlspecialchars($seller['username']); ?></h5>
                    <p>Total des ventes : <?php echo number_format($seller['total_sales'], 2); ?> €</p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-center">Aucun vendeur pour le moment.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Témoignages -->
<div class="container my-5 text-center">
    <h2 class="text-center">Ce que disent nos clients</h2>
    <br>
    <div class="d-flex justify-content-center flex-wrap">
        <?php
        $query_reviews = "
            SELECT r.comment, r.rating, u.username 
            FROM review r 
            JOIN user u ON r.user_id = u.id 
            ORDER BY RAND() 
            LIMIT 3";
        $result_reviews = mysqli_query($conn, $query_reviews);

        if (mysqli_num_rows($result_reviews) > 0):
            while ($review = mysqli_fetch_assoc($result_reviews)):
        ?>
            <div class="card-review m-3 text-center">
                <h5><?php echo htmlspecialchars($review['username']); ?></h5>
                <p>"<?php echo htmlspecialchars($review['comment']); ?>"</p>
                <p class="text-warning">Note : <?php echo $review['rating']; ?>/5</p>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <p class="text-center">Aucun témoignage pour le moment.</p>
        <?php endif; ?>
    </div>
</div>

    <!-- Call-to-Action -->
    <div class="container my-5 text-center">
        <h2>Rejoignez notre communauté</h2><br>
        <p>Découvrez les meilleurs produits ou devenez un vendeur sur notre plateforme.</p>
        <a href="register.php" class="btn btn-success me-2">S'inscrire</a>
        <a href="collection.php" class="btn btn-primary">Voir le Catalogue</a>
    </div>
                <br><br><br>
    <!-- Statistiques -->
    <div class="container my-5">
        <h2 class="text-center">Nos Statistiques</h2>
        <div class="row text-center">
            <div class="col-md-4">
                <h3 class="text-success">
                    <?php
                    $query_total_products = "SELECT COUNT(*) AS total_products FROM article";
                    $result_total_products = mysqli_query($conn, $query_total_products);
                    $total_products = mysqli_fetch_assoc($result_total_products)['total_products'];
                    echo $total_products;
                    ?>
                </h3>
                <p>Produits disponibles</p>
            </div>
            <div class="col-md-4">
                <h3 class="text-primary">
                    <?php
                    $query_total_users = "SELECT COUNT(*) AS total_users FROM user";
                    $result_total_users = mysqli_query($conn, $query_total_users);
                    $total_users = mysqli_fetch_assoc($result_total_users)['total_users'];
                    echo $total_users;
                    ?>
                </h3>
                <p>Utilisateurs inscrits</p>
            </div>
            <div class="col-md-4">
                <h3 class="text-warning">
                    <?php
                    $query_total_sales = "SELECT SUM(total_price) AS total_sales FROM `order`";
                    $result_total_sales = mysqli_query($conn, $query_total_sales);
                    $total_sales = mysqli_fetch_assoc($result_total_sales)['total_sales'];
                    echo number_format($total_sales, 2) . ' €';
                    ?>
                </h3>
                <p>Ventes réalisées</p>
            </div>
        </div>
    </div>
                <br>
                <br>
    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3">
        <p>&copy; 2025 Votre Site. Tous droits réservés.</p>
        <a href="contact.php" class="text-white">Contact</a> | 
        <a href="terms.php" class="text-white">Conditions d'utilisation</a> | 
        <a href="privacy.php" class="text-white">Politique de confidentialité</a>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
