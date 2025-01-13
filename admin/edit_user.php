<?php
require_once("../config.php"); // Connexion à la base de données
session_start();

// Vérification du rôle admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Récupération de l'ID de l'utilisateur à modifier
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupération des informations de l'utilisateur
$stmt = mysqli_prepare($conn, "SELECT username, email, role FROM User WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "Utilisateur introuvable.";
    exit();
}

$error = "";
$success = "";

// Traitement du formulaire de mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);

    // Validation des champs
    if (empty($username) || empty($email) || empty($role)) {
        $error = "Tous les champs requis doivent être remplis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email est invalide.";
    } elseif (!in_array($role, ['user', 'seller', 'admin'])) {
        $error = "Le rôle sélectionné est invalide.";
    } else {
        // Mise à jour dans la base de données
        $update_stmt = mysqli_prepare($conn, "UPDATE User SET username = ?, email = ?, role = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "sssi", $username, $email, $role, $id);

        if (mysqli_stmt_execute($update_stmt)) {
            $success = "Utilisateur mis à jour avec succès.";
            // Rafraîchir les données utilisateur après la mise à jour
            $user['username'] = $username;
            $user['email'] = $email;
            $user['role'] = $role;
        } else {
            $error = "Erreur lors de la mise à jour : " . mysqli_error($conn);
        }

        mysqli_stmt_close($update_stmt);
    }
}

mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include("../navbar.php"); ?>

    <div class="container my-5">
        <h2 class="text-center mb-4">Modifier un utilisateur</h2>

        <!-- Messages d'erreur ou de succès -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Formulaire de mise à jour -->
        <form method="post" action="edit_user.php?id=<?php echo $id; ?>" class="row g-3">
            <div class="col-md-6">
                <label for="username" class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Adresse e-mail</label>
                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="col-md-12">
                <label for="role" class="form-label">Rôle</label>
                <select name="role" id="role" class="form-select" required>
                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="seller" <?php echo $user['role'] == 'seller' ? 'selected' : ''; ?>>Seller</option>
                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-dark">Mettre à jour</button>
                <a href="users.php" class="btn btn-secondary">Retour</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
