<?php
session_start();

// Vérifie si le panier existe, puis le vide
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}

// Retourne une réponse HTTP 200 OK
http_response_code(200);
echo "Panier vidé avec succès.";
?>
