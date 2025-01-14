<?php
require('lib/fpdf/fpdf.php');
session_start();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    die('Aucun article dans le panier.');
}

// Connexion à la base de données
require_once("config.php");

// Récupérer les articles du panier
$cart_items = [];
$article_ids = implode(',', array_keys($_SESSION['cart']));
$query = "SELECT id, name, price FROM article WHERE id IN ($article_ids)";
$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $row['quantity'] = $_SESSION['cart'][$row['id']];
    $row['subtotal'] = $row['price'] * $row['quantity'];
    $cart_items[] = $row;
}

// Calculer le total
$total_price = array_reduce($cart_items, function ($sum, $item) {
    return $sum + $item['subtotal'];
}, 0);

// Création de la facture avec FPDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Titre
$pdf->Cell(0, 10, 'Facture', 0, 1, 'C');
$pdf->Ln(10);

// Informations client (optionnelles)
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Client ID : ' . ($_SESSION['id'] ?? 'N/A'), 0, 1);
$pdf->Cell(0, 10, 'Date : ' . date('d/m/Y'), 0, 1);
$pdf->Ln(10);

// Tableau des articles
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 10, 'Nom', 1);
$pdf->Cell(30, 10, 'Prix Unitaire', 1);
$pdf->Cell(30, 10, 'Quantite', 1);
$pdf->Cell(40, 10, 'Sous-Total', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 12);
foreach ($cart_items as $item) {
    $pdf->Cell(80, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $item['name']), 1);
    $pdf->Cell(30, 10, number_format($item['price'], 2) . ' e', 1);
    $pdf->Cell(30, 10, $item['quantity'], 1);
    $pdf->Cell(40, 10, number_format($item['subtotal'], 2) . ' e', 1);
    $pdf->Ln();
}

// Total
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(140, 10, 'Total', 1);
$pdf->Cell(40, 10, number_format($total_price, 2) . ' e', 1);
$pdf->Ln();

// Téléchargement de la facture
$pdf->Output('D', 'Facture.pdf');

// Vider le panier
unset($_SESSION['cart']);

exit();
?>
