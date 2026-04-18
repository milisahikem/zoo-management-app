<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('parrainages');

$conn = getDatabaseConnection();

$parrainages = [];

$sql = "SELECT
            v.id_visiteur,
            v.nom AS nom_visiteur,
            v.prenom AS prenom_visiteur,
            a.id_animal,
            a.nom AS nom_animal,
            n.libelle AS niveau,
            TO_CHAR(p.date_parrainage, 'DD/MM/YYYY') AS date_parrainage,
            p.montant
        FROM PARRAINER p
        JOIN VISITEUR v ON p.id_visiteur = v.id_visiteur
        JOIN ANIMAL a ON p.id_animal = a.id_animal
        JOIN NIVEAU n ON p.id_niveau = n.id_niveau
        ORDER BY p.date_parrainage DESC, v.nom, v.prenom";

$stid = oci_parse($conn, $sql);
oci_execute($stid);

while ($row = oci_fetch_assoc($stid)) {
    $parrainages[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des parrainages - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="ajouter_parrainage.php">Ajouter en admin</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Gestion des parrainages</h1>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Visiteur</th>
                        <th>Animal</th>
                        <th>Niveau</th>
                        <th>Date</th>
                        <th>Montant (€)</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($parrainages)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#999; padding:20px;">
                            Aucun parrainage enregistré.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($parrainages as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['PRENOM_VISITEUR'] . ' ' . $p['NOM_VISITEUR']) ?></td>
                            <td><?= htmlspecialchars($p['NOM_ANIMAL']) ?></td>
                            <td><?= htmlspecialchars($p['NIVEAU']) ?></td>
                            <td><?= htmlspecialchars($p['DATE_PARRAINAGE']) ?></td>
                            <td><?= htmlspecialchars($p['MONTANT']) ?></td>
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