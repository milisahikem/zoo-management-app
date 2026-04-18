<?php
require_once("connex.inc.php");

$id = $_GET['id'] ?? '';

if ($id === '' || !ctype_digit($id)) {
    die("Zone invalide.");
}

$conn = getDatabaseConnection();

/* Récupérer la zone */
$sql_zone = "SELECT nom_zone
             FROM ZONE
             WHERE id_zone = :id";
$stid_zone = oci_parse($conn, $sql_zone);
oci_bind_by_name($stid_zone, ":id", $id);
oci_execute($stid_zone);

$zone = oci_fetch_assoc($stid_zone);

if (!$zone) {
    oci_free_statement($stid_zone);
    oci_close($conn);
    die("Zone introuvable.");
}

/* Récupérer les animaux de la zone */
$sql_animaux = "SELECT a.nom,
                       e.nom_usuel AS espece
                FROM ANIMAL a
                JOIN ESPECE e ON a.id_espece = e.id_espece
                JOIN ENCLOS en ON a.id_enclos = en.id_enclos
                WHERE en.id_zone = :id
                ORDER BY a.nom";

$stid_animaux = oci_parse($conn, $sql_animaux);
oci_bind_by_name($stid_animaux, ":id", $id);
oci_execute($stid_animaux);

/* Petit texte fun selon la zone */
$nomZone = $zone['NOM_ZONE'];
$phrase = "Bienvenue dans cet univers sauvage.";
if (stripos($nomZone, 'félin') !== false) {
    $phrase = "Entrez dans le royaume des prédateurs majestueux 🦁";
} elseif (stripos($nomZone, 'savane') !== false) {
    $phrase = "Cap sur les grandes plaines et leurs géants fascinants 🌾";
} elseif (stripos($nomZone, 'rapace') !== false) {
    $phrase = "Levez les yeux vers les maîtres du ciel 🦅";
} elseif (stripos($nomZone, 'primate') !== false) {
    $phrase = "Découvrez l’intelligence et l’agilité de nos cousins les primates 🌴";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($zone['NOM_ZONE']) ?> - Zoo'land</title>
    <link rel="stylesheet" href="style_visiteur.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar scrolled">
        <div class="logo">🌿 ZOO'LAND</div>
        <ul class="nav-links">
            <li><a href="index.php#decouvrir">Retour aux Mondes</a></li>
            <li><a href="parrainage.php" style="color: #E6A117; font-weight: bold;">❤️ Parrainer</a></li>
            <li><a href="login.php" class="btn-login">Espace Personnel</a></li>
        </ul>
    </nav>

    <header class="hero" style="height: 55vh; background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.65)), url('images/accueil.avif');">
        <div class="hero-content">
            <h1><?= htmlspecialchars($zone['NOM_ZONE']) ?></h1>
            <p><?= htmlspecialchars($phrase) ?></p>
        </div>
    </header>

    <section class="section">
        <h2 class="section-title">Les animaux de cette zone</h2>

        <div class="cards-container">
            <?php
            $trouve = false;
            while ($animal = oci_fetch_assoc($stid_animaux)):
                $trouve = true;
            ?>
                <div class="card">
                    <div class="card-content">
                        <span class="tag"><?= htmlspecialchars($zone['NOM_ZONE']) ?></span>
                        <h3><?= htmlspecialchars($animal['NOM']) ?></h3>
                        <p>Espèce : <?= htmlspecialchars($animal['ESPECE']) ?></p>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if (!$trouve): ?>
                <div class="card">
                    <div class="card-content">
                        <span class="tag">Information</span>
                        <h3>Aucun animal affiché</h3>
                        <p>Cette zone ne contient actuellement aucun animal visible dans la base.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align:center; margin-top: 40px;">
            <a href="index.php#decouvrir" class="btn-primary">Retour à l'accueil</a>
        </div>
    </section>

</body>
</html>
<?php
oci_free_statement($stid_zone);
oci_free_statement($stid_animaux);
oci_close($conn);
?>
