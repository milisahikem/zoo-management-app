<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('boutiques');

$conn = getDatabaseConnection();

$role = current_role();
$id_personne = current_user_id();

$boutiques = [];
$message = "";
$message_class = "";

if ($role === 'responsable_boutique') {
    $sql = "SELECT b.id_boutique,
                   b.nom_boutique,
                   b.type_boutique,
                   b.id_responsable
            FROM BOUTIQUE b
            WHERE b.id_responsable = :id_personne
            ORDER BY b.nom_boutique";

    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id_personne", $id_personne);

} elseif ($role === 'employe_boutique') {
    $sql = "SELECT DISTINCT b.id_boutique,
                           b.nom_boutique,
                           b.type_boutique,
                           b.id_responsable
            FROM BOUTIQUE b
            JOIN TRAVAILLER t ON t.id_boutique = b.id_boutique
            WHERE t.id_personne = :id_personne
            ORDER BY b.nom_boutique";

    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id_personne", $id_personne);

} else {
    $sql = "SELECT b.id_boutique,
                   b.nom_boutique,
                   b.type_boutique,
                   b.id_responsable
            FROM BOUTIQUE b
            ORDER BY b.nom_boutique";

    $stid = oci_parse($conn, $sql);
}

$ok = @oci_execute($stid);

if (!$ok) {
    $e = oci_error($stid);
    die("Erreur execute : " . htmlspecialchars($e['message']));
}

while ($row = oci_fetch_assoc($stid)) {
    $boutiques[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Boutiques - Zoo'land Admin</title>
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
        <h1>Boutiques</h1>
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
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($boutiques)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; color:#999; padding:20px;">
                            Aucune boutique trouvée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($boutiques as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['ID_BOUTIQUE']) ?></td>
                            <td><?= htmlspecialchars($b['NOM_BOUTIQUE']) ?></td>
                            <td><?= htmlspecialchars($b['TYPE_BOUTIQUE']) ?></td>
                            <td><?= htmlspecialchars($b['ID_RESPONSABLE']) ?></td>
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