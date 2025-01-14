# **Cave d'Exception - Site E-commerce en PHP**

Bienvenue dans **Cave d'Exception**, un site e-commerce de vente de bouteilles d'alcool haut de gamme : vins, champagnes, et spiritueux. Ce projet est développé en **PHP natif** et permet une gestion complète des utilisateurs, des vendeurs, et des administrateurs.

---

## **Sommaire**
1. [Description du projet](#description-du-projet)
2. [Prérequis](#prérequis)
3. [Installation](#installation)
4. [Structure des pages](#structure-des-pages)
5. [Base de données](#base-de-données)
6. [Fonctionnalités](#fonctionnalités)
7. [Rôles et permissions](#rôles-et-permissions)
8. [Technologies utilisées](#technologies-utilisées)
9. [Améliorations futures](#améliorations-futures)
10. [Statut du projet](#statut-du-projet)
11. [Crédits](#crédits)

---

## **Description du projet**

Ce projet est un site e-commerce réalisé pour un module PHP, sans framework, avec pour objectifs :
- Proposer une plateforme élégante de vente de bouteilles haut de gamme.
- Gérer les utilisateurs (clients, vendeurs et administrateurs).
- Assurer la sécurité des transactions et des données.
- Créer une architecture claire et maintenable pour le développement.

---

## **Prérequis**

- Serveur local : **XAMPP** ou **MAMP** (PHP 8+).
- Base de données : **MySQL**.
- Gestionnaire de versions : **Git**.
- Navigateur web (ex: Chrome, Firefox).

---

## **Installation**

1. **Cloner le projet** :
   ```bash
   git clone https://github.com/votre-utilisateur/votre-projet.git
   cd votre-projet
   ```

2. **Créer la base de données** :
   Importer le fichier SQL fourni (php_exam_db.sql) dans phpMyAdmin.

3.**Configurer la connexion à la base de données** :

Modifier les informations de connexion dans un fichier config.php :
```php
<?php
$db = new mysqli("localhost", "root", "", "php_exam_db");
if ($db->connect_error) {
    die("Erreur de connexion : " . $db->connect_error);
}
?>
```

## **Structure des pages**

| **Page**                     | **Description**                           | **Statut**    |
|------------------------------|-------------------------------------------|---------------|
| `/home`                      | Page d'accueil, affichage des articles    | 🎨 Ajouter du style    |
| `/register`                  | Page d'inscription                        | ✅ Terminé   |
| `/login`                     | Page de connexion                         | ✅ Terminé   |
| `/catalogue`                 | Liste des articles avec filtres           | 🎨 Ajouter du style    |
| `/detail?id=ID`              | Page détaillée d'un produit               | 🎨 Ajouter du style     |
| `/cart`                      | Affichage et gestion du panier            | 🚧 Ajouter du style    |
| `/cart/validate`             | Validation des informations de commande   | 🚧 Ajouter du style   |
| `/favorites`                 | Liste des articles favoris                | 🎨 Ajouter du style    |
| `/account`                   | Gestion du compte utilisateur             | ✅ Terminé    |
| `sellers/article`            | Liste des articles proposer par le vendeur| 🎨 Ajouter du style    |
| `sellers/sell`               | Formulaire de création d'article (vendeur)| 🎨 Ajouter du style    |
| `/edit?id=ID`                | Modification/suppression d'article        | 🎨 Ajouter du style    |
| `/seller/orders`             | Liste des commandes reçues par le vendeur | 🎨 Ajouter du style    |
| `/seller/dashboard`          | Tableau de bord administrateur            | 🎨 Ajouter du style    |
| `/admin/dashboard`           | Tableau de bord administrateur            | 🚧 À faire    |
| `/admin/users`               | Gestion des utilisateurs                  | 🎨 Ajouter du style    |
| `/admin/articles`            | Gestion des articles                      | 🚧 Ajouter du style    |
| `/search`                    | Recherche avancée multi-critères          | 🚧 Ajouter du style   |
| `/giftboxes`                 | Page des coffrets cadeaux                 | 🚧 À faire    |
| `/contact`                   | Formulaire de contact                     | 🚧 À faire    |
| `/404`                       | Page d'erreur 404                         | 🚧 À faire    |

---

## **Base de données**

Le projet utilise une base de données **MySQL** avec les tables suivantes :

- `User` : Gestion des utilisateurs (clients, vendeurs, admins).
- `Article` : Produits disponibles à la vente.
- `Cart` : Articles ajoutés dans le panier.
- `Invoice` : Historique des commandes validées.
- `Favorites` : Articles sauvegardés en favoris.
- `Review` : Système de notation et commentaires.
- `Stock` : Gestion des quantités en stock.
- `Order` : Gestion des commandes reçues pour les vendeurs.
- `GiftBox` : Coffrets cadeaux.
- `GiftBox_Article` : Articles inclus dans chaque coffret cadeau.

---

## **Rôles et permissions**

| **Rôle**       | **Permissions**                                                                 |
|-----------------|-------------------------------------------------------------------------------|
| `user`         | Consulter les produits, ajouter au panier, passer des commandes, noter.       |
| `seller`       | Créer, modifier, supprimer ses propres articles, consulter ses commandes.     |
| `admin`        | Gérer tous les utilisateurs et articles, modérer le site, gérer les stocks.   |

---

## **Fonctionnalités**

- **Authentification sécurisée** (bcrypt).
- **Gestion complète des produits** (ajout, modification, suppression).
- **Système de panier** avec validation et confirmation.
- **Page vendeur** pour suivre les commandes reçues.
- **Gestion des favoris** pour les utilisateurs.
- **Espace administrateur** pour modérer les utilisateurs et produits.

---

## **Technologies utilisées**

- **Backend** : PHP 8 (natif).  
- **Base de données** : MySQL.  
- **Frontend** : HTML, CSS, JavaScript.  
- **Outils** : XAMPP/MAMP, phpMyAdmin.  

---

## **Statut du projet**

- **Actuellement en développement 🚧**.  
- **Date limite** : **14 janvier**.

---

## **Crédits**

Projet réalisé par :  
- **Arthur Chessé**  
- **Killian Roux**  
- **Gabin Rolland-Bertrand**

---
