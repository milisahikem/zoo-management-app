<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('ajouter_ca');

$conn = getDatabaseConnection();

$message = "";
$message_class = "";

$id_boutique_selected = $_POST['id_boutique'] ?? '';
$date_ca_value = $_POST['date_ca'] ?? '';
$montant_value = $_POST['montant'] ?? '';

$role = current_role();
$id_personne = current_user_id();

/* =========================
   LISTE DES BOUTIQUES
========================= */
$boutiques = [];

if ($role === 'responsable_boutique') {
    $sql_b = "SELECT id_boutique, nom_boutique
              FROM BOUTIQUE
              WHERE id_responsable = :id_personne
              ORDER BY nom_boutique";

    $stid_b = oci_parse($conn, $sql_b);
    oci_bind_by_name($stid_b, ":id_personne", $id_personne);
} else {
    $sql_b = "SELECT id_boutique, nom_boutique
              FROM BOUTIQUE
              ORDER BY nom_boutique";

    $stid_b = oci_parse($conn, $sql_b);
}

$ok_b = @oci_execute($stid_b);

if (!$ok_b) {
    $e = oci_error($stid_b);
    die("Erreur chargement boutiques : " . htmlspecialchars($e['message']));
}

while ($row = oci_fetch_assoc($stid_b)) {
    $boutiques[] = $row;
}
oci_free_statement($stid_b);

/* =========================
   AJOUT DU CA
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_boutique = isset($_POST['id_boutique']) ? (int)$_POST['id_boutique'] : 0;
    $date_ca = trim($_POST['date_ca'] ?? '');
    $montant = isset($_POST['montant']) ? (float)$_POST['montant'] : -1;

    if ($id_boutique <= 0) {
        $message = "Veuillez sélectionner une boutique.";
        $message_class = "erreur";
    } elseif ($date_ca === '') {
        $message = "Veuillez saisir une date.";
        $message_class = "erreur";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_ca)) {
        $message = "La date doit être au format YYYY-MM-DD.";
        $message_class = "erreur";
    } elseif ($montant < 0) {
        $message = "Le montant doit être positif.";
        $message_class = "erreur";
    } else {
        /* vérification doublon clé primaire */
        $sql_check = "SELECT COUNT(*) AS NB
                      FROM GENERE_CA
                      WHERE id_boutique = :id_boutique
                        AND date_ca = TO_DATE(:date_ca, 'YYYY-MM-DD')";

        $stid_check = oci_parse($conn, $sql_check);
        oci_bind_by_name($stid_check, ":id_boutique", $id_boutique);
        oci_bind_by_name($stid_check, ":date_ca", $date_ca);
        oci_execute($stid_check);
        $check = oci_fetch_assoc($stid_check);
        oci_free_statement($stid_check);

        if ($check && (int)$check['NB'] > 0) {
            $message = "Un chiffre d'affaires existe déjà pour cette boutique à cette date.";
            $message_class = "erreur";
        } else {
            $sql_insert = "INSERT INTO GENERE_CA
                           (id_boutique, date_ca, montantCA)
                           VALUES (
                               :id_boutique,
                               TO_DATE(:date_ca, 'YYYY-MM-DD'),
                               :montant_ca
                           )";

            $stid_insert = oci_parse($conn, $sql_insert);
            oci_bind_by_name($stid_insert, ":id_boutique", $id_boutique);
            oci_bind_by_name($stid_insert, ":date_ca", $date_ca);
            oci_bind_by_name($stid_insert, ":montant_ca", $montant);

            $ok_insert = @oci_execute($stid_insert, OCI_NO_AUTO_COMMIT);

            if ($ok_insert) {
                oci_commit($conn);
                $message = "Chiffre d'affaires enregistré avec succès.";
                $message_class = "success";

                $id_boutique_selected = '';
                $date_ca_value = '';
                $montant_value = '';
            } else {
                $e = oci_error($stid_insert);
                oci_rollback($conn);
                $message = "Erreur lors de l'enregistrement : " . htmlspecialchars($e['message']);
                $message_class = "erreur";
            }

            oci_free_statement($stid_insert);
        }
    }
}

/* =========================
   HISTORIQUE DES CA
========================= */
$historique = [];

if ($role === 'responsable_boutique') {
    $sql_h = "SELECT b.nom_boutique,
                     TO_CHAR(g.date_ca, 'DD/MM/YYYY') AS date_ca,
                     g.montantCA
              FROM GENERE_CA g
              JOIN BOUTIQUE b ON g.id_boutique = b.id_boutique
              WHERE b.id_responsable = :id_personne
              ORDER BY g.date_ca DESC";

    $stid_h = oci_parse($conn, $sql_h);
    oci_bind_by_name($stid_h, ":id_personne", $id_personne);
} else {
    $sql_h = "SELECT b.nom_boutique,
                     TO_CHAR(g.date_ca, 'DD/MM/YYYY') AS date_ca,
                     g.montantCA
              FROM GENERE_CA g
              JOIN BOUTIQUE b ON g.id_boutique = b.id_boutique
              ORDER BY g.date_ca DESC";

    $stid_h = oci_parse($conn, $sql_h);
}

$ok_h = @oci_execute($stid_h);

if ($ok_h) {
    while ($row = oci_fetch_assoc($stid_h)) {
        $historique[] = $row;
    }
}
oci_free_statement($stid_h);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisir un CA - Zoo'land</title>
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
        <h1>Saisir un chiffre d'affaires</h1>
    </div>

    <?php if ($message !== ""): ?>
        <div class="<?= $message_class ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="post">

            <label for="id_boutique">Boutique *</label>
            <select name="id_boutique" id="id_boutique" required>
                <option value="">-- Choisir une boutique --</option>
                <?php foreach ($boutiques as $b): ?>
                    <option value="<?= $b['ID_BOUTIQUE'] ?>" <?= ($id_boutique_selected == $b['ID_BOUTIQUE']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['NOM_BOUTIQUE']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="date_ca">Date *</label>
            <input
                type="text"
                name="date_ca"
                id="date_ca"
                required
                placeholder="YYYY-MM-DD"
                value="<?= htmlspecialchars($date_ca_value) ?>"
            >

            <label for="montant">Montant (€) *</label>
            <input
                type="number"
                name="montant"
                id="montant"
                step="0.01"
                min="0"
                required
                value="<?= htmlspecialchars($montant_value) ?>"
            >

            <button type="submit" class="btn btn-primary">Enregistrer</button>

        </form>
    </div>

    <div class="card">
        <h2>Historique</h2>

        <table>
            <thead>
                <tr>
                    <th>Boutique</th>
                    <th>Date</th>
                    <th>Montant (€)</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($historique)): ?>
                <tr>
                    <td colspan="3" style="text-align:center;">Aucun CA</td>
                </tr>
            <?php else: ?>
                <?php foreach ($historique as $h): ?>
                    <tr>
                        <td><?= htmlspecialchars($h['NOM_BOUTIQUE']) ?></td>
                        <td><?= htmlspecialchars($h['DATE_CA']) ?></td>
                        <td><?= htmlspecialchars($h['MONTANTCA']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

</body>
</html>