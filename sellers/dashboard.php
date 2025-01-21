<?php
session_start();
require_once("../config.php");

// Vérification si l'utilisateur est connecté et est un "seller"
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

$seller_id = $_SESSION['id']; // ID du vendeur connecté

// Initialisation des variables pour éviter les erreurs
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

// Vérification si des données sont retournées
if (!$total_products_sold) $total_products_sold = 0;
if (!$total_revenue) $total_revenue = 0;

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

if (!$most_ordered_article_name) $most_ordered_article_name = "Aucun produit";
if (!$most_ordered_quantity) $most_ordered_quantity = 0;

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

if (!$highest_revenue_article_name) $highest_revenue_article_name = "Aucun produit";
if (!$highest_revenue_total) $highest_revenue_total = 0;

// Récupération des ventes des 6 derniers mois
$query = "
    SELECT DATE_FORMAT(o.created_at, '%Y-%m') AS month, SUM(o.total_price) AS monthly_sales
    FROM `order` o
    JOIN article a ON o.article_id = a.id
    WHERE a.author_id = ?
    GROUP BY month
    ORDER BY month ASC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $seller_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$months = [];
$sales = [];

while ($row = mysqli_fetch_assoc($result)) {
    $months[] = $row['month'];
    $sales[] = $row['monthly_sales'];
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include("../navbar.php"); ?>


<div class="container my-5 ">
    <h1 class="text-center mb-4">Tableau de bord du vendeur</h1>

    <div class="text-center mt-4">
            <a href="orders.php" class="btn btn-primary me-2">Gérer les commandes</a>
            <a href="article.php" class="btn btn-secondary me-2">Gérer les articles</a>
            <a href="../account.php" class="btn btn-dark">Mon compte</a>
        </div>
    </div>
    <?php if ($total_products_sold > 0): ?>
        <div class="row justify-content-center">
            <div class="col-md-5">
            <div class="card border-dark mb-3">
                <div class="card-header bg-dark text-white">Produits vendus</div>
                <div class="card-body text-center">
                <h5 class="card-title">Total des produits vendus</h5>
                <p class="card-text"><?php echo $total_products_sold; ?></p>
                </div>
            </div>
            </div>

            <div class="col-md-5">
            <div class="card border-primary mb-3">
                <div class="card-header bg-primary text-white">Produit le plus commandé</div>
                <div class="card-body text-center">
                <h5 class="card-title"><?php echo $most_ordered_article_name; ?></h5>
                <p class="card-text"><?php echo $most_ordered_quantity; ?> commandes</p>
                </div>
            </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-5">
            <div class="card border-success mb-3">
                <div class="card-header bg-success text-white">Produit le plus rentable</div>
                <div class="card-body text-center">
                <h5 class="card-title"><?php echo $highest_revenue_article_name; ?></h5>
                <p class="card-text">Revenu total : <?php echo number_format($highest_revenue_total, 2); ?> €</p>
                </div>
            </div>
            </div>

            <div class="col-md-5">
            <div class="card border-warning mb-3">
                <div class="card-header bg-warning text-white">Revenu total</div>
                <div class="card-body text-center">
                <h5 class="card-title">Revenu total généré</h5>
                <p class="card-text"><?php echo number_format($total_revenue, 2); ?> €</p>
                </div>
            </div>
            </div>
        </div>
  <!-- Liens de navigation -->

        <!-- Section des graphiques -->
        <div class="row mt-5 justify-content-center">
            <div class="col-md-3">
            <div class="card p-3">
                <h5 class="text-center mb-3">Répartition des ventes</h5>
                <canvas id="salesChart"></canvas>
            </div>
            </div>

            <div class="col-md-3">
            <div class="card p-3">
                <h5 class="text-center mb-3">Revenus générés</h5>
                <canvas id="revenueChart"></canvas>
            </div>
            </div>

            <div class="col-md-3">
            <div class="card p-3">
                <h5 class="text-center mb-3">Évolution des ventes</h5>
                <canvas id="salesTrendChart"></canvas>
            </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-info text-center">
            <p>Aucun produit vendu pour le moment.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    // Données dynamiques pour les graphiques
    var salesData = {
        labels: ["<?php echo $most_ordered_article_name; ?>", "Autres"],
        datasets: [{
            data: [<?php echo $most_ordered_quantity; ?>, <?php echo $total_products_sold - $most_ordered_quantity; ?>],
            backgroundColor: ['#0d6efd', '#adb5bd']
        }]
    };

    var revenueData = {
        labels: ["<?php echo $highest_revenue_article_name; ?>", "Autres"],
        datasets: [{
            data: [<?php echo $highest_revenue_total; ?>, <?php echo $total_revenue - $highest_revenue_total; ?>],
            backgroundColor: ['#198754', '#adb5bd']
        }]
    };

    var trendData = {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Ventes mensuelles (€)',
            data: <?php echo json_encode($sales); ?>,
            backgroundColor: ['#f39c12', '#ff5733', '#2ecc71', '#3498db', '#9b59b6', '#e74c3c'],
            borderColor: '#333',
            borderWidth: 1
        }]
    };

    // Initialisation des graphiques Chart.js

    var ctx1 = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx1, {
        type: 'doughnut',
        data: salesData,
        options: {
            responsive: false,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    var ctx2 = document.getElementById('revenueChart').getContext('2d');
    var revenueChart = new Chart(ctx2, {
        type: 'pie',
        data: revenueData,
        options: {
            responsive: false,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    var ctx3 = document.getElementById('salesTrendChart').getContext('2d');
    var salesTrendChart = new Chart(ctx3, {
        type: 'bar',
        data: trendData,
        options: {
            responsive: false,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
</script>


</body>
</html>
