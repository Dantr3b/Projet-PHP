<?php
session_start();
session_unset(); // Vide toutes les variables de session
session_destroy(); // Détruit la session

header("Location: login.php"); // Redirige vers la page de connexion
exit();
?>
