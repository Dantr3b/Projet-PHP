<?php
require_once("../config.php");
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

$article_id = intval($_GET['id']);
$seller_id = $_SESSION['id'];

$stmt = mysqli_prepare($conn, "SELECT name, description, price, stock FROM article WHERE id = ? AND author_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $article_id, $seller_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$article = mysqli_fetch_assoc($result)) {
    echo "Article introuvable.";
    exit();
}

// Mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);

    $update_stmt = mysqli_prepare($conn, "UPDATE article SET name = ?,description , price = ?, stock = ? WHERE id = ?");
    mysqli_stmt_bind_param($update_stmt, "sdii", $name, $description, $price, $stock, $article_id);
    mysqli_stmt_execute($update_stmt);

    header("Location: articles.php");
    exit();
}
?>

<form method="post">
    <label>Nom :</label>
    <input type="text" name="name" value="<?php echo htmlspecialchars($article['name']); ?>" required><br>

    <label>Description :</label>
    <textarea name="description" required><?php echo htmlspecialchars($article['description']); ?></textarea><br>

    <label>Prix :</label>
    <input type="number" name="price" step="0.01" value="<?php echo $article['price']; ?>" required><br>

    <label>Stock :</label>
    <input type="number" name="stock" value="<?php echo $article['stock']; ?>" required><br>

    <button type="submit">Modifier</button>
    <a href="article.php">Retour</a>
</form>
