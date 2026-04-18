<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_permission('personnel');

$conn = getDatabaseConnection();
$id   = $_GET["id"] ?? "";

if ($id === "" || !ctype_digit((string)$id)) {
    die("<div class='erreur'>ID personnel invalide.</div>");
}

// Infos de base
$stid_p = oci_parse($conn, "SELECT p.id_personne, p.nom, p.prenom, p.salaire,
                                    TO_CHAR(p.date_entreefonction,'DD/MM/YYYY') AS date_entree,
                                    h.type_poste
                             FROM PERSONNEL p
                             LEFT JOIN HISTORIQUE_EMPLOI h
                               ON p.id_personne = h.id_personne AND h.date_fin IS NULL
                             WHERE p.id_personne = :id");
oci_bind_by_name($stid_p, ":id", $id);
oci_execute($stid_p);
$pers = oci_fetch_assoc($stid_p);
oci_free_statement($stid_p);

if (!$pers) {
    oci_close($conn);
    die("<div class='erreur'>Personnel introuvable.</div>");
}

// Historique emploi
$stid_h = oci_parse($conn, "SELECT type_poste,
                                    TO_CHAR(date_debut,'DD/MM/YYYY') AS date_debut,
                                    TO_CHAR(date_fin,'DD/MM/YYYY')   AS date_fin
                             FROM HISTORIQUE_EMPLOI
                             WHERE id_personne = :id
                             ORDER BY date_debut DESC");
oci_bind_by_name($stid_h, ":id", $id);
oci_execute($stid_h);
$historique = [];
while ($r = oci_fetch_assoc($stid_h)) $historique[] = $r;
oci_free_statement($stid_h);

// Animaux attitres
$stid_a = oci_parse($conn, "SELECT a.id_animal, a.nom, e.nom_usuel AS espece, en.particularite
                             FROM ANIMAL a
                             LEFT JOIN ESPECE  e  ON a.id_espece = e.id_espece
                             LEFT JOIN ENCLOS  en ON a.id_enclos = en.id_enclos
                             WHERE a.id_soigneur_attitre = :id
                             ORDER BY a.nom");
oci_bind_by_name($stid_a, ":id", $id);
oci_execute($stid_a);
$animaux = [];
while ($r = oci_fetch_assoc($stid_a)) $animaux[] = $r;
oci_free_statement($stid_a);

// Soins réalisés (5 derniers)
$stid_s = oci_parse($conn, "SELECT TO_CHAR(s.date_soin,'DD/MM/YYYY') AS date_soin,
                                    s.type_soin, s.dosejournaliereSoin,
                                    a.nom AS nom_animal
                             FROM SOINS s
                             JOIN ANIMAL a ON s.id_animal = a.id_animal
                             WHERE s.id_personne = :id
                             ORDER BY s.date_soin DESC");
oci_bind_by_name($stid_s, ":id", $id);
oci_execute($stid_s);
$soins = [];
while ($r = oci_fetch_assoc($stid_s)) $soins[] = $r;
oci_free_statement($stid_s);

// Réparations réalisées
$stid_r = oci_parse($conn, "SELECT r.id_reparation, r.nature,
                                    TO_CHAR(r.date_reparation,'DD/MM/YYYY') AS date_rep,
                                    en.particularite, z.nom_zone
                             FROM REPARATION r
                             JOIN ENCLOS en ON r.id_enclos = en.id_enclos
                             JOIN ZONE z    ON en.id_zone  = z.id_zone
                             WHERE r.id_personne = :id
                             ORDER BY r.date_reparation DESC");
oci_bind_by_name($stid_r, ":id", $id);
oci_execute($stid_r);
$reparations = [];
while ($r = oci_fetch_assoc($stid_r)) $reparations[] = $r;
oci_free_statement($stid_r);

// Boutiques liées
$stid_b = oci_parse($conn, "SELECT b.nom_boutique, b.type_boutique
                             FROM BOUTIQUE b
                             WHERE b.id_responsable = :id
                                OR EXISTS (
                                    SELECT 1 FROM TRAVAILLER t
                                    WHERE t.id_boutique = b.id_boutique
                                      AND t.id_personne = :id
                                )
                             ORDER BY b.nom_boutique");
oci_bind_by_name($stid_b, ":id", $id);
oci_execute($stid_b);
$boutiques = [];
while ($r = oci_fetch_assoc($stid_b)) $boutiques[] = $r;
oci_free_statement($stid_b);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche personnel - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="admin-nav">
    <a href="accueil.php" class="logo">🌿 Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="personnel.php">Retour au personnel</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>👤 <?= htmlspecialchars($pers['PRENOM'] . ' ' . $pers['NOM']) ?></h1>
        <?php if (can_access('modifier_personnel')): ?>
            <a href="modifier_personnel.php?id=<?= urlencode($pers['ID_PERSONNE']) ?>" class="btn btn-orange">Modifier</a>
        <?php endif; ?>
    </div>

    <!-- Infos générales -->
    <div class="card" style="margin-bottom:20px;">
        <h2 style="font-size:1rem;margin-bottom:15px;color:var(--primary-color);">Informations générales</h2>
        <table>
            <tr><th>ID</th><td><?= htmlspecialchars($pers['ID_PERSONNE']) ?></td></tr>
            <tr><th>Rôle actuel</th><td><?= htmlspecialchars(role_label(strtolower($pers['TYPE_POSTE'] ?? ''))) ?></td></tr>
            <tr><th>Date d'entrée</th><td><?= htmlspecialchars($pers['DATE_ENTREE']) ?></td></tr>
            <tr><th>Salaire</th><td><?= number_format((float)$pers['SALAIRE'], 2, ',', ' ') ?> €</td></tr>
        </table>
    </div>

    <!-- Historique -->
    <div class="card" style="margin-bottom:20px;">
        <h2 style="font-size:1rem;margin-bottom:15px;color:var(--primary-color);">Historique d'emploi</h2>
        <table>
            <tr><th>Poste</th><th>Début</th><th>Fin</th></tr>
            <?php foreach ($historique as $h): ?>
                <tr>
                    <td><?= htmlspecialchars(role_label(strtolower($h['TYPE_POSTE']))) ?></td>
                    <td><?= htmlspecialchars($h['DATE_DEBUT']) ?></td>
                    <td><?= htmlspecialchars($h['DATE_FIN'] ?: 'En cours') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Animaux -->
    <?php if (!empty($animaux)): ?>
    <div class="card" style="margin-bottom:20px;">
        <h2 style="font-size:1rem;margin-bottom:15px;color:var(--primary-color);">Animaux attitres (<?= count($animaux) ?>)</h2>
        <table>
            <tr><th>Nom</th><th>Espèce</th><th>Enclos</th></tr>
            <?php foreach ($animaux as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['NOM']) ?></td>
                    <td><?= htmlspecialchars($a['ESPECE'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($a['PARTICULARITE'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- Soins -->
    <?php if (!empty($soins)): ?>
    <div class="card" style="margin-bottom:20px;">
        <h2 style="font-size:1rem;margin-bottom:15px;color:var(--primary-color);">Soins réalisés</h2>
        <table>
            <tr><th>Date</th><th>Animal</th><th>Type</th><th>Dose (g)</th></tr>
            <?php foreach (array_slice($soins, 0, 10) as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['DATE_SOIN']) ?></td>
                    <td><?= htmlspecialchars($s['NOM_ANIMAL']) ?></td>
                    <td><?= htmlspecialchars($s['TYPE_SOIN']) ?></td>
                    <td><?= htmlspecialchars($s['DOSEJOURNALIERESOIN']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- Réparations -->
    <?php if (!empty($reparations)): ?>
    <div class="card" style="margin-bottom:20px;">
        <h2 style="font-size:1rem;margin-bottom:15px;color:var(--primary-color);">Réparations réalisées</h2>
        <table>
            <tr><th>Date</th><th>Zone</th><th>Enclos</th><th>Nature</th></tr>
            <?php foreach ($reparations as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['DATE_REP']) ?></td>
                    <td><?= htmlspecialchars($r['NOM_ZONE']) ?></td>
                    <td><?= htmlspecialchars($r['PARTICULARITE'] ?? '—') ?></td>
                    <td><?= htmlspecialchars(ucfirst($r['NATURE'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- Boutiques -->
    <?php if (!empty($boutiques)): ?>
    <div class="card">
        <h2 style="font-size:1rem;margin-bottom:15px;color:var(--primary-color);">Boutiques associées</h2>
        <table>
            <tr><th>Boutique</th><th>Type</th></tr>
            <?php foreach ($boutiques as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['NOM_BOUTIQUE']) ?></td>
                    <td><?= htmlspecialchars($b['TYPE_BOUTIQUE']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

</div>
</body>
</html>