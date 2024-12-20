<?php
// Inclut les paramètres de connexion à la base de données
require_once("config.php"); 

$error = "";
$success = "";

// Traitement du formulaire d'inscription
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer et nettoyer les données du formulaire
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'user'; // Rôle par défaut

    // Vérification des champs requis
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Tous les champs sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse e-mail n'est pas valide.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'utilisateur existe déjà
        $stmt = mysqli_prepare($conn, "SELECT id FROM User WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_fetch_assoc($result)) {
            $error = "Ce nom d'utilisateur ou cette adresse e-mail existe déjà.";
        } else {
            // Hachage du mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insertion des données dans la table User
            $stmt = mysqli_prepare($conn, "INSERT INTO User (username, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $role);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Erreur lors de l'inscription : " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<link rel="stylesheet" href="style/css/register.css">

<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
</head>

<?php include("navbar.php"); ?>


<body>
    <h2>Créer un compte</h2>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="post" action="register.php">
        <label for="username">Nom d'utilisateur :</label><br>
        <input type="text" name="username" id="username" required><br><br>

        <label for="email">Adresse e-mail :</label><br>
        <input type="email" name="email" id="email" required><br><br>

        <label for="password">Mot de passe :</label><br>
        <input type="password" name="password" id="password" required><br><br>

        <label for="confirm_password">Confirmer le mot de passe :</label><br>
        <input type="password" name="confirm_password" id="confirm_password" required><br><br>

        <button type="submit">S'inscrire</button>
        <br>

        <p>Vous avez déjà un compte ? <a href="login.php">Connectez-vous ici
    </form>
</body>
</html>
