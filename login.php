<?php
// Inclut la connexion à la base de données
require_once("config.php");

session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Requête préparée pour éviter les injections SQL
        $stmt = mysqli_prepare($conn, "SELECT password FROM user WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Vérification du mot de passe avec password_verify
            if (password_verify($password, $row['password'])) {
                // Connexion réussie : initialiser la session
                $_SESSION['username'] = $username;
                $_SESSION['cart'] = [];
                header("Location: account.php"); // Rediriger vers la page protégée
                exit();
            } else {
                $error = "Identifiant ou mot de passe incorrect.";
            }
        } else {
            $error = "Identifiant ou mot de passe incorrect.";
        }

        mysqli_stmt_close($stmt);
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <!-- Navbar -->
    <?php include("navbar.php"); ?>

    <!-- Conteneur principal -->
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
            <h2 class="text-center mb-4">Connexion</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <!-- Champ Nom d'utilisateur -->
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Votre nom d'utilisateur" required>
                </div>

                <!-- Champ Mot de passe -->
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe" required>
                </div>

                <!-- Bouton de connexion -->
                <button type="submit" class="btn btn-dark w-100">Se connecter</button>
            </form>

            <p class="text-center mt-3">Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
