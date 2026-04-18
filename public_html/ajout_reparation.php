<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('ajouter_reparation');

$conn = getDatabaseConnection();

$message = "";
$message_class = "";

/* =========================
   LISTES
========================= */
$enclos = [];
$techniciens = [];
$prestataires = [];

/* ENCLOS */
$sql_enclos = "SELECT e.id_enclos,
                      e.particularite,
                      z.nom_zone
               FROM ENCLOS e
               JOIN ZONE z ON e.id_zone = z.id_zone
               ORDER BY e.id_enclos";

$stid_enclos = oci_parse($conn, $sql_enclos);
oci_execute($stid_enclos);

while ($row = oci_fetch_assoc($stid_enclos)) {
    $enclos[] = $row;
}
oci_free_statement($stid_enclos);

/* TECHNICIENS */
$sql_techniciens = "SELECT id_personne, prenom, nom
                    FROM PERSONNEL
                    WHERE type_poste = 'technique'
                    ORDER BY nom, prenom";

$stid_techniciens = oci_parse($conn, $sql_techniciens);
oci_execute($stid_techniciens);

while ($row = oci_fetch_assoc($stid_techniciens)) {
    $techniciens[] = $row;
}
oci_free_statement($stid_techniciens);

/* PRESTATAIRES */
$sql_prestataires = "SELECT id_prestataire, nom, nature
                     FROM PRESTATAIRE
                     ORDER BY nom";

$stid_prestataires = oci_parse($conn, $sql_prestataires);
oci_execute($stid_prestataires);

while ($row = oci_fetch_assoc($stid_prestataires)) {
    $prestataires[] = $row;
}
oci_free_statement($stid_prestataires);

/* =========================
   AJOUT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nature = trim($_POST['nature'] ?? '');
    $date_reparation = $_POST['date_reparation'] ?? '';
    $id_enclos = isset($_POST['id_enclos']) ? (int)$_POST['id_enclos'] : 0;

    $id_personne = (isset($_POST['id_personne']) && $_POST['id_personne'] !== '') ? (int)$_POST['id_personne'] : null;
    $id_prestataire = (isset($_POST['id_prestataire']) && $_POST['id_prestataire'] !== '') ? (int)$_POST['id_prestataire'] : null;

    if (!in_array($nature, ['simple', 'complexe'], true) || $date_reparation === '' || $id_enclos <= 0) {
        $message = "Veuillez remplir correctement tous les champs.";
        $message_class = "erreur";
    } elseif ($nature === 'simple' && ($id_personne === null || $id_prestataire !== null)) {
        $message = "Une réparation simple doit être attribuée à un technicien interne.";
        $message_class = "erreur";
    } elseif ($nature === 'complexe' && ($id_prestataire === null || $id_personne !== null)) {
        $message = "Une réparation complexe doit être attribuée à un prestataire.";
        $message_class = "erreur";
    } else {
        $sql_new_id = "SELECT NVL(MAX(id_reparation), 1300) + 1 AS NEW_ID
                       FROM REPARATION";

        $stid_new_id = oci_parse($conn, $sql_new_id);
        oci_execute($stid_new_id);
        $row_new = oci_fetch_assoc($stid_new_id);
        oci_free_statement($stid_new_id);

        $id_reparation = (int)$row_new['NEW_ID'];

        $sql_insert = "INSERT INTO REPARATION
                       (id_reparation, nature, date_reparation, id_enclos, id_personne, id_prestataire)
                       VALUES (
                           :id_reparation,
                           :nature,
                           TO_DATE(:date_reparation,'YYYY-MM-DD'),
                           :id_enclos,
                           :id_personne,
                           :id_prestataire
                       )";

        $stid_insert = oci_parse($conn, $sql_insert);
        oci_bind_by_name($stid_insert, ":id_reparation", $id_reparation);
        oci_bind_by_name($stid_insert, ":nature", $nature);
        oci_bind_by_name($stid_insert, ":date_reparation", $date_reparation);
        oci_bind_by_name($stid_insert, ":id_enclos", $id_enclos);
        oci_bind_by_name($stid_insert, ":id_personne", $id_personne);
        oci_bind_by_name($stid_insert, ":id_prestataire", $id_prestataire);

        $ok = @oci_execute($stid_insert, OCI_NO_AUTO_COMMIT);

        if ($ok) {
            oci_commit($conn);
            $message = "Réparation ajoutée avec succès.";
            $message_class = "success";
        } else {
            $e = oci_error($stid_insert);
            oci_rollback($conn);
            $message = "Erreur lors de l'ajout : " . htmlspecialchars($e['message']);
            $message_class = "erreur";
        }

        oci_free_statement($stid_insert);
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une réparation - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function basculerIntervenant() {
            const nature = document.getElementById('nature').value;
            const blocPersonne = document.getElementById('bloc-personne');
            const blocPrestataire = document.getElementById('bloc-prestataire');

            if (nature === 'simple') {
                blocPersonne.style.display = 'block';
                blocPrestataire.style.display = 'none';
            } else if (nature === 'complexe') {
                blocPersonne.style.display = 'none';
                blocPrestataire.style.display = 'block';
            } else {
                blocPersonne.style.display = 'none';
                blocPrestataire.style.display = 'none';
            }
        }
    </script>
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="travaux.php">Retour aux travaux</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Ajouter une réparation</h1>
    </div>

    <?php if ($message !== ""): ?>
        <div class="<?= $message_class ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post">

            <label for="nature">Nature</label>
            <select name="nature" id="nature" required onchange="basculerIntervenant()">
                <option value="">-- Choisir --</option>
                <option value="simple">simple</option>
                <option value="complexe">complexe</option>
            </select>

            <label for="date_reparation">Date de réparation</label>
            <input type="date" name="date_reparation" id="date_reparation" required>

            <label for="id_enclos">Enclos concerné</label>
            <select name="id_enclos" id="id_enclos" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($enclos as $e): ?>
                    <option value="<?= $e['ID_ENCLOS'] ?>">
                        #<?= $e['ID_ENCLOS'] ?> - <?= htmlspecialchars($e['NOM_ZONE']) ?>
                        <?php if (!empty($e['PARTICULARITE'])): ?>
                            (<?= htmlspecialchars($e['PARTICULARITE']) ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div id="bloc-personne" style="display:none;">
                <label for="id_personne">Technicien interne</label>
                <select name="id_personne" id="id_personne">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($techniciens as $t): ?>
                        <option value="<?= $t['ID_PERSONNE'] ?>">
                            <?= htmlspecialchars($t['PRENOM'] . ' ' . $t['NOM']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="bloc-prestataire" style="display:none;">
                <label for="id_prestataire">Prestataire</label>
                <select name="id_prestataire" id="id_prestataire">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($prestataires as $p): ?>
                        <option value="<?= $p['ID_PRESTATAIRE'] ?>">
                            <?= htmlspecialchars($p['NOM'] . ' - ' . $p['NATURE']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Valider</button>
        </form>
    </div>
</div>

<script>
basculerIntervenant();
</script>

</body>
</html>