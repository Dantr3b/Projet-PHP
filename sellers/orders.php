<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commandes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #fff;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        form {
            display: inline;
        }
        button {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        h1 {
            color: #333;
        }
        .filter-container {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php 
require_once("../config.php"); // Connexion à la base de données
session_start();

// Vérification de l'authentification et du rôle "seller"
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

// Connexion à la base de données via mysqli
if (!$conn) {
    die("Échec de la connexion : " . mysqli_connect_error());
}

// Gestion des filtres
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$order_id_search = isset($_GET['order_id']) ? intval($_GET['order_id']) : '';

// Base de la requête SQL
$sql_orders = "SELECT o.id AS order_id, a.name AS article_name, o.quantity, o.total_price, o.status, o.created_at 
               FROM `order` o 
               JOIN article a ON o.article_id = a.id 
               WHERE o.seller_id = ?";

// Ajout des filtres
if (!empty($status_filter)) {
    $sql_orders .= " AND o.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
if (!empty($order_id_search)) {
    $sql_orders .= " AND o.id = $order_id_search";
}

$sql_orders .= " ORDER BY o.created_at DESC";
$stmt_orders = mysqli_prepare($conn, $sql_orders);
mysqli_stmt_bind_param($stmt_orders, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt_orders);
$result_orders = mysqli_stmt_get_result($stmt_orders);

// Formulaire de tri et recherche
echo "<div class='filter-container'>";
echo "<form method='GET' action=''>";
echo "<label for='status'>Filtrer par statut :</label>";
echo "<select name='status' id='status'>";
echo "<option value=''>Tous</option>";
echo "<option value='en cours'" . ($status_filter === 'en cours' ? ' selected' : '') . ">En cours</option>";
echo "<option value='expédié'" . ($status_filter === 'expédié' ? ' selected' : '') . ">Expédié</option>";
echo "<option value='livré'" . ($status_filter === 'livré' ? ' selected' : '') . ">Livré</option>";
echo "</select>";
echo "<label for='order_id'>Rechercher par ID commande :</label>";
echo "<input type='number' name='order_id' id='order_id' value='$order_id_search'>";
echo "<button type='submit'>Appliquer</button>";
echo "</form>";
echo "</div>";

if ($result_orders && mysqli_num_rows($result_orders) > 0) {
    echo "<h1>Gestion des commandes</h1>";
    echo "<table>";
    echo "<tr><th>ID Commande</th><th>Article</th><th>Quantité</th><th>Prix Total</th><th>Status</th><th>Date</th><th>Action</th></tr>";

    while ($order = mysqli_fetch_assoc($result_orders)) {
        $order_id = $order['order_id'];
        $article_name = $order['article_name'];
        $quantity = $order['quantity'];
        $total_price = $order['total_price'];
        $status = $order['status'];
        $created_at = $order['created_at'];

        echo "<tr>";
        echo "<td>$order_id</td>";
        echo "<td>$article_name</td>";
        echo "<td>$quantity</td>";
        echo "<td>$total_price</td>";
        echo "<td>$status</td>";
        echo "<td>$created_at</td>";
        echo "<td>";
        echo "<form method='POST' action='update_order_status.php'>";
        echo "<input type='hidden' name='order_id' value='$order_id'>";
        echo "<select name='status'>";
        echo "<option value='en cours'" . ($status === 'en cours' ? ' selected' : '') . ">En cours</option>";
        echo "<option value='expédié'" . ($status === 'expédié' ? ' selected' : '') . ">Expédié</option>";
        echo "<option value='livré'" . ($status === 'livré' ? ' selected' : '') . ">Livré</option>";
        echo "</select>";
        echo "<button type='submit'>Mettre à jour</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "<h1>Aucune commande trouvée</h1>";
}

// Fermeture de la connexion
mysqli_close($conn);
?>

</body>
</html>
