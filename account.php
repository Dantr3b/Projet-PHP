<?php
require_once("config.php"); // Connexion à la base de données
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Vérification et récupération de l'ID de l'utilisateur pour la page
$id_page = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_page <= 0) {
    echo "ID d'utilisateur invalide.";
    exit();
}


$error = "";
$success = "";
$photo = "";

// Chargement des informations utilisateur
if ($_SESSION['id'] === $id_page) {
    // Chargement des informations pour l'utilisateur connecté (propriétaire)
    $stmt = mysqli_prepare($conn, "SELECT email, photo, role, id, create_at FROM User WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_page);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
} else {
    // Chargement des informations pour un autre utilisateur
    $stmt = mysqli_prepare($conn, "SELECT username, email, photo, role, id, create_at FROM User WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_page);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
}

// Gestion du rôle
$role = $user['role'] ?? 'user';

// Si l'utilisateur connecté est le propriétaire du compte
if ($_SESSION['id'] === $id_page) {
    // Mise à jour du profil
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $new_username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $old_photo = $user['photo'];

        // Gestion de l'image uploadée
        if (!empty($_FILES['photo']['name'])) {
            $photo_name = basename($_FILES['photo']['name']);
            $target_path = "uploads/" . $photo_name;

            // Vérification du fichier
            $file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
            if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                    if (!empty($old_photo) && file_exists("uploads/" . $old_photo)) {
                        unlink("uploads/" . $old_photo);
                    }
                    $photo = $photo_name;
                } else {
                    $error = "Erreur lors de l'upload de l'image.";
                }
            } else {
                $error = "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.";
            }
        }

        // Validation des champs
        if (empty($new_username) || empty($email)) {
            $error = "Le nom d'utilisateur et l'email sont requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse e-mail n'est pas valide.";
        } elseif (!empty($password) && $password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            // Mise à jour des données
            $update_query = "UPDATE User SET username = ?, email = ?";
            $types = "ss";
            $params = [$new_username, $email];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_query .= ", password = ?";
                $types .= "s";
                $params[] = $hashed_password;
            }

            if ($photo) {
                $update_query .= ", photo = ?";
                $types .= "s";
                $params[] = $photo;
            }

            $update_query .= " WHERE id = ?";
            $types .= "i";
            $params[] = $id_page;

            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, $types, ...$params);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['username'] = $new_username; // Mise à jour de la session
                $success = "Profil mis à jour avec succès.";
            } else {
                $error = "Erreur lors de la mise à jour : " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Récupération des commandes pour le propriétaire
    $orders_query = "
    SELECT 
        o.id AS order_id, 
        a.name AS product_name, 
        a.image AS product_image, 
        o.status, 
        o.created_at 
    FROM 
        `order` o 
    JOIN 
        `article` a 
    ON 
        o.article_id = a.id 
    WHERE 
        o.buyer_id = ? 
    ORDER BY 
        o.created_at DESC";
    $stmt_orders = mysqli_prepare($conn, $orders_query);
    mysqli_stmt_bind_param($stmt_orders, "i", $id_page);
    mysqli_stmt_execute($stmt_orders);
    $orders = mysqli_stmt_get_result($stmt_orders);
    mysqli_stmt_close($stmt_orders);
} elseif ($role === 'seller') {
    // Informations pour un vendeur
    $info_query = "SELECT COUNT(o.id) AS total_orders, u.create_at FROM `order` o JOIN User u ON o.seller_id = u.id WHERE u.id = ?";
    $stmt_info = mysqli_prepare($conn, $info_query);
    mysqli_stmt_bind_param($stmt_info, "i", $id_page);
    mysqli_stmt_execute($stmt_info);
    $user_info = mysqli_stmt_get_result($stmt_info);
    $info = mysqli_fetch_assoc($user_info);

    // Récupération des produits pour un vendeur
    $products_query = "SELECT id, name, price, image , created_at FROM article WHERE author_id = ? ORDER BY created_at DESC";
    $stmt_products = mysqli_prepare($conn, $products_query);
    mysqli_stmt_bind_param($stmt_products, "i", $id_page);
    mysqli_stmt_execute($stmt_products);
    $products = mysqli_stmt_get_result($stmt_products);
    mysqli_stmt_close($stmt_products);
} else {
    // Informations pour un simple utilisateur
    $info_query = "
    SELECT COUNT(o.id) AS total_orders, u.create_at 
    FROM `order` o 
    JOIN User u ON o.buyer_id = u.id 
    WHERE u.id = ?";
    $stmt_info = mysqli_prepare($conn, $info_query);
    mysqli_stmt_bind_param($stmt_info, "i", $id_page);
    mysqli_stmt_execute($stmt_info);
    $user_info = mysqli_stmt_get_result($stmt_info);
    $info = mysqli_fetch_assoc($user_info);
    mysqli_stmt_close($stmt_info);
}


?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php if ($_SESSION['id'] == $id_page): ?>
            Mon Profil
        <?php else: ?>
            Profil de <?php echo htmlspecialchars($user['username'] ?? 'Utilisateur'); ?>
        <?php endif; ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <?php include("navbar.php"); ?>
    <div class="container my-5">
    <?php if ($_SESSION['id'] == $id_page): ?>
            <h2 class="text-center mb-4">Mon Profil </h2>
        <?php else: ?>
            <h2 class="text-center mb-4">Profil de <?php echo htmlspecialchars($user['username'] ?? 'Utilisateur'); ?></h2>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        

        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <?php if ($_SESSION['id'] === $id_page): ?>                    
                    <form method="post" action="account.php" enctype="multipart/form-data">
                        <!-- Photo de profil -->
                        <div class="mb-3 text-center">
                            <?php if (!empty($user['photo'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" 
                                     alt="Photo de profil" 
                                     class="profile-picture">
                            <?php else: ?>
                                <img src="style/images/user.svg" 
                                     alt="Avatar" 
                                     class="profile-picture">
                            <?php endif; ?>
                            <div class="mt-2">
                                <label for="photo" class="form-label">Changer de photo :</label>
                                <input type="file" name="photo" id="photo" class="form-control">
                            </div>
                        </div>

                        <!-- Informations utilisateur -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" name="username" id="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Adresse e-mail</label>
                            <input type="email" name="email" id="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe (laisser vide si inchangé)</label>
                            <input type="password" name="password" id="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-dark w-100">Mettre à jour</button>
                    </form>
                <?php elseif ($role === 'seller'): ?>
                    <h5 class="mb-3">Informations utilisateur :</h5>
                    <p>Rôle : <?php echo htmlspecialchars($role); ?></p>
                    <p>Date d'inscription : <?php echo htmlspecialchars($user['create_at']); ?></p>
                    <p>Nombre de commandes : <?php echo htmlspecialchars($info['total_orders'] ?? 0); ?></p>
                    <h5 class="mb-3">Produits en vente :</h5>
                    <ul>
                        <?php while ($product = mysqli_fetch_assoc($products)): ?>
                            <li>
                            <img src="<?php echo htmlspecialchars(str_replace('../', '', $product['image'])); ?>" 
                                alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                style="width: 150px; height: auto; object-fit: cover;" />
                                <?php echo htmlspecialchars($product['name']); ?> - <?php echo htmlspecialchars($product['price']); ?> €
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <h5 class="mb-3">Informations utilisateur :</h5>
                    <p>Rôle : <?php echo htmlspecialchars($role); ?></p>
                    <p>Date d'inscription : <?php echo htmlspecialchars($user['create_at']); ?></p>
                    <p>Nombre de commandes : <?php echo htmlspecialchars($info['total_orders'] ?? 0); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
