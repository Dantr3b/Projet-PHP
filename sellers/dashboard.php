<?php 
require_once("../config.php"); // Connexion à la base de données
session_start();

// Vérification de l'authentification et du rôle "seller"
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

// Récupération de l'ID de l'acheteur (par exemple via une URL ou un formulaire)
$buyer_id = $_SESSION['id'];

if ($buyer_id > 0) {
    // Requête SQL pour récupérer le total des produits vendus
    $sql = "SELECT SUM(quantity) AS total_products_sold FROM `order` WHERE seller_id = ?;";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $buyer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Récupération du résultat
        $row = mysqli_fetch_assoc($result);
        $total_products_sold = $row['total_products_sold'] ?? 0;

        // Fermeture du statement
        mysqli_stmt_close($stmt);
    } else {
        echo "<h1>Erreur lors de la préparation de la requête.</h1>";
    }
} else {
    echo "<h1>Veuillez fournir un ID d'acheteur valide.</h1>";
}

// Produit le plus commandé avec le nom de l'article
$sql_most_ordered = "SELECT a.name AS article_name, o.article_id, SUM(o.quantity) AS total_quantity 
                    FROM `order` o
                    JOIN article a ON o.article_id = a.id 
                    WHERE o.seller_id = ?
                    GROUP BY o.article_id 
                    ORDER BY total_quantity DESC 
                    LIMIT 1;";
$stmt = mysqli_prepare($conn, $sql_most_ordered);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $buyer_id);
    mysqli_stmt_execute($stmt);
    $result_most_ordered = mysqli_stmt_get_result($stmt);

    if ($result_most_ordered && mysqli_num_rows($result_most_ordered) > 0) {
        $most_ordered = mysqli_fetch_assoc($result_most_ordered);
        $most_ordered_article_name = $most_ordered['article_name'];
        $most_ordered_quantity = $most_ordered['total_quantity'];
    }

    // Fermeture du statement
    mysqli_stmt_close($stmt);
} else {
    echo "<h1>Erreur lors de la préparation de la requête.</h1>";
}

// Produit qui rapporte le plus d'argent avec le nom de l'article
$sql_highest_revenue = "SELECT a.name AS article_name, o.article_id, SUM(o.total_price) AS total_revenue 
                        FROM `order` o 
                        JOIN article a ON o.article_id = a.id
                        WHERE o.seller_id = ?
                        GROUP BY o.article_id 
                        ORDER BY total_revenue DESC 
                        LIMIT 1";
$stmt = mysqli_prepare($conn, $sql_highest_revenue);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $buyer_id);
    mysqli_stmt_execute($stmt);
    $result_highest_revenue = mysqli_stmt_get_result($stmt);

    if ($result_highest_revenue && mysqli_num_rows($result_highest_revenue) > 0) {
        $highest_revenue = mysqli_fetch_assoc($result_highest_revenue);
        $highest_revenue_article_name = $highest_revenue['article_name'];
        $highest_revenue_total = $highest_revenue['total_revenue'];
    }

    // Fermeture du statement
    mysqli_stmt_close($stmt);
} else {
    echo "<h1>Erreur lors de la préparation de la requête.</h1>";
}

$sql_total_revenue = 'SELECT SUM(total_price) AS total_revenue FROM `order` WHERE seller_id = ?';
$stmt = mysqli_prepare($conn, $sql_total_revenue);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $buyer_id);
    mysqli_stmt_execute($stmt);
    $result_total_revenue = mysqli_stmt_get_result($stmt);

    if ($result_total_revenue && mysqli_num_rows($result_total_revenue) > 0) {
        $total_revenue_row = mysqli_fetch_assoc($result_total_revenue);
        $total_revenue = $total_revenue_row['total_revenue'];
    }

    // Fermeture du statement
    mysqli_stmt_close($stmt);
} else {
    echo "<h1>Erreur lors de la préparation de la requête.</h1>";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h1>Tableau de bord</h1>
    <?php if ($total_products_sold > 0): ?>
        <p>Total des produits vendus : <?php echo $total_products_sold; ?></p>
        <br>
        <p>Produit le plus commandé : <?php echo $most_ordered_article_name; ?> avec <?php echo $most_ordered_quantity; ?> commandes</p>
        <br>
        <p>Produit qui rapporte le plus d'argent : <?php echo $highest_revenue_article_name; ?> avec un revenu total de <?php echo $highest_revenue_total; ?></p>
        <br>
        <p>Revenu total : <?php echo $total_revenue; ?></p>
    <?php else: ?>
        <p>Aucun produit vendu pour le moment.</p>
    <?php endif; ?>


    <a href="orders.php">gerer les commandes</a>

    <a href="article.php">gerer les articles</a>

    <a href="../account.php">gerer le compte</a>

    
</body>
</html>
