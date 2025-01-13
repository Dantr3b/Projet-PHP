<?php
require_once("../config.php");
session_start();

// Vérification du rôle admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialisation des variables
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$role_filter = isset($_GET['role']) ? trim($_GET['role']) : "";
$success = "";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php include("../navbar.php"); ?>

    <div class="container my-5">
        <h1 class="text-center mb-4">Gestion des utilisateurs</h1>

        <!-- Message de succès -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de recherche et de filtre -->
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="search" class="form-label">Rechercher par nom</label>
                <input type="text" name="search" id="search" class="form-control" placeholder="Nom d'utilisateur" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <label for="role" class="form-label">Filtrer par rôle</label>
                <select name="role" id="role" class="form-select">
                    <option value="">Tous</option>
                    <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="seller" <?php echo $role_filter == 'seller' ? 'selected' : ''; ?>>Seller</option>
                    <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-dark w-100">Rechercher</button>
            </div>
        </form>

        <!-- Tableau des utilisateurs -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <div class="d-flex">
                                    <!-- Lien pour modifier -->
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary me-2">Modifier</a>
                                    <!-- Formulaire pour supprimer -->
                                    <form method="post" action="users.php" onsubmit="return confirm('Voulez-vous vraiment supprimer cet utilisateur ?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-4">
            <a href="../account.php" class="btn btn-secondary">Retour au profil</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
