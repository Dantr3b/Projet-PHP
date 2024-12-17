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
$stmt = mysqli_prepare($conn, "SELECT username, email, role, balance FROM User WHERE id = ?");
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
    $balance = floatval($_POST['balance']);

    // Validation des champs
    if (empty($username) || empty($email) || empty($role)) {
        $error = "Tous les champs requis doivent être remplis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email est invalide.";
    } elseif (!in_array($role, ['user', 'seller', 'admin'])) {
        $error = "Le rôle sélectionné est invalide.";
    } else {
        // Mise à jour dans la base de données
        $update_stmt = mysqli_prepare($conn, "UPDATE User SET username = ?, email = ?, role = ?, balance = ? WHERE id = ?");
        mysqli_stmt_bind_param($update_stmt, "sssdi", $username, $email, $role, $balance, $id);

        if (mysqli_stmt_execute($update_stmt)) {
            $success = "Utilisateur mis à jour avec succès.";
            // Rafraîchir les données utilisateur après la mise à jour
            $user['username'] = $username;
            $user['email'] = $email;
            $user['role'] = $role;
            $user['balance'] = $balance;
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
    <title>Modifier un utilisateur</title>
</head>
<body>
    <h2>Modifier un utilisateur</h2>

    <!-- Affichage des messages d'erreur ou de succès -->
    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <!-- Formulaire de mise à jour -->
    <form method="post" action="edit_user.php?id=<?php echo $id; ?>">
        <label for="username">Nom d'utilisateur :</label><br>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br><br>

        <label for="email">Adresse e-mail :</label><br>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br><br>

        <label for="role">Rôle :</label><br>
        <select name="role" id="role" required>
            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
            <option value="seller" <?php echo $user['role'] == 'seller' ? 'selected' : ''; ?>>Seller</option>
            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select><br><br>

        <label for="balance">Solde :</label><br>
        <input type="number" name="balance" id="balance" step="0.01" value="<?php echo htmlspecialchars($user['balance']); ?>" required><br><br>

        <button type="submit">Mettre à jour</button>
        <a href="users.php">Retour à la gestion des utilisateurs</a>
    </form>
</body>
</html>
