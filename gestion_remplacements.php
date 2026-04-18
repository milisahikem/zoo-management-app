<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('gestion_remplacements');

$conn = getDatabaseConnection();

$id_animal = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_animal <= 0) {
    die("<div class='container'><div class='erreur'>Animal non spécifié.</div></div>");
}

$message = "";
$message_class = "";

/* =========================
   RECUP ANIMAL + SOIGNEUR
========================= */
$sql = "SELECT 
            a.nom,
            p.id_personne,
            p.prenom,
            p.nom AS nom_soigneur,
            p.id_remplacant
        FROM ANIMAL a
        JOIN PERSONNEL p ON a.id_soigneur_attitre = p.id_personne
        WHERE a.id_animal = :id";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $id_animal);
oci_execute($stid);

$data = oci_fetch_assoc($stid);
oci_free_statement($stid);

if (!$data) {
    die("<div class='container'><div class='erreur'>Animal introuvable.</div></div>");
}

$id_soigneur = $data['ID_PERSONNE'];

/* =========================
   MODIFICATION REMPLACANT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_remp = isset($_POST['id_remplacant']) ? (int)$_POST['id_remplacant'] : null;

    $sql_update = "UPDATE PERSONNEL
                   SET id_remplacant = :remp
                   WHERE id_personne = :id";

    $stid = oci_parse($conn, $sql_update);
    oci_bind_by_name($stid, ":remp", $id_remp);
    oci_bind_by_name($stid, ":id", $id_soigneur);

    $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);

    if ($ok) {
        oci_commit($conn);
        $message = "Remplaçant mis à jour avec succès.";
        $message_class = "success";
    } else {
        $e = oci_error($stid);
        oci_rollback($conn);
        $message = "Erreur : " . htmlspecialchars($e['message']);
        $message_class = "erreur";
    }

    oci_free_statement($stid);
}

/* =========================
   LISTE DES SOIGNEURS
========================= */
$soigneurs = [];

$sql = "SELECT id_personne, prenom, nom
        FROM PERSONNEL
        WHERE type_poste IN ('soigneur','chef_soigneur')
        ORDER BY nom";

$stid = oci_parse($conn, $sql);
oci_execute($stid);

while ($row = oci_fetch_assoc($stid)) {
    $soigneurs[] = $row;
}
oci_free_statement($stid);

oci_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestion remplaçant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="animal_detail.php?id=<?= $id_animal ?>">Retour</a>
        <a href="logout.php">Déconnexion</a>
    </div>
</nav>

<div class="container">

    <div class="page-header">
        <h1>Gestion du remplaçant</h1>
    </div>

    <?php if ($message): ?>
        <div class="<?= $message_class ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Informations</h2>

        <p><strong>Animal :</strong> <?= htmlspecialchars($data['NOM']) ?></p>
        <p><strong>Soigneur :</strong>
            <?= htmlspecialchars($data['PRENOM'] . " " . $data['NOM_SOIGNEUR']) ?>
        </p>
    </div>

    <div class="card">
        <h2>Changer le remplaçant</h2>

        <form method="post">

            <label>Choisir un remplaçant :</label>

            <select name="id_remplacant">
                <option value="">-- Aucun remplaçant --</option>

                <?php foreach ($soigneurs as $s): ?>
                    <option value="<?= $s['ID_PERSONNE'] ?>"
                        <?= ($data['ID_REMPLACANT'] == $s['ID_PERSONNE']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['PRENOM'] . " " . $s['NOM']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="btn btn-primary">Valider</button>

        </form>
    </div>

</div>

</body>
</html>