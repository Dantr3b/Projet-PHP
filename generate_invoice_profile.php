<?php
require('lib/fpdf/fpdf.php');
require_once("config.php");
session_start();

// Vérification de la connexion de l'utilisateur
if (!isset($_SESSION['id'])) {
    die('Veuillez vous connecter pour voir vos factures.');
}

// Vérification de l'ID de la commande passé en paramètre
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    die('Commande invalide.');
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['id'];

// Récupération des informations de la commande depuis la base de données
$query = "
    SELECT o.id, o.quantity, o.total_price, o.created_at, a.name AS article_name, a.price AS unit_price
    FROM `order` o
    JOIN article a ON o.article_id = a.id
    WHERE o.id = ? AND o.seller_id = ?
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    die('Commande introuvable ou accès non autorisé.');
}

$order = mysqli_fetch_assoc($result);

// Création de la facture avec FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Titre
$pdf->Cell(0, 10, 'Facture de la commande #' . $order['id'], 0, 1, 'C');
$pdf->Ln(10);

// Informations client
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Client ID : ' . $user_id, 0, 1);
$pdf->Cell(0, 10, 'Date de commande : ' . date('d/m/Y', strtotime($order['created_at'])), 0, 1);
$pdf->Ln(10);

// Tableau des articles
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 10, 'Nom', 1);
$pdf->Cell(30, 10, 'Prix Unitaire', 1);
$pdf->Cell(30, 10, 'Quantite', 1);
$pdf->Cell(40, 10, 'Sous-Total', 1);
$pdf->Ln();

// Ajout des détails de la commande
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(80, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $order['article_name']), 1);
$pdf->Cell(30, 10, number_format($order['unit_price'], 2) . ' €', 1);
$pdf->Cell(30, 10, $order['quantity'], 1);
$pdf->Cell(40, 10, number_format($order['total_price'], 2) . ' €', 1);
$pdf->Ln();

// Total de la commande
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(140, 10, 'Total', 1);
$pdf->Cell(40, 10, number_format($order['total_price'], 2) . ' €', 1);
$pdf->Ln();

// Téléchargement de la facture
$pdf->Output('D', 'Facture_Commande_' . $order_id . '.pdf');

exit();
?>
