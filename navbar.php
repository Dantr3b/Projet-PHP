<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <style>body {
    padding-top: 70px; /* Ajustez en fonction de la hauteur de votre navbar */
}
</style>
    <div class="container">
        <a class="navbar-brand" href="/Projet-PHP/index.php">Cave d'Exception</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/Projet-PHP/collection.php">Collection</a></li>
                <?php if (!isset($_SESSION['username'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/login.php">Connexion</a></li>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/register.php">Inscription</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/cart.php">Panier</a></li>
                    <?php if ($_SESSION['role'] === 'seller'): ?>
                        <li class="nav-item"><a class="nav-link" href="/Projet-PHP/sellers/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/account.php">Mon Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/Projet-PHP/logout.php">Déconnexion</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
