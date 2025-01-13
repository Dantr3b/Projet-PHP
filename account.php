<?php
require_once("config.php"); // Connexion à la base de données
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";
$photo = "";

// Récupération des informations actuelles de l'utilisateur
$username = $_SESSION['username'];
$stmt = mysqli_prepare($conn, "SELECT email, photo, role, id FROM User WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Récupération du rôle
$role = $user['role'] ?? 'user'; // Par défaut, 'user' si le rôle n'existe pas
$_SESSION['role'] = $user['role']; // Met à jour la session

$id = $user['id']; // Récupère l'ID de l'utilisateur
$_SESSION['id'] = $id;

// Mettre à jour le profil
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

        // Vérifier si le fichier est une image
        $file_type = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                // Supprimer l'ancienne photo si elle existe
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

        $update_query .= " WHERE username = ?";
        $types .= "s";
        $params[] = $username;

        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['username'] = $new_username; // Met à jour la session
            $success = "Profil mis à jour avec succès.";
        } else {
            $error = "Erreur lors de la mise à jour : " . mysqli_error($conn);
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
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover; /* Assure que l'image reste proportionnée */
        }
    </style>
</head>

<body>
    <?php include("navbar.php"); ?>

    <div class="container my-5">
        <h2 class="text-center mb-4">Mon Profil</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-body">
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
            </div>
        </div>

        <!-- Liens spécifiques selon le rôle -->
        <div class="text-center mt-4">
            <?php if ($role === 'admin'): ?>
                <a href="admin/users.php" class="btn btn-primary">Gestion des utilisateurs</a>
            <?php endif; ?>

            <?php if ($role === 'seller'): ?>
                <a href="sellers/dashboard.php" class="btn btn-secondary">Accéder au Dashboard</a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
