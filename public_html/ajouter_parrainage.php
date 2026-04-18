<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('ajouter_parrainage');

$conn = getDatabaseConnection();

$message = "";
$message_class = "";

/* listes */
$visiteurs = [];
$animaux = [];
$niveaux = [];

$sql_visiteurs = "SELECT id_visiteur, nom, prenom FROM VISITEUR ORDER BY nom, prenom";
$stid = oci_parse($conn, $sql_visiteurs);
oci_execute($stid);
while ($row = oci_fetch_assoc($stid)) { $visiteurs[] = $row; }
oci_free_statement($stid);

$sql_animaux = "SELECT id_animal, nom FROM ANIMAL ORDER BY nom";
$stid = oci_parse($conn, $sql_animaux);
oci_execute($stid);
while ($row = oci_fetch_assoc($stid)) { $animaux[] = $row; }
oci_free_statement($stid);

$sql_niveaux = "SELECT id_niveau, libelle FROM NIVEAU ORDER BY id_niveau";
$stid = oci_parse($conn, $sql_niveaux);
oci_execute($stid);
while ($row = oci_fetch_assoc($stid)) { $niveaux[] = $row; }
oci_free_statement($stid);

function montant_valide_admin($libelle_niveau, $montant) {
    $niveau = strtolower(trim($libelle_niveau));

    if ($niveau === 'bronze') return $montant >= 0 && $montant < 200;
    if ($niveau === 'argent') return $montant >= 200 && $montant < 280;
    if ($niveau === 'or') return $montant >= 280;

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_visiteur = isset($_POST['id_visiteur']) ? (int)$_POST['id_visiteur'] : 0;
    $id_animal   = isset($_POST['id_animal']) ? (int)$_POST['id_animal'] : 0;
    $id_niveau   = isset($_POST['id_niveau']) ? (int)$_POST['id_niveau'] : 0;
    $montant     = isset($_POST['montant']) ? (float)$_POST['montant'] : -1;

    $sql_niveau = "SELECT libelle FROM NIVEAU WHERE id_niveau = :id_niveau";
    $stid_niv = oci_parse($conn, $sql_niveau);
    oci_bind_by_name($stid_niv, ":id_niveau", $id_niveau);
    oci_execute($stid_niv);
    $niv = oci_fetch_assoc($stid_niv);
    oci_free_statement($stid_niv);

    if (!$niv || $id_visiteur <= 0 || $id_animal <= 0 || $montant < 0) {
        $message = "Veuillez remplir correctement tous les champs.";
        $message_class = "erreur";
    } elseif (!montant_valide_admin($niv['LIBELLE'], $montant)) {
        $message = "Le montant ne correspond pas au niveau choisi.";
        $message_class = "erreur";
    } else {
        $sql_insert = "INSERT INTO PARRAINER
                       (id_visiteur, id_animal, id_niveau, date_parrainage, montant)
                       VALUES (:id_visiteur, :id_animal, :id_niveau, SYSDATE, :montant)";

        $stid_insert = oci_parse($conn, $sql_insert);
        oci_bind_by_name($stid_insert, ":id_visiteur", $id_visiteur);
        oci_bind_by_name($stid_insert, ":id_animal", $id_animal);
        oci_bind_by_name($stid_insert, ":id_niveau", $id_niveau);
        oci_bind_by_name($stid_insert, ":montant", $montant);

        $ok = @oci_execute($stid_insert, OCI_NO_AUTO_COMMIT);

        if ($ok) {
            oci_commit($conn);
            $message = "Parrainage ajouté avec succès.";
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
    <title>Ajouter un parrainage - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="parrainage.php">Retour aux parrainages</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Ajouter un parrainage</h1>
    </div>

    <?php if ($message !== ""): ?>
        <div class="<?= $message_class ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="post">
            <label for="id_visiteur">Visiteur</label>
            <select name="id_visiteur" id="id_visiteur" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($visiteurs as $v): ?>
                    <option value="<?= $v['ID_VISITEUR'] ?>">
                        <?= htmlspecialchars($v['PRENOM'] . ' ' . $v['NOM']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_animal">Animal</label>
            <select name="id_animal" id="id_animal" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($animaux as $a): ?>
                    <option value="<?= $a['ID_ANIMAL'] ?>">
                        <?= htmlspecialchars($a['NOM']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_niveau">Niveau</label>
            <select name="id_niveau" id="id_niveau" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($niveaux as $n): ?>
                    <option value="<?= $n['ID_NIVEAU'] ?>">
                        <?= htmlspecialchars($n['LIBELLE']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="montant">Montant (€)</label>
            <input type="number" step="0.01" min="0" name="montant" id="montant" required>

            <button type="submit" class="btn btn-primary">Valider</button>
        </form>
    </div>
</div>

</body>
</html>