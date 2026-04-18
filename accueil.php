<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();

if (must_change_password()) {
    header("Location: changer_mdp.php");
    exit();
}

$user = current_user();
$role = current_role();
$prenom = $user['prenom'] ?? '';
$nom = $user['nom'] ?? '';

function page_exists_local($file) {
    return file_exists(__DIR__ . '/' . $file);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <span>
            <?= htmlspecialchars(trim($prenom . ' ' . $nom)) ?>
            <em style="opacity:0.8;">(<?= htmlspecialchars(role_label($role)) ?>)</em>
        </span>

        <?php if (page_exists_local('index.php')): ?>
            <a href="index.php">Voir le site</a>
        <?php endif; ?>

        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">

    <div class="page-header">
        <h1>Bienvenue <?= htmlspecialchars($prenom) ?></h1>
        <p style="color:#666;">Tableau de bord de gestion du zoo</p>
    </div>

    <!-- MON ESPACE -->
    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Mon espace
        </h2>

        <div class="dashboard-grid">
            <?php if (page_exists_local('profil.php')): ?>
                <a href="profil.php" class="dash-card personnel">
                    <h3>Espace personnel</h3>
                    <p>Consulter vos informations personnelles</p>
                </a>
            <?php endif; ?>

            <?php if (page_exists_local('changer_mdp.php')): ?>
                <a href="changer_mdp.php" class="dash-card personnel">
                    <h3>Changer mot de passe</h3>
                    <p>Modifier votre mot de passe</p>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- GESTION DU ZOO -->
    <?php if (
        (can_access('animaux') && page_exists_local('animaux.php')) ||
        (can_access('soins') && page_exists_local('soins.php')) ||
        (can_access('nourrir_animaux') && page_exists_local('nourrir_animaux.php')) ||
        (can_access('travaux') && page_exists_local('travaux.php'))
    ): ?>
    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Gestion du zoo
        </h2>

        <div class="dashboard-grid">

            <?php if (can_access('animaux') && page_exists_local('animaux.php')): ?>
                <a href="animaux.php" class="dash-card animaux">
                    <h3>Animaux</h3>
                    <p>Consulter les animaux et leurs fiches</p>
                </a>
            <?php endif; ?>

            <?php if (can_access('soins') && page_exists_local('soins.php')): ?>
                <a href="soins.php" class="dash-card soins">
                    <h3>Soins</h3>
                    <p>Consulter les soins enregistrés</p>
                </a>
            <?php endif; ?>

            <?php if (can_access('nourrir_animaux') && page_exists_local('nourrir_animaux.php')): ?>
                <a href="nourrir_animaux.php" class="dash-card animaux">
                    <h3>Nourrissage</h3>
                    <p>Enregistrer les repas des animaux</p>
                </a>
            <?php endif; ?>

            <?php if (can_access('travaux') && page_exists_local('travaux.php')): ?>
                <a href="travaux.php" class="dash-card">
                    <h3>Travaux</h3>
                    <p>Réparations et maintenance des enclos</p>
                </a>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

    

    <!-- PERSONNEL -->
    <?php if (
        (can_access('personnel') && page_exists_local('personnel.php')) ||
        (can_access('ajouter_employe') && page_exists_local('ajouter_employe.php')) ||
        (($role === 'entretien' || $role === 'directeur') && page_exists_local('mes_zones_entretien.php'))
    ): ?>
    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Personnel
        </h2>

        <div class="dashboard-grid">

            <?php if (can_access('personnel') && page_exists_local('personnel.php')): ?>
                <a href="personnel.php" class="dash-card personnel">
                    <h3>Personnel</h3>
                    <p>Consulter les employés du zoo</p>
                </a>
            <?php endif; ?>

            <?php if (can_access('ajouter_employe') && page_exists_local('ajouter_employe.php')): ?>
                <a href="ajouter_employe.php" class="dash-card personnel">
                    <h3>Ajouter employé</h3>
                    <p>Créer un nouvel employé</p>
                </a>
            <?php endif; ?>

            <?php if (($role === 'entretien' || $role === 'directeur') && page_exists_local('mes_zones_entretien.php')): ?>
                <a href="mes_zones_entretien.php" class="dash-card personnel">
                    <h3>Zones d'entretien</h3>
                    <p>Consulter les zones affectées</p>
                </a>
            <?php endif; ?>
            <?php if ($role === 'directeur' || $role === 'comptable'): ?>
    <a href="parrainage.php" class="dash-card">
        <h3>Parrainages</h3>
        <p>Gérer les parrainages et montants</p>
    </a>

    <a href="ajouter_parrainage.php" class="dash-card">
        <h3>Ajouter un parrainage</h3>
        <p>Créer un nouveau parrainage</p>
    </a>
<?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

    <!-- COMMERCE ET FINANCES -->
    <?php if (
        (can_access('boutiques') && page_exists_local('boutiques.php')) ||
        (can_access('ajouter_ca') && page_exists_local('ajouter_ca.php')) ||
        (can_access('calculer_ca') && page_exists_local('calculer_ca.php')) ||
        (can_access('parrainages') && page_exists_local('parrainages.php'))
    ): ?>
    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Commerce et finances
        </h2>

        <div class="dashboard-grid">

            <?php if (can_access('boutiques') && page_exists_local('boutiques.php')): ?>
                <a href="boutiques.php" class="dash-card boutiques">
                    <h3>Boutiques</h3>
                    <p>Consulter les boutiques du zoo</p>
                </a>
            <?php endif; ?>

            <?php if (can_access('ajouter_ca') && page_exists_local('ajouter_ca.php')): ?>
                <a href="ajouter_ca.php" class="dash-card boutiques">
                    <h3>Saisir CA</h3>
                    <p>Enregistrer le chiffre d'affaires journalier</p>
                </a>
            <?php endif; ?>

            <?php if (can_access('calculer_ca') && page_exists_local('calculer_ca.php')): ?>
                <a href="calculer_ca.php" class="dash-card boutiques">
                    <h3>Calculer CA</h3>
                    <p>Calcul mensuel et annuel</p>
                </a>
            <?php endif; ?>

            <?php if (can_access('parrainages') && page_exists_local('parrainages.php')): ?>
                <a href="parrainages.php" class="dash-card boutiques">
                    <h3>Parrainages</h3>
                    <p>Consulter les dons et visiteurs parrains</p>
                </a>
            <?php endif; ?>

        </div>
    </div>
    <?php endif; ?>

    <!-- RAPPORTS -->
    <?php if (can_access('rapports') && page_exists_local('rapports.php')): ?>
    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Rapports
        </h2>

        <div class="dashboard-grid">
            <a href="rapports.php" class="dash-card boutiques">
                <h3>Rapports</h3>
                <p>Consulter les analyses et synthèses</p>
            </a>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>