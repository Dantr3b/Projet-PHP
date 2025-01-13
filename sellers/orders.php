<?php
session_start();
require_once("../config.php");

// Vérification de l'authentification et du rôle "seller"
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

$seller_id = $_SESSION['id'];

// Gestion des filtres
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$order_id_search = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Base de la requête SQL
$query = "
    SELECT 
        o.id AS order_id,
        a.name AS article_name,
        o.quantity,
        o.total_price,
        o.status,
        o.created_at
    FROM `order` o
    JOIN article a ON o.article_id = a.id
    WHERE a.author_id = ?";

// Ajout des filtres dynamiques
$filters = [];
$types = "i";
$params = [$seller_id];

if (!empty($status_filter)) {
    $query .= " AND o.status = ?";
    $filters[] = $status_filter;
    $types .= "s";
}

if ($order_id_search > 0) {
    $query .= " AND o.id = ?";
    $filters[] = $order_id_search;
    $types .= "i";
}

$query .= " ORDER BY o.created_at DESC";

// Préparation et exécution de la requête
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...array_merge($params, $filters));
mysqli_stmt_execute($stmt);
$result_orders = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include("../navbar.php"); ?>

    <div class="container my-5">
        <h1 class="text-center mb-4">Gestion des commandes</h1>

        <!-- Formulaire de tri et recherche -->
        <div class="mb-4">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Filtrer par statut</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Tous</option>
                        <option value="en cours" <?php echo $status_filter === 'en cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="expédié" <?php echo $status_filter === 'expédié' ? 'selected' : ''; ?>>Expédié</option>
                        <option value="livré" <?php echo $status_filter === 'livré' ? 'selected' : ''; ?>>Livré</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="order_id" class="form-label">Rechercher par ID commande</label>
                    <input type="number" name="order_id" id="order_id" class="form-control" value="<?php echo htmlspecialchars($order_id_search); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Appliquer</button>
                </div>
            </form>
        </div>

        <!-- Tableau des commandes -->
        <?php if ($result_orders && mysqli_num_rows($result_orders) > 0): ?>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID Commande</th>
                        <th>Article</th>
                        <th>Quantité</th>
                        <th>Prix Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = mysqli_fetch_assoc($result_orders)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars($order['article_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                            <td><?php echo number_format($order['total_price'], 2); ?> €</td>
                            <td>
                                <form method="POST" action="update_order_status.php">
                                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                    <select name="status" class="form-select">
                                        <option value="en cours" <?php echo $order['status'] === 'en cours' ? 'selected' : ''; ?>>En cours</option>
                                        <option value="expédié" <?php echo $order['status'] === 'expédié' ? 'selected' : ''; ?>>Expédié</option>
                                        <option value="livré" <?php echo $order['status'] === 'livré' ? 'selected' : ''; ?>>Livré</option>
                                    </select>
                                    <button type="submit" class="btn btn-success mt-2">Mettre à jour</button>
                                </form>
                            </td>
                            <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <p>Aucune commande trouvée.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
