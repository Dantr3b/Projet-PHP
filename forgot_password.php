<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("config.php");
require 'vendor/autoload.php'; // Charger PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez entrer une adresse e-mail valide.";
    } else {
        // Vérification si l'email existe dans la base de données
        $stmt = mysqli_prepare($conn, "SELECT id FROM user WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            // Générer un mot de passe temporaire sécurisé
            $temp_password = bin2hex(random_bytes(4)); // Exemple: 'a3f4c8e2'
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

            // Mettre à jour le mot de passe temporaire dans la base de données
            $stmt_update = mysqli_prepare($conn, "UPDATE user SET password = ? WHERE email = ?");
            mysqli_stmt_bind_param($stmt_update, "ss", $hashed_password, $email);
            if (mysqli_stmt_execute($stmt_update)) {

                // Détection dynamique du protocole (http ou https)
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

                // Détection de l'hôte (localhost ou IP)
                $host = $_SERVER['HTTP_HOST']; // Cela inclut le port automatiquement si défini

                // Générer le lien dynamique
                $login_link = $protocol . $host . "/Projet-PHP/login.php";

                // Configuration de PHPMailer pour l'envoi du mail
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'sandbox.smtp.mailtrap.io';
                    $mail->SMTPAuth = true;
                    $mail->Username = '5c41d43ed2f547'; // Remplace par ton username mailtrap
                    $mail->Password = 'f34b1ffcaa79c1';  // Remplace par ton password mailtrap
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                    $mail->Port = 587;

                    $mail->setFrom('support@monsite.com', 'Support Projet PHP');
                    $mail->addAddress($email);
                    $mail->Subject = 'Reinitialisation de votre mot de passe';
                    $mail->isHTML(true);
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px;'>
                            <h2 style='color: #333; text-align: center;'>Reinitialisation de votre mot de passe</h2>
                            <p style='font-size: 16px; color: #555;'>Bonjour,</p>
                            <p style='font-size: 16px; color: #555;'>Nous avons recu une demande de reinitialisation de votre mot de passe.</p>
                            <p style='font-size: 16px;'><strong>Voici votre nouveau mot de passe temporaire :</strong></p>
                            <p style='font-size: 24px; color: #007bff; text-align: center; font-weight: bold;'>$temp_password</p>
                            <p style='font-size: 16px; color: #555;'>Veuillez cliquer sur le bouton ci-dessous pour vous connecter :</p>
                            <div style='text-align: center;'>
                                <a href='$login_link' style='display:inline-block;padding:10px 20px;color:#fff;background-color:#007bff;text-decoration:none;border-radius:5px;'>Se connecter</a>
                            </div>
                            <p style='font-size: 16px; color: #555;'>Nous vous recommandons de modifier votre mot de passe apres connexion.</p>
                            <p style='font-size: 14px; color: #888;'>Ce mot de passe est temporaire et expirera dans 1 heure.</p>
                            <hr>
                            <p style='font-size: 12px; color: #aaa; text-align: center;'> © 2025 Cavedexception.com | Tous droits reserves.</p>
                        </div>
                    ";

                    $mail->send();
                    $success = "Un e-mail de réinitialisation a été envoyé.";
                } catch (Exception $e) {
                    $error = "Erreur lors de l'envoi de l'e-mail : " . $mail->ErrorInfo;
                }
            } else {
                $error = "Une erreur est survenue lors de la mise à jour du mot de passe.";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error = "Aucun compte associé à cette adresse e-mail.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include("navbar.php"); ?>

    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow-lg p-4" style="width: 100%; max-width: 400px;">
            <h2 class="text-center mb-4">Mot de passe oublié</h2>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php elseif (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="post" action="forgot_password.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Votre adresse e-mail" required>
                </div>

                <button type="submit" class="btn btn-dark w-100">Envoyer</button>
            </form>

            <p class="text-center mt-3"><a href="login.php">Retour à la connexion</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!-- LMAHV6EA4CUWNYZMLQXQ1PM6 -->