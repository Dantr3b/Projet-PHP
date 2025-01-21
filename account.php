<?php
require_once("config.php");
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

// Récupération des informations actuelles de l'utilisateur
$username = $_SESSION['username'];
$stmt = mysqli_prepare($conn, "SELECT email, photo, role, id, balance FROM User WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$role = $user['role'] ?? 'user'; 
$_SESSION['role'] = $user['role']; 
$id = $user['id']; 
$_SESSION['id'] = $id;

// Récupération des commandes pour les utilisateurs
if (in_array($role, ['user', 'seller', 'admin'])) {
  // Récupération des commandes pour tous les rôles (user, seller, admin)
$query_orders = "SELECT o.id, a.name, o.quantity, o.total_price, o.status, o.created_at 
                 FROM `order` o 
                 JOIN article a ON o.article_id = a.id 
                 WHERE o.seller_id = ?";
$stmt_orders = mysqli_prepare($conn, $query_orders);
mysqli_stmt_bind_param($stmt_orders, "i", $id);
mysqli_stmt_execute($stmt_orders);
$result_orders = mysqli_stmt_get_result($stmt_orders);
}

// Mettre à jour le profil
// Gestion de la mise à jour de la photo de profil
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_username) || empty($email)) {
        $error = "Le nom d'utilisateur et l'email sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse e-mail n'est pas valide.";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Gestion de la photo de profil
        if (!empty($_FILES['photo']['name'])) {
            $target_dir = "uploads/";
            $image_name = basename($_FILES['photo']['name']);
            $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($image_extension, $allowed_extensions)) {
                $new_image_name = "profile_" . $id . "_" . time() . "." . $image_extension;
                $target_file = $target_dir . $new_image_name;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                    // Met à jour la photo de profil dans la base de données
                    $update_photo_query = "UPDATE User SET photo = ? WHERE id = ?";
                    $stmt_photo = mysqli_prepare($conn, $update_photo_query);
                    mysqli_stmt_bind_param($stmt_photo, "si", $new_image_name, $id);
                    mysqli_stmt_execute($stmt_photo);
                    mysqli_stmt_close($stmt_photo);

                    // Mise à jour de la session
                    $_SESSION['photo'] = $new_image_name;
                    $success = "Photo de profil mise à jour avec succès.";
                } else {
                    $error = "Erreur lors du téléchargement de l'image.";
                }
            } else {
                $error = "Format d'image non pris en charge. Formats autorisés: JPG, JPEG, PNG, GIF.";
            }
        }

        // Mise à jour des autres informations
        $update_query = "UPDATE User SET username = ?, email = ?";
        $types = "ss";
        $params = [$new_username, $email];

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query .= ", password = ?";
            $types .= "s";
            $params[] = $hashed_password;
        }

        $update_query .= " WHERE username = ?";
        $types .= "s";
        $params[] = $username;

        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['username'] = $new_username;
            $success = "Profil mis à jour avec succès.";
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-bottom: 20px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ddd;
            cursor: pointer;
        }
        .hidden-input {
            display: none;
        }
    </style>
</head>

<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <h2 class="text-center mb-4">Mon Profil</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success text-center"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Colonne Profil -->
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h5 class="card-title">Informations du Profil</h5>
                        <form method="post" action="account.php" enctype="multipart/form-data">
                        <label for="photoInput">
                            <img src="<?php echo !empty($user['photo']) ? 'uploads/' . htmlspecialchars($user['photo']) : 'uploads/defaultpp.png'; ?>" 
                                alt="Photo de profil" class="profile-picture my-3" id="profilePicture">
                        </label>
                        <input type="file" name="photo" id="photoInput" class="hidden-input" accept="image/*">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" name="username" id="username" class="form-control" 
                                value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse e-mail</label>
                            <input type="email" name="email" id="email" class="form-control" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" name="password" id="password" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-dark w-100">Mettre à jour</button>
                    </form>
                    </div>
                </div>
            </div>

            <!-- Colonne Solde -->
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h5 class="card-title">Votre Solde</h5>
                        <p class="display-4 text-success"><?php echo number_format($user['balance'], 2); ?> €</p>
                        <form action="add_balance.php" method="POST" class="mt-3">
                            <div class="input-group">
                                <input type="number" name="amount" class="form-control" 
                                       placeholder="Montant à ajouter" min="1" step="0.01" required>
                                <button type="submit" class="btn btn-success">Ajouter des fonds</button>
                            </div>
                        </form>
                        <br>
                        <?php if ($role === 'admin'): ?>
                            <a href="admin/users.php" class="btn btn-primary mt-3">Gestion des utilisateurs</a>
                        <?php endif; ?>
                        <?php if ($role === 'seller'): ?>
                            <a href="sellers/dashboard.php" class="btn btn-secondary mt-3">Accéder au Dashboard</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Historique des commandes -->
                <?php if (in_array($role, ['user', 'seller', 'admin'])): ?>

                    <div class="card shadow mt-4">
                        <div class="card-body">
                        <h5 class="card-title">
                                <?php echo ($role === 'seller') ? 'Commandes reçues' : 'Mes Commandes'; ?>
                            </h5>
                            <div style="max-height: 330px; overflow-y: auto;">
                                <ul class="list-group">
                                    <?php while ($order = mysqli_fetch_assoc($result_orders)): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo htmlspecialchars($order['name']); ?></strong> - 
                                            Quantité: <?php echo $order['quantity']; ?> - 
                                            Total: <?php echo number_format($order['total_price'], 2); ?> € - 
                                            <br>
                                            <br>
                                            <a href="generate_invoice_profile.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                                Télécharger la facture
                                            </a>
                                            <br>
                                            <br>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('photoInput').addEventListener('change', function() {
            document.getElementById('profilePicture').src = URL.createObjectURL(this.files[0]);
        });
    </script>
</body>
</html>
