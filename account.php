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
    <title>Modifier votre profil</title>
    <link rel="stylesheet" href="style/css/account.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pattaya&display=swap" rel="stylesheet">
</head>

<header>
    <?php include("navbar.php"); ?>
</header>

<body>
    <h2>Modifier votre profil</h2>

    <?php if (!empty($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif (!empty($success)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="post" action="account.php" enctype="multipart/form-data">
        <label for="username">Nom d'utilisateur :</label><br>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username'] ?? $_SESSION['username']); ?>" required><br><br>

        <label for="email">Adresse e-mail :</label><br>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br><br>

        <label for="password">Nouveau mot de passe (laisser vide si inchangé) :</label><br>
        <input type="password" name="password" id="password"><br><br>

        <label for="confirm_password">Confirmer le mot de passe :</label><br>
        <input type="password" name="confirm_password" id="confirm_password"><br><br>

        <label for="photo">Photo de profil (JPG, JPEG, PNG, GIF) :</label><br>
        <input type="file" name="photo" id="photo"><br><br>

        <?php if (!empty($user['photo'])): ?>
            <p>Photo actuelle :</p>
            <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" width="100" alt="Photo de profil">
        <?php endif; ?>

        <button type="submit">Mettre à jour</button>
    </form>

    <?php if ($role === 'admin'): ?>
            <p><a href="admin/users.php">Gestion des utilisateurs</a></p>
    <?php endif; ?>

    <?php if ($role === 'seller'): ?>

        <p><a href="sellers/article.php">Vendre un article</a></p>
        <p><a href="sellers/article.php">Gérer les articles</a></p>
    <?php endif; ?>

    <p><a href="logout.php">Se déconnecter</a></p>
</body>
</html>
