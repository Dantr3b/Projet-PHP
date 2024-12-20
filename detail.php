<?php
require_once("config.php"); // Connexion à la base de données
session_start();


// Vérification et récupération de l'ID de l'article
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Initialisation du panier
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($id <= 0) {
    echo "ID d'article invalide.";
    exit();
}

// Récupération de l'utilisateur connecté
$user_id = $_SESSION['id'] ?? 0;

if ($user_id <= 0) {
    echo "Vous devez être connecté pour voir cet article.";
    exit();
}

// Récupération des détails de l'article
$stmt = mysqli_prepare($conn, "SELECT name, description, price, category, vintage, region, image, stock FROM article WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$article = mysqli_fetch_assoc($result);

// Si l'article n'existe pas, afficher un message
if (!$article) {
    echo "Article introuvable.";
    exit();
}

// Vérification si l'article est déjà dans les favoris
$fav_stmt = mysqli_prepare($conn, "SELECT id FROM favorites WHERE user_id = ? AND article_id = ?");
mysqli_stmt_bind_param($fav_stmt, "ii", $user_id, $id);
mysqli_stmt_execute($fav_stmt);
$fav_result = mysqli_stmt_get_result($fav_stmt);
$is_favorited = mysqli_fetch_assoc($fav_result) ? true : false;
mysqli_stmt_close($fav_stmt);

// Gestion de l'ajout aux favoris
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_favorites']) && !$is_favorited) {
    $insert_stmt = mysqli_prepare($conn, "INSERT INTO favorites (user_id, article_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($insert_stmt, "ii", $user_id, $id);
    if (mysqli_stmt_execute($insert_stmt)) {
        $is_favorited = true; // Mettre à jour l'état
    }
    mysqli_stmt_close($insert_stmt);
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($article['name']); ?></title>
</head>
<body>
    <h2><?php echo htmlspecialchars($article['name']); ?></h2>

    <!-- Affichage de l'image -->
    <img src="<?php echo htmlspecialchars(str_replace('../', '', $article['image'] ?? "")); ?>" 
         alt="<?php echo htmlspecialchars($article['name']); ?>" 
         width="400"><br><br>

    <!-- Détails de l'article -->
    <p><strong>Prix :</strong> <?php echo number_format($article['price'], 2); ?> €</p>
    <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($article['category'] ?? ""); ?></p>
    <p><strong>Année :</strong> <?php echo htmlspecialchars($article['vintage'] ?? ""); ?></p>
    <p><strong>Région :</strong> <?php echo htmlspecialchars($article['region'] ?? ""); ?></p>
    <p><strong>Stock disponible :</strong> <?php echo htmlspecialchars($article['stock']); ?></p>
    <p><strong>Description :</strong></p>
    <p><?php echo nl2br(htmlspecialchars($article['description'] ?? "")); ?></p>

    <!-- Bouton ajouter aux favoris -->
    <form method="post" action="detail.php?id=<?php echo $id; ?>">
        <?php if ($is_favorited): ?>
            <p style="color: green;">Cet article est déjà dans vos favoris.</p>
        <?php else: ?>
            <button type="submit" name="add_to_favorites">Ajouter aux favoris</button>
        <?php endif; ?>
    </form>

    <!-- Affichage du message -->
    <?php if (!empty($message)): ?>
        <p style="color: green;"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="post" onsubmit="addToCart(event)">
        <input type="hidden" name="id" value="<?php echo $id; ?>"> <!-- ID de l'article -->
        <label for="quantity">Quantité :</label>
        <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $article['stock']; ?>"> <!-- Quantité -->
        <button type="submit" name="add_to_cart">Ajouter au panier</button>
    </form>

    <!-- Zone pour afficher le message -->
    <p id="cart-message"></p>




    <p><a href="collection.php">Retour à la collection</a></p>


    <script>
    // Fonction pour envoyer les données au serveur via AJAX
    function addToCart(event) {
        event.preventDefault(); // Empêche le rechargement de la page

        const formData = new FormData(event.target); // Récupère les données du formulaire

        // Envoi de la requête AJAX
        fetch("carttools.php", {
            method: "POST",
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Affiche un message de succès
                document.getElementById("cart-message").innerHTML = "Article ajouté au panier avec succès.";
                document.getElementById("cart-message").style.color = "green";
            } else {
                // Affiche un message d'erreur
                document.getElementById("cart-message").innerHTML = data.message;
                document.getElementById("cart-message").style.color = "red";
            }
        })
        .catch(error => {
            console.error("Erreur:", error);
            document.getElementById("cart-message").innerHTML = "Une erreur est survenue.";
            document.getElementById("cart-message").style.color = "red";
        });
    }
</script>

</body>
</html>
