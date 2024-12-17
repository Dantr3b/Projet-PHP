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
                header("Location: dashboard.php"); // Rediriger vers la page protégée
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

<!-- Formulaire de connexion -->
<form method="post" action="login.php">
    <input type="text" name="username" placeholder="Nom d'utilisateur" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <button type="submit">Se connecter</button>
</form>

<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
