<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_permission('personnel');

$conn = getDatabaseConnection();

$sql = "SELECT id_personne, nom, prenom, type_poste
        FROM PERSONNEL
        ORDER BY nom";

$stid = oci_parse($conn, $sql);
oci_execute($stid);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Personnel - Zoo'land</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="accueil.php">Retour</a>
        <a href="logout.php">Déconnexion</a>
    </div>
</nav>

<div class="container">

    <div class="page-header">
        <h1>Personnel</h1>

        <?php if (can_access('ajouter_employe') && file_exists(__DIR__ . '/ajouter_employe.php')): ?>
            <a href="ajouter_employe.php" class="btn btn-primary">+ Ajouter un employé</a>
        <?php endif; ?>
    </div>

    <div class="card">

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Poste</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($row = oci_fetch_assoc($stid)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['ID_PERSONNE']) ?></td>
                        <td><?= htmlspecialchars($row['NOM']) ?></td>
                        <td><?= htmlspecialchars($row['PRENOM']) ?></td>
                        <td><?= htmlspecialchars($row['TYPE_POSTE']) ?></td>

                        <td>
                            <?php if (file_exists(__DIR__ . '/personnel_detail.php')): ?>
                                <a href="personnel_detail.php?id=<?= urlencode($row['ID_PERSONNE']) ?>" class="btn btn-sm">
                                    Voir
                                </a>
                            <?php endif; ?>

                            <?php if (can_access('modifier_personnel') && file_exists(__DIR__ . '/modifier_personnel.php')): ?>
                                <a href="modifier_personnel.php?id=<?= urlencode($row['ID_PERSONNE']) ?>" class="btn btn-sm">
                                    Modifier
                                </a>
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endwhile; ?>

                </tbody>
            </table>
        </div>

    </div>

</div>

</body>
</html>

<?php
oci_free_statement($stid);
oci_close($conn);
?>
