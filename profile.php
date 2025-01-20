<?php
require_once("config.php");
session_start();

// Vérifier si un ID de profil est passé dans l'URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: search.php");
    exit();
}

$profile_id = intval($_GET['id']);

// Récupération des informations du profil
$query = "SELECT username, email, photo, role, balance FROM user WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $profile_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($result);

if (!$profile) {
    die("Profil non trouvé.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?php echo htmlspecialchars($profile['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .profile-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            background: linear-gradient(120deg,rgb(46, 46, 46),rgb(42, 42, 42));
            color: white;
            padding: 50px 30px;
            text-align: center;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #fff;
            margin-top: -75px;
            position: relative;
            z-index: 1;
        }
        .profile-body {
            padding: 40px;
        }
   
        .stat-card {
            border-radius: 15px;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stat-card i {
            font-size: 40px;
            color: #6a11cb;
        }
    </style>
</head>
<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card profile-card">
                    <div class="profile-header">
                        <h2 class="fw-bold">Profil de <?php echo htmlspecialchars($profile['username']); ?></h2>
                    </div>
                    <div class="profile-body text-center">
                        <img src="uploads/<?php echo htmlspecialchars($profile['photo'] ?? 'defaultpp.jpg'); ?>" 
                             alt="Photo de profil" class="profile-picture mb-3">
                        <h4 class="mt-3"><?php echo htmlspecialchars($profile['username']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($profile['email']); ?></p>
                        <p class="badge bg-primary fs-6"><?php echo ucfirst(htmlspecialchars($profile['role'])); ?></p>
                        <p class="fs-4 text-success mt-3">Solde : <?php echo number_format($profile['balance'], 2); ?> €</p>

                        <p class="mt-4 text-muted">"Passionné par le vin et les spiritueux, <?php echo htmlspecialchars($profile['username']); ?> adore partager ses connaissances et proposer des produits de qualité à ses clients."</p>
                        
                        <a href="javascript:history.back()" class="btn btn-back mt-3">Retour</a>
                    </div>
                </div>
            </div>
        </div>
