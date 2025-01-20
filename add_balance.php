<?php
require_once("config.php");
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['id'];
    $amount = floatval($_POST['amount']);

    if ($amount > 0) {
        $query = "UPDATE User SET balance = balance + ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "di", $amount, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Votre solde a été mis à jour avec succès.";
        } else {
            $_SESSION['error'] = "Une erreur s'est produite lors de l'ajout des fonds.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = "Montant invalide.";
    }

    header("Location: account.php");
    exit();
}
?>
