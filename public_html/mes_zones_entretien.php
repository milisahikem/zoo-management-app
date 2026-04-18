<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();

if (current_role() !== 'entretien' && current_role() !== 'directeur') {
    die("<div class='container'><div class='erreur'>Accès refusé.</div></div>");
}

$conn = getDatabaseConnection();
$id_personne = current_user_id();

$zones = [];

if (current_role() === 'directeur') {
    $sql = "SELECT id_zone, nom_zone
            FROM ZONE
            ORDER BY nom_zone";

    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
} else {
    $sql = "SELECT z.id_zone, z.nom_zone
            FROM AFFECTER a
            JOIN ZONE z ON a.id_zone = z.id_zone
            WHERE a.id_personne = :id_personne
            ORDER BY z.nom_zone";

    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id_personne", $id_personne);
    oci_execute($stid);
}

while ($row = oci_fetch_assoc($stid)) {
    $zones[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Zones d'entretien - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="accueil.php">Retour</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Zones d'entretien</h1>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID zone</th>
                        <th>Nom de la zone</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($zones)): ?>
                    <tr>
                        <td colspan="2" style="text-align:center; color:#999; padding:20px;">
                            Aucune zone affectée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($zones as $z): ?>
                        <tr>
                            <td><?= htmlspecialchars($z['ID_ZONE']) ?></td>
                            <td><?= htmlspecialchars($z['NOM_ZONE']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>