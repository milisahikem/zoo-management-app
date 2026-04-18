<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('animaux');

$conn = getDatabaseConnection();

$id_animal = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_animal <= 0) {
    die("<div class='container'><div class='erreur'>Animal non spécifié.</div></div>");
}

$role = current_role();
$user_id = current_user_id();

/* =========================
   VERIFICATION D'ACCES PAR ROLE
========================= */
if ($role === 'soigneur') {
    $sql_check = "SELECT COUNT(*) AS NB
                  FROM ANIMAL
                  WHERE id_animal = :id_animal
                    AND id_soigneur_attitre = :id_personne";

    $stid_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stid_check, ":id_animal", $id_animal);
    oci_bind_by_name($stid_check, ":id_personne", $user_id);
    oci_execute($stid_check);
    $check = oci_fetch_assoc($stid_check);
    oci_free_statement($stid_check);

    if (!$check || (int)$check['NB'] === 0) {
        oci_close($conn);
        die("<div class='container'><div class='erreur'>Accès refusé à cet animal.</div></div>");
    }
}

if ($role === 'chef_soigneur') {
    $sql_check = "SELECT COUNT(*) AS NB
                  FROM ANIMAL a
                  JOIN ENCLOS en ON a.id_enclos = en.id_enclos
                  JOIN ZONE z ON en.id_zone = z.id_zone
                  WHERE a.id_animal = :id_animal
                    AND z.id_personnel_chef = :id_personne";

    $stid_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stid_check, ":id_animal", $id_animal);
    oci_bind_by_name($stid_check, ":id_personne", $user_id);
    oci_execute($stid_check);
    $check = oci_fetch_assoc($stid_check);
    oci_free_statement($stid_check);

    if (!$check || (int)$check['NB'] === 0) {
        oci_close($conn);
        die("<div class='container'><div class='erreur'>Accès refusé à cet animal.</div></div>");
    }
}

/* =========================
   INFOS PRINCIPALES ANIMAL
========================= */
$sql_animal = "SELECT
                    a.id_animal,
                    a.nom,
                    TO_CHAR(a.date_de_naissance, 'DD/MM/YYYY') AS date_naissance,
                    a.poids,
                    a.regime_alimentaire,
                    a.id_espece,
                    a.id_enclos,
                    a.id_soigneur_attitre,

                    es.nom_usuel,
                    es.nom_latin,
                    es.menacee,

                    en.latitude,
                    en.longitude,
                    en.surface,
                    en.particularite,

                    z.id_zone,
                    z.nom_zone,

                    p.id_personne AS id_soigneur,
                    p.prenom AS prenom_soigneur,
                    p.nom AS nom_soigneur,
                    p.id_remplacant,

                    pr.id_personne AS id_remplacant_personne,
                    pr.prenom AS prenom_remplacant,
                    pr.nom AS nom_remplacant

               FROM ANIMAL a
               JOIN ESPECE es ON a.id_espece = es.id_espece
               JOIN ENCLOS en ON a.id_enclos = en.id_enclos
               JOIN ZONE z ON en.id_zone = z.id_zone
               JOIN PERSONNEL p ON a.id_soigneur_attitre = p.id_personne
               LEFT JOIN PERSONNEL pr ON p.id_remplacant = pr.id_personne
               WHERE a.id_animal = :id_animal";

$stid_animal = oci_parse($conn, $sql_animal);
oci_bind_by_name($stid_animal, ":id_animal", $id_animal);
oci_execute($stid_animal);
$animal = oci_fetch_assoc($stid_animal);
oci_free_statement($stid_animal);

if (!$animal) {
    oci_close($conn);
    die("<div class='container'><div class='erreur'>Animal introuvable.</div></div>");
}

/* =========================
   PARENTS
========================= */
$parents = [];
$sql_parents = "SELECT
                    a.id_animal,
                    a.nom,
                    es.nom_usuel
                FROM EST_PARENT ep
                JOIN ANIMAL a ON ep.id_animal_parent = a.id_animal
                JOIN ESPECE es ON a.id_espece = es.id_espece
                WHERE ep.id_animal_enfant = :id_animal
                ORDER BY a.nom";

$stid_parents = oci_parse($conn, $sql_parents);
oci_bind_by_name($stid_parents, ":id_animal", $id_animal);
oci_execute($stid_parents);

while ($row = oci_fetch_assoc($stid_parents)) {
    $parents[] = $row;
}
oci_free_statement($stid_parents);

/* =========================
   ENFANTS
========================= */
$enfants = [];
$sql_enfants = "SELECT
                    a.id_animal,
                    a.nom,
                    es.nom_usuel
                FROM EST_PARENT ep
                JOIN ANIMAL a ON ep.id_animal_enfant = a.id_animal
                JOIN ESPECE es ON a.id_espece = es.id_espece
                WHERE ep.id_animal_parent = :id_animal
                ORDER BY a.nom";

$stid_enfants = oci_parse($conn, $sql_enfants);
oci_bind_by_name($stid_enfants, ":id_animal", $id_animal);
oci_execute($stid_enfants);

while ($row = oci_fetch_assoc($stid_enfants)) {
    $enfants[] = $row;
}
oci_free_statement($stid_enfants);

/* =========================
   ESPECES COMPATIBLES
========================= */
$compatibles = [];
$sql_compatibles = "SELECT
                        e.id_espece,
                        e.nom_usuel,
                        e.nom_latin
                    FROM COHABITER c
                    JOIN ESPECE e
                      ON e.id_espece = CASE
                            WHEN c.id_espece1 = :id_espece THEN c.id_espece2
                            ELSE c.id_espece1
                         END
                    WHERE c.id_espece1 = :id_espece
                       OR c.id_espece2 = :id_espece
                    ORDER BY e.nom_usuel";

$stid_compatibles = oci_parse($conn, $sql_compatibles);
$id_espece = $animal['ID_ESPECE'];
oci_bind_by_name($stid_compatibles, ":id_espece", $id_espece);
oci_execute($stid_compatibles);

while ($row = oci_fetch_assoc($stid_compatibles)) {
    $compatibles[] = $row;
}
oci_free_statement($stid_compatibles);

/* =========================
   AUTRES ANIMAUX DU MEME ENCLOS
========================= */
$cohabitants = [];
$sql_cohabitants = "SELECT
                        a.id_animal,
                        a.nom,
                        es.nom_usuel
                    FROM ANIMAL a
                    JOIN ESPECE es ON a.id_espece = es.id_espece
                    WHERE a.id_enclos = :id_enclos
                      AND a.id_animal <> :id_animal
                    ORDER BY a.nom";

$stid_cohabitants = oci_parse($conn, $sql_cohabitants);
$id_enclos = $animal['ID_ENCLOS'];
oci_bind_by_name($stid_cohabitants, ":id_enclos", $id_enclos);
oci_bind_by_name($stid_cohabitants, ":id_animal", $id_animal);
oci_execute($stid_cohabitants);

while ($row = oci_fetch_assoc($stid_cohabitants)) {
    $cohabitants[] = $row;
}
oci_free_statement($stid_cohabitants);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail animal - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="animaux.php">Retour aux animaux</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">

    <div class="page-header">
        <h1><?= htmlspecialchars($animal['NOM']) ?></h1>
        <div>
            <?php if (in_array($role, ['chef_soigneur', 'directeur'], true) && file_exists(__DIR__ . '/modifier_animal.php')): ?>
                <a href="modifier_animal.php?id=<?= $animal['ID_ANIMAL'] ?>" class="btn btn-primary">Modifier</a>
            <?php endif; ?>

            <?php if (in_array($role, ['chef_soigneur', 'directeur'], true) && file_exists(__DIR__ . '/gestion_affectations.php')): ?>
                <a href="gestion_affectations.php?id=<?= $animal['ID_ANIMAL'] ?>" class="btn btn-blue">Affectations</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Informations générales
        </h2>

        <div class="table-responsive">
            <table>
                <tr>
                    <th style="width:240px;">Nom</th>
                    <td><?= htmlspecialchars($animal['NOM']) ?></td>
                </tr>
                <tr>
                    <th>Espèce</th>
                    <td>
                        <?= htmlspecialchars($animal['NOM_USUEL']) ?>
                        (<?= htmlspecialchars($animal['NOM_LATIN']) ?>)
                    </td>
                </tr>
                <tr>
                    <th>Espèce menacée</th>
                    <td><?= ((int)$animal['MENACEE'] === 1) ? 'Oui' : 'Non' ?></td>
                </tr>
                <tr>
                    <th>Date de naissance</th>
                    <td><?= htmlspecialchars($animal['DATE_NAISSANCE'] ?? '—') ?></td>
                </tr>
                <tr>
                    <th>Poids</th>
                    <td><?= htmlspecialchars($animal['POIDS']) ?> kg</td>
                </tr>
                <tr>
                    <th>Régime alimentaire</th>
                    <td><?= htmlspecialchars($animal['REGIME_ALIMENTAIRE']) ?></td>
                </tr>
                <tr>
                    <th>Zone</th>
                    <td><?= htmlspecialchars($animal['NOM_ZONE']) ?></td>
                </tr>
                <tr>
                    <th>Enclos</th>
                    <td>
                        #<?= htmlspecialchars($animal['ID_ENCLOS']) ?>
                        — <?= htmlspecialchars($animal['PARTICULARITE'] ?? 'Sans particularité') ?>
                    </td>
                </tr>
                <tr>
                    <th>Coordonnées enclos</th>
                    <td>
                        Latitude : <?= htmlspecialchars($animal['LATITUDE']) ?> /
                        Longitude : <?= htmlspecialchars($animal['LONGITUDE']) ?>
                    </td>
                </tr>
                <tr>
                    <th>Surface enclos</th>
                    <td><?= htmlspecialchars($animal['SURFACE']) ?> m²</td>
                </tr>
                <tr>
                    <th>Soigneur attitré</th>
                    <td>
                        <?= htmlspecialchars($animal['PRENOM_SOIGNEUR'] . ' ' . $animal['NOM_SOIGNEUR']) ?>
                    </td>
                </tr>
                <tr>
                    <th>Soigneur remplaçant</th>
                    <td>
                        <?php if (!empty($animal['ID_REMPLACANT_PERSONNE'])): ?>
                            <?= htmlspecialchars($animal['PRENOM_REMPLACANT'] . ' ' . $animal['NOM_REMPLACANT']) ?>
                        <?php else: ?>
                            Aucun remplaçant défini
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-top:20px;">
            <?php if (in_array($role, ['soigneur', 'chef_soigneur'], true) && file_exists(__DIR__ . '/nourrir_animaux.php')): ?>
                <a href="nourrir_animaux.php?id=<?= $animal['ID_ANIMAL'] ?>" class="btn btn-orange">Nourrir</a>
            <?php endif; ?>

            <?php if (in_array($role, ['soigneur', 'chef_soigneur', 'veterinaire'], true) && file_exists(__DIR__ . '/ajouter_soin.php')): ?>
                <a href="ajouter_soin.php?id=<?= $animal['ID_ANIMAL'] ?>" class="btn btn-blue">Ajouter un soin</a>
            <?php endif; ?>

            <?php if (in_array($role, ['chef_soigneur', 'directeur'], true) && file_exists(__DIR__ . '/gestion_remplacements.php')): ?>
                <a href="gestion_remplacements.php?id=<?= $animal['ID_ANIMAL'] ?>" class="btn btn-primary">Gérer remplaçant</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Parents
        </h2>

        <?php if (empty($parents)): ?>
            <p>Aucun parent enregistré.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Espèce</th>
                            <th>Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($parents as $parent): ?>
                        <tr>
                            <td><?= htmlspecialchars($parent['NOM']) ?></td>
                            <td><?= htmlspecialchars($parent['NOM_USUEL']) ?></td>
                            <td>
                                <a href="animal_detail.php?id=<?= $parent['ID_ANIMAL'] ?>">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (can_access('gestion_parents') && file_exists(__DIR__ . '/gestion_parents.php')): ?>
            <div style="margin-top:20px;">
                <a href="gestion_parents.php?id=<?= $animal['ID_ANIMAL'] ?>" class="btn btn-primary">Gérer les parents</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Enfants
        </h2>

        <?php if (empty($enfants)): ?>
            <p>Aucun enfant enregistré.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Espèce</th>
                            <th>Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($enfants as $enfant): ?>
                        <tr>
                            <td><?= htmlspecialchars($enfant['NOM']) ?></td>
                            <td><?= htmlspecialchars($enfant['NOM_USUEL']) ?></td>
                            <td>
                                <a href="animal_detail.php?id=<?= $enfant['ID_ANIMAL'] ?>">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Espèces compatibles
        </h2>

        <?php if (empty($compatibles)): ?>
            <p>Aucune compatibilité enregistrée.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Espèce</th>
                            <th>Nom latin</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($compatibles as $esp): ?>
                        <tr>
                            <td><?= htmlspecialchars($esp['NOM_USUEL']) ?></td>
                            <td><?= htmlspecialchars($esp['NOM_LATIN']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (can_access('gestion_cohabitation') && file_exists(__DIR__ . '/gestion_cohabitation.php')): ?>
            <div style="margin-top:20px;">
                <a href="gestion_cohabitation.php?id_espece=<?= $animal['ID_ESPECE'] ?>" class="btn btn-blue">Gérer la cohabitation</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Autres animaux du même enclos
        </h2>

        <?php if (empty($cohabitants)): ?>
            <p>Aucun autre animal dans cet enclos.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Espèce</th>
                            <th>Détail</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cohabitants as $cohab): ?>
                        <tr>
                            <td><?= htmlspecialchars($cohab['NOM']) ?></td>
                            <td><?= htmlspecialchars($cohab['NOM_USUEL']) ?></td>
                            <td>
                                <a href="animal_detail.php?id=<?= $cohab['ID_ANIMAL'] ?>">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>