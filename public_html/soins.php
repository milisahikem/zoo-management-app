<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_permission('soins');

$conn = getDatabaseConnection();
$role = current_role();
$id = current_user_id();

if ($role === 'soigneur') {
    $sql = "SELECT s.id_soin, s.type_soin, s.dosejournaliereSoin, TO_CHAR(s.date_soin, 'DD/MM/YYYY') AS date_soin,
                   a.nom AS nom_animal, p.prenom || ' ' || p.nom AS intervenant
            FROM SOINS s
            JOIN ANIMAL a ON a.id_animal = s.id_animal
            JOIN PERSONNEL p ON p.id_personne = s.id_personne
            WHERE s.id_personne = :id OR a.id_soigneur_attitre = :id
            ORDER BY s.date_soin DESC";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
} elseif ($role === 'chef_soigneur') {
    $sql = "SELECT s.id_soin, s.type_soin, s.dosejournaliereSoin, TO_CHAR(s.date_soin, 'DD/MM/YYYY') AS date_soin,
                   a.nom AS nom_animal, p.prenom || ' ' || p.nom AS intervenant
            FROM SOINS s
            JOIN ANIMAL a ON a.id_animal = s.id_animal
            JOIN PERSONNEL p ON p.id_personne = s.id_personne
            JOIN ENCLOS en ON en.id_enclos = a.id_enclos
            JOIN ZONE z ON z.id_zone = en.id_zone
            WHERE z.id_personnel_chef = :id
            ORDER BY s.date_soin DESC";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id", $id);
} else {
    $sql = "SELECT s.id_soin, s.type_soin, s.dosejournaliereSoin, TO_CHAR(s.date_soin, 'DD/MM/YYYY') AS date_soin,
                   a.nom AS nom_animal, p.prenom || ' ' || p.nom AS intervenant
            FROM SOINS s
            JOIN ANIMAL a ON a.id_animal = s.id_animal
            JOIN PERSONNEL p ON p.id_personne = s.id_personne
            ORDER BY s.date_soin DESC";
    $stid = oci_parse($conn, $sql);
}
oci_execute($stid);
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Soins - Zoo'land Admin</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="admin-nav"><a href="accueil.php" class="logo">🌿 Zoo'land Admin</a><div class="nav-actions"><a href="accueil.php">Retour</a><a href="logout.php" style="color:#ffcccc;">Déconnexion</a></div></nav>
<div class="container">
    <div class="page-header"><h1>Soins des animaux</h1><?php if (can_access('ajouter_soin')): ?><a href="ajouter_soin.php" class="btn btn-primary">+ Ajouter un soin</a><?php endif; ?></div>
    <div class="card"><div class="table-responsive"><table>
        <tr><th>Animal</th><th>Type</th><th>Dose</th><th>Date</th><th>Intervenant</th><th>Action</th></tr>
        <?php $nb = 0; while ($row = oci_fetch_assoc($stid)): $nb++; ?>
            <tr>
                <td><?= htmlspecialchars($row["NOM_ANIMAL"]) ?></td>
                <td><?= htmlspecialchars($row["TYPE_SOIN"]) ?></td>
                <td><?= htmlspecialchars($row["DOSEJOURNALIERESOIN"]) ?></td>
                <td><?= htmlspecialchars($row["DATE_SOIN"]) ?></td>
                <td><?= htmlspecialchars($row["INTERVENANT"]) ?></td>
                <td>
                    <?php if (can_access('modifier_soin')): ?><a class="btn btn-blue" href="modifier_soin.php?id_soin=<?= urlencode($row['ID_SOIN']) ?>">Modifier</a><?php endif; ?>
                    <?php if (can_access('supprimer_soin')): ?><a class="btn btn-red" href="supprimer_soin.php?id_soin=<?= urlencode($row['ID_SOIN']) ?>" onclick="return confirm('Supprimer ce soin ?');">Supprimer</a><?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php if ($nb === 0): ?><tr><td colspan="6" style="text-align:center;color:#999;padding:20px;">Aucun soin trouvé.</td></tr><?php endif; ?>
    </table></div></div>
</div>
</body>
</html>
<?php oci_free_statement($stid); oci_close($conn); ?>