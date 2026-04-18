<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('gestion_affectations');

$conn = getDatabaseConnection();

$id_animal = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_animal <= 0) {
    die("<div class='container'><div class='erreur'>Animal non spécifié.</div></div>");
}

$message = "";
$message_class = "";

/* =========================
   RECUP ANIMAL
========================= */
$sql = "SELECT nom, id_enclos, id_soigneur_attitre
        FROM ANIMAL
        WHERE id_animal = :id";

$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id", $id_animal);
oci_execute($stid);

$animal = oci_fetch_assoc($stid);
oci_free_statement($stid);

if (!$animal) {
    die("<div class='container'><div class='erreur'>Animal introuvable.</div></div>");
}

/* =========================
   MODIFICATION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_enclos = (int)$_POST['id_enclos'];
    $id_soigneur = (int)$_POST['id_soigneur'];

    $sql_update = "UPDATE ANIMAL
                   SET id_enclos = :enclos,
                       id_soigneur_attitre = :soigneur
                   WHERE id_animal = :id";

    $stid = oci_parse($conn, $sql_update);

    oci_bind_by_name($stid, ":enclos", $id_enclos);
    oci_bind_by_name($stid, ":soigneur", $id_soigneur);
    oci_bind_by_name($stid, ":id", $id_animal);

    $ok = @oci_execute($stid, OCI_NO_AUTO_COMMIT);

    if ($ok) {
        oci_commit($conn);
        $message = "Affectations mises à jour.";
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
   LISTE ENCLOS
========================= */
$enclos = [];

$sql = "SELECT id_enclos, particularite
        FROM ENCLOS
        ORDER BY id_enclos";

$stid = oci_parse($conn, $sql);
oci_execute($stid);

while ($row = oci_fetch_assoc($stid)) {
    $enclos[] = $row;
}
oci_free_statement($stid);

/* =========================
   LISTE SOIGNEURS
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
    <title>Affectations</title>
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
        <h1>Gestion des affectations</h1>
    </div>

    <?php if ($message): ?>
        <div class="<?= $message_class ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Animal : <?= htmlspecialchars($animal['NOM']) ?></h2>

        <form method="post">

            <label>Enclos :</label>
            <select name="id_enclos" required>
                <?php foreach ($enclos as $e): ?>
                    <option value="<?= $e['ID_ENCLOS'] ?>"
                        <?= ($animal['ID_ENCLOS'] == $e['ID_ENCLOS']) ? 'selected' : '' ?>>
                        #<?= $e['ID_ENCLOS'] ?> - <?= htmlspecialchars($e['PARTICULARITE']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Soigneur :</label>
            <select name="id_soigneur" required>
                <?php foreach ($soigneurs as $s): ?>
                    <option value="<?= $s['ID_PERSONNE'] ?>"
                        <?= ($animal['ID_SOIGNEUR_ATTITRE'] == $s['ID_PERSONNE']) ? 'selected' : '' ?>>
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