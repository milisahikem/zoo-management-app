<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('ajouter_animal');

$conn = getDatabaseConnection();

$message = "";
$messageClass = "";

/* Charger espèces */
$especes = [];
$stid_e = oci_parse($conn, "SELECT id_espece, nom_usuel FROM ESPECE ORDER BY nom_usuel");
oci_execute($stid_e);
while ($row = oci_fetch_assoc($stid_e)) {
    $especes[] = $row;
}
oci_free_statement($stid_e);

/* Charger enclos */
$enclos = [];
$stid_en = oci_parse($conn, "SELECT id_enclos, particularite FROM ENCLOS ORDER BY id_enclos");
oci_execute($stid_en);
while ($row = oci_fetch_assoc($stid_en)) {
    $enclos[] = $row;
}
oci_free_statement($stid_en);

/* Charger soigneurs */
$soigneurs = [];
$stid_s = oci_parse($conn, "SELECT id_personne, prenom, nom
                            FROM PERSONNEL
                            WHERE LOWER(type_poste) IN ('soigneur', 'chef_soigneur')
                            ORDER BY nom, prenom");
oci_execute($stid_s);
while ($row = oci_fetch_assoc($stid_s)) {
    $soigneurs[] = $row;
}
oci_free_statement($stid_s);

/* Traitement formulaire */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_animal           = trim($_POST["id_animal"] ?? "");
    $nom                 = trim($_POST["nom"] ?? "");
    $date_naissance      = trim($_POST["date_naissance"] ?? "");
    $poids               = trim($_POST["poids"] ?? "");
    $regime              = trim($_POST["regime"] ?? "");
    $id_espece           = trim($_POST["id_espece"] ?? "");
    $id_enclos           = trim($_POST["id_enclos"] ?? "");
    $id_soigneur_attitre = trim($_POST["id_soigneur_attitre"] ?? "");

    if (
        $id_animal === "" || $nom === "" || $date_naissance === "" ||
        $poids === "" || $regime === "" || $id_espece === "" ||
        $id_enclos === "" || $id_soigneur_attitre === ""
    ) {
        $message = "Veuillez remplir tous les champs obligatoires.";
        $messageClass = "erreur";
    } elseif (!ctype_digit($id_animal)) {
        $message = "ID animal invalide.";
        $messageClass = "erreur";
    } elseif (!in_array($regime, ['carnivore', 'herbivore', 'omnivore'], true)) {
        $message = "Régime invalide.";
        $messageClass = "erreur";
    } else {
        $sql = "INSERT INTO ANIMAL (
                    id_animal,
                    date_de_naissance,
                    nom,
                    poids,
                    regime_alimentaire,
                    id_espece,
                    id_enclos,
                    id_soigneur_attitre
                ) VALUES (
                    :id_animal,
                    TO_DATE(:date_naissance, 'YYYY-MM-DD'),
                    :nom,
                    :poids,
                    :regime_alimentaire,
                    :id_espece,
                    :id_enclos,
                    :id_soigneur_attitre
                )";

        $stid = oci_parse($conn, $sql);

        oci_bind_by_name($stid, ":id_animal", $id_animal);
        oci_bind_by_name($stid, ":date_naissance", $date_naissance);
        oci_bind_by_name($stid, ":nom", $nom);
        oci_bind_by_name($stid, ":poids", $poids);
        oci_bind_by_name($stid, ":regime_alimentaire", $regime);
        oci_bind_by_name($stid, ":id_espece", $id_espece);
        oci_bind_by_name($stid, ":id_enclos", $id_enclos);
        oci_bind_by_name($stid, ":id_soigneur_attitre", $id_soigneur_attitre);

        $ok = oci_execute($stid, OCI_NO_AUTO_COMMIT);

        if ($ok) {
            oci_commit($conn);
            header("Location: animaux.php?success=animal_ajoute");
            exit();
        } else {
            $e = oci_error($stid);
            $message = "Erreur : " . ($e['message'] ?? 'Erreur inconnue');
            $messageClass = "erreur";
            oci_rollback($conn);
        }

        oci_free_statement($stid);
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un animal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="animaux.php">Retour</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Ajouter un animal</h1>
    </div>

    <div class="card form-card">
        <?php if ($message !== ""): ?>
            <p class="<?= htmlspecialchars($messageClass) ?>"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="id_animal">ID animal *</label>
            <input type="number" name="id_animal" id="id_animal" required value="<?= htmlspecialchars($_POST['id_animal'] ?? '') ?>">

            <label for="nom">Nom *</label>
            <input type="text" name="nom" id="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">

            <label for="date_naissance">Date de naissance *</label>
            <input type="date" name="date_naissance" id="date_naissance" required value="<?= htmlspecialchars($_POST['date_naissance'] ?? '') ?>">

            <label for="poids">Poids *</label>
            <input type="number" step="0.01" name="poids" id="poids" required value="<?= htmlspecialchars($_POST['poids'] ?? '') ?>">

            <label for="regime">Régime alimentaire *</label>
            <select name="regime" id="regime" required>
                <option value="">-- Choisir un régime --</option>
                <option value="carnivore" <?= (($_POST['regime'] ?? '') === 'carnivore') ? 'selected' : '' ?>>carnivore</option>
                <option value="herbivore" <?= (($_POST['regime'] ?? '') === 'herbivore') ? 'selected' : '' ?>>herbivore</option>
                <option value="omnivore" <?= (($_POST['regime'] ?? '') === 'omnivore') ? 'selected' : '' ?>>omnivore</option>
            </select>

            <label for="id_espece">Espèce *</label>
            <select name="id_espece" id="id_espece" required>
                <option value="">-- Choisir une espèce --</option>
                <?php foreach ($especes as $e): ?>
                    <option value="<?= htmlspecialchars($e['ID_ESPECE']) ?>"
                        <?= (($_POST['id_espece'] ?? '') == $e['ID_ESPECE']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['ID_ESPECE'] . ' - ' . $e['NOM_USUEL']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_enclos">Enclos *</label>
            <select name="id_enclos" id="id_enclos" required>
                <option value="">-- Choisir un enclos --</option>
                <?php foreach ($enclos as $e): ?>
                    <option value="<?= htmlspecialchars($e['ID_ENCLOS']) ?>"
                        <?= (($_POST['id_enclos'] ?? '') == $e['ID_ENCLOS']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['ID_ENCLOS'] . ' - ' . ($e['PARTICULARITE'] ?? 'Sans nom')) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_soigneur_attitre">Soigneur attitré *</label>
            <select name="id_soigneur_attitre" id="id_soigneur_attitre" required>
                <option value="">-- Choisir un soigneur --</option>
                <?php foreach ($soigneurs as $s): ?>
                    <option value="<?= htmlspecialchars($s['ID_PERSONNE']) ?>"
                        <?= (($_POST['id_soigneur_attitre'] ?? '') == $s['ID_PERSONNE']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['ID_PERSONNE'] . ' - ' . $s['PRENOM'] . ' ' . $s['NOM']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary">Ajouter</button>
        </form>
    </div>
</div>

</body>
</html>
