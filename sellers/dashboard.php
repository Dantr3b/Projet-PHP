<?php
session_start();
require_once("../config.php");

// Vérification si l'utilisateur est connecté et est un "seller"
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

$seller_id = $_SESSION['id']; // ID du vendeur connecté

// Initialisation des variables pour le tableau de bord
$total_products_sold = 0;
$most_ordered_article_name = "Aucun produit";
$most_ordered_quantity = 0;
$highest_revenue_article_name = "Aucun produit";
$highest_revenue_total = 0;
$total_revenue = 0;

// Calcul du total des produits vendus et du revenu total
$query = "
    SELECT 
        SUM(o.quantity) AS total_quantity,
        SUM(o.total_price) AS total_revenue
    FROM `order` o
    JOIN article a ON o.article_id = a.id
    WHERE a.author_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total_products_sold, $total_revenue);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Récupération du produit le plus commandé
$query = "
    SELECT 
        a.name, 
        SUM(o.quantity) AS total_quantity
    FROM `order` o
    JOIN article a ON o.article_id = a.id
    WHERE a.author_id = ?
    GROUP BY a.id
    ORDER BY total_quantity DESC
    LIMIT 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $most_ordered_article_name, $most_ordered_quantity);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Récupération du produit le plus rentable
$query = "
    SELECT 
        a.name, 
        SUM(o.total_price) AS total_revenue
    FROM `order` o
    JOIN article a ON o.article_id = a.id
    WHERE a.author_id = ?
    GROUP BY a.id
    ORDER BY total_revenue DESC
    LIMIT 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $highest_revenue_article_name, $highest_revenue_total);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../collection.php">Cave d'Exception</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../collection.php">Collection</a></li>
                    <?php if (isset($_SESSION['username'])): ?>
                        <?php if ($_SESSION['role'] === 'seller'): ?>
                            <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        
                        <li class="nav-item"><a class="nav-link" href="../cart.php">Panier</a></li>
                        <li class="nav-item"><a class="nav-link" href="../account.php">Mon Profil</a></li>
                        <li class="nav-item"><a class="nav-link" href="../logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../login.php">Connexion</a></li>
                        <li class="nav-item"><a class="nav-link" href="../register.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu du tableau de bord -->
    <div class="container my-5">
        <h1 class="text-center mb-4">Tableau de bord du vendeur</h1>

        <?php if ($total_products_sold > 0): ?>
            <div class="row">
                <!-- Total des produits vendus -->
                <div class="col-md-6">
                    <div class="card border-dark mb-3">
                        <div class="card-header bg-dark text-white">Produits vendus</div>
                        <div class="card-body">
                            <h5 class="card-title">Total des produits vendus</h5>
                            <p class="card-text"><?php echo $total_products_sold; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Produit le plus commandé -->
                <div class="col-md-6">
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">Produit le plus commandé</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $most_ordered_article_name; ?></h5>
                            <p class="card-text"><?php echo $most_ordered_quantity; ?> commandes</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Produit qui rapporte le plus -->
                <div class="col-md-6">
                    <div class="card border-success mb-3">
                        <div class="card-header bg-success text-white">Produit le plus rentable</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $highest_revenue_article_name; ?></h5>
                            <p class="card-text">Revenu total : <?php echo number_format($highest_revenue_total, 2); ?> €</p>
                        </div>
                    </div>
                </div>

                <!-- Revenu total -->
                <div class="col-md-6">
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning text-white">Revenu total</div>
                        <div class="card-body">
                            <h5 class="card-title">Revenu total généré</h5>
                            <p class="card-text"><?php echo number_format($total_revenue, 2); ?> €</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <p>Aucun produit vendu pour le moment.</p>
            </div>
        <?php endif; ?>

        <!-- Liens de navigation -->
        <div class="text-center mt-4">
            <a href="orders.php" class="btn btn-primary me-2">Gérer les commandes</a>
            <a href="article.php" class="btn btn-secondary me-2">Gérer les articles</a>
            <a href="../account.php" class="btn btn-dark">Mon compte</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
