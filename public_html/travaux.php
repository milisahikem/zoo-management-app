<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('travaux');

$conn = getDatabaseConnection();

$message = "";
$message_class = "";

/* =========================
   SUPPRESSION
========================= */
if (isset($_GET['supprimer'])) {
    $id_supprimer = (int)$_GET['supprimer'];

    if ($id_supprimer > 0) {
        $sql_delete = "DELETE FROM REPARATION
                       WHERE id_reparation = :id_reparation";

        $stid_delete = oci_parse($conn, $sql_delete);
        oci_bind_by_name($stid_delete, ":id_reparation", $id_supprimer);

        $ok = @oci_execute($stid_delete, OCI_NO_AUTO_COMMIT);

        if ($ok) {
            oci_commit($conn);
            $message = "Réparation supprimée avec succès.";
            $message_class = "success";
        } else {
            $e = oci_error($stid_delete);
            oci_rollback($conn);
            $message = "Erreur lors de la suppression : " . htmlspecialchars($e['message']);
            $message_class = "erreur";
        }

        oci_free_statement($stid_delete);
    }
}

/* =========================
   LISTE DES REPARATIONS
========================= */
$reparations = [];

$sql = "SELECT
            r.id_reparation,
            r.nature,
            TO_CHAR(r.date_reparation, 'DD/MM/YYYY') AS date_reparation,
            e.id_enclos,
            e.particularite,
            z.nom_zone,

            p.prenom AS prenom_personnel,
            p.nom AS nom_personnel,

            pr.nom AS nom_prestataire,
            pr.nature AS nature_prestataire

        FROM REPARATION r
        JOIN ENCLOS e ON r.id_enclos = e.id_enclos
        JOIN ZONE z ON e.id_zone = z.id_zone
        LEFT JOIN PERSONNEL p ON r.id_personne = p.id_personne
        LEFT JOIN PRESTATAIRE pr ON r.id_prestataire = pr.id_prestataire
        ORDER BY r.date_reparation DESC, r.id_reparation DESC";

$stid = oci_parse($conn, $sql);
$ok = @oci_execute($stid);

if (!$ok) {
    $e = oci_error($stid);
    die("Erreur execute : " . htmlspecialchars($e['message']));
}

while ($row = oci_fetch_assoc($stid)) {
    $reparations[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Travaux et réparations - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <?php if (file_exists(__DIR__ . '/ajout_reparation.php')): ?>
            <a href="ajout_reparation.php">Ajouter une réparation</a>
        <?php endif; ?>
        <a href="accueil.php">Retour</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Travaux et réparations</h1>
    </div>

    <?php if ($message !== ""): ?>
        <div class="<?= $message_class ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nature</th>
                        <th>Date</th>
                        <th>Enclos</th>
                        <th>Zone</th>
                        <th>Intervenant</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (empty($reparations)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:#999; padding:20px;">
                            Aucune réparation enregistrée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reparations as $rep): ?>
                        <tr>
                            <td><?= htmlspecialchars($rep['ID_REPARATION']) ?></td>
                            <td><?= htmlspecialchars($rep['NATURE']) ?></td>
                            <td><?= htmlspecialchars($rep['DATE_REPARATION']) ?></td>
                            <td>
                                #<?= htmlspecialchars($rep['ID_ENCLOS']) ?>
                                <?php if (!empty($rep['PARTICULARITE'])): ?>
                                    — <?= htmlspecialchars($rep['PARTICULARITE']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($rep['NOM_ZONE']) ?></td>
                            <td>
                                <?php if (!empty($rep['NOM_PERSONNEL'])): ?>
                                    <?= htmlspecialchars($rep['PRENOM_PERSONNEL'] . ' ' . $rep['NOM_PERSONNEL']) ?> (interne)
                                <?php else: ?>
                                    <?= htmlspecialchars($rep['NOM_PRESTATAIRE'] . ' - ' . $rep['NATURE_PRESTATAIRE']) ?> (prestataire)
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="travaux.php?supprimer=<?= $rep['ID_REPARATION'] ?>"
                                   class="btn btn-red"
                                   onclick="return confirm('Supprimer cette réparation ?');">
                                    Supprimer
                                </a>
                            </td>
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