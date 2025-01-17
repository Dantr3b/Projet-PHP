<?php
require_once("config.php");
session_start();

// Vérification de l'utilisateur connecté
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Message de confirmation après validation de la commande
$message = "Votre commande a été validée avec succès !";
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <div class="text-center">
            <h1 class="text-success mb-4">Merci pour votre commande, <?php echo htmlspecialchars($username); ?> !</h1>
            <p class="lead"><?php echo htmlspecialchars($message); ?></p>
            <p>Nous avons bien reçu votre commande et elle est actuellement en cours de traitement.</p>
            <p>Un email de confirmation vous sera envoyé à l'adresse associée à votre compte.</p>
        </div>

        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="alert alert-info text-center">
                    <p><strong>Prochaine étape :</strong> Votre commande sera expédiée sous 3 à 5 jours ouvrés.</p>
                    <p>Vous recevrez une notification dès que votre commande aura été expédiée.</p>
                </div>
            </div>
        </div>

        <div class="text-center my-5">
            <a href="generate_invoice.php" class="btn btn-primary">Télécharger la facture</a>
        </div>

        <div class="text-center mt-4">
            <a href="collection.php" onclick="clearCart()" class="btn btn-primary">Retourner au catalogue</a>
            <a href="account.php" onclick="clearCart()" class="btn btn-dark">Consulter mon compte</a>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>&copy; 2025 Cave d'Exception. Tous droits réservés.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour vider le panier
        function clearCart() {
            fetch('clear_cart.php')
                .then(response => {
                    if (!response.ok) {
                        console.error('Failed to clear cart.');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>

</html>
