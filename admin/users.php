<?php
require_once("../config.php"); // Connexion à la base de données
session_start();

// Vérification du rôle admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialisation des variables
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : "";

// Suppression d'un utilisateur
if (isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $stmt = mysqli_prepare($conn, "DELETE FROM User WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $success = "Utilisateur supprimé avec succès.";
}

// Récupération des utilisateurs avec filtres
$query = "SELECT id, username, email, role, balance FROM User WHERE 1=1";

if ($search) {
    $query .= " AND username LIKE ?";
    $search_param = "%$search%";
}
if ($role_filter) {
    $query .= " AND role = ?";
}

$stmt = mysqli_prepare($conn, $query);

if ($search && $role_filter) {
    mysqli_stmt_bind_param($stmt, "ss", $search_param, $role_filter);
} elseif ($search) {
    mysqli_stmt_bind_param($stmt, "s", $search_param);
} elseif ($role_filter) {
    mysqli_stmt_bind_param($stmt, "s", $role_filter);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs</title>
</head>
<body>
    <h2>Gestion des utilisateurs</h2>

    <!-- Formulaire de recherche et filtre -->
    <form method="get" action="users.php">
        <label for="search">Rechercher par nom :</label>
        <input type="text" name="search" id="search" placeholder="Nom d'utilisateur" value="<?php echo htmlspecialchars($search); ?>">

        <label for="role">Filtrer par rôle :</label>
        <select name="role" id="role">
            <option value="">Tous</option>
            <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>User</option>
            <option value="seller" <?php echo $role_filter == 'seller' ? 'selected' : ''; ?>>Seller</option>
            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>

        <button type="submit">Rechercher</button>
    </form>

    <!-- Message de succès -->
    <?php if (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <!-- Tableau des utilisateurs -->
    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom d'utilisateur</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Solde</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td><?php echo number_format($user['balance'], 2); ?> €</td>
                    <td>
                        <!-- Lien pour modifier -->
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>">Modifier</a>
                        <!-- Formulaire pour supprimer -->
                        <form method="post" action="users.php" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" onclick="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <p><a href="../account.php">Retour au profil</a></p>
</body>
</html>
