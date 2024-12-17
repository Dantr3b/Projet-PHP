<?php
// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');     // Hôte (dans XAMPP, c'est généralement localhost)
define('DB_USER', 'root');          // Nom d'utilisateur (par défaut dans XAMPP : root)
define('DB_PASS', '');              // Mot de passe (par défaut vide dans XAMPP)
define('DB_NAME', 'projet-php'); // Nom de votre base de données

// Connexion à la base de données
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Vérification de la connexion
if (!$conn) {
    die("Échec de la connexion : " . mysqli_connect_error());
}

// Connexion réussie
//echo "Connexion réussie à la base de données";
?>