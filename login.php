<?php
require_once("config.php");
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = mysqli_prepare($conn, "SELECT id, password FROM user WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                // Stocker l'ID utilisateur et l'username en session
                $_SESSION['id'] = $row['id'];
                $_SESSION['username'] = $username;

                // Vérifier si le mot de passe correspond au format temporaire (8 caractères hexadécimaux)
                if (preg_match('/^[a-f0-9]{8}$/', $password)) {
                    // Rediriger vers la page de changement de mot de passe
                    header("Location: change_password.php");
                    exit();
                } else {
                    header("Location: index.php"); // Redirection vers l'accueil si connexion normale
                    exit();
                }
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
    <?php include("navbar.php"); ?>

    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
            <h2 class="text-center mb-4">Connexion</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Votre nom d'utilisateur" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe" required>
                </div>

                <button type="submit" class="btn btn-dark w-100">Se connecter</button>

                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="text-decoration-none">Mot de passe oublié ?</a>
                </div>
            </form>

            <p class="text-center mt-3">Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
