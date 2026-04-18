<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('gestion_cohabitation');

$conn = getDatabaseConnection();

$id_espece = isset($_GET['id_espece']) ? (int)$_GET['id_espece'] : 0;

if ($id_espece <= 0) {
    die("<div class='container'><div class='erreur'>Espèce non spécifiée.</div></div>");
}

$message = "";
$message_class = "";

/* =========================
   VERIFIER QUE L'ESPECE EXISTE
========================= */
$sql_espece = "SELECT id_espece, nom_usuel, nom_latin
               FROM ESPECE
               WHERE id_espece = :id_espece";

$stid_espece = oci_parse($conn, $sql_espece);
oci_bind_by_name($stid_espece, ":id_espece", $id_espece);
oci_execute($stid_espece);
$espece = oci_fetch_assoc($stid_espece);
oci_free_statement($stid_espece);

if (!$espece) {
    oci_close($conn);
    die("<div class='container'><div class='erreur'>Espèce introuvable.</div></div>");
}

/* =========================
   AJOUTER UNE COMPATIBILITE
   On stocke toujours le plus petit ID en premier
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_compatibilite'])) {
    $id_autre = isset($_POST['id_autre_espece']) ? (int)$_POST['id_autre_espece'] : 0;

    if ($id_autre <= 0) {
        $message = "Veuillez sélectionner une espèce.";
        $message_class = "erreur";
    } elseif ($id_autre === $id_espece) {
        $message = "Une espèce ne peut pas cohabiter avec elle-même dans cette table.";
        $message_class = "erreur";
    } else {
        $id1 = min($id_espece, $id_autre);
        $id2 = max($id_espece, $id_autre);

        $sql_check = "SELECT COUNT(*) AS NB
                      FROM COHABITER
                      WHERE id_espece1 = :id1
                        AND id_espece2 = :id2";

        $stid_check = oci_parse($conn, $sql_check);
        oci_bind_by_name($stid_check, ":id1", $id1);
        oci_bind_by_name($stid_check, ":id2", $id2);
        oci_execute($stid_check);
        $check = oci_fetch_assoc($stid_check);
        oci_free_statement($stid_check);

        if ($check && (int)$check['NB'] > 0) {
            $message = "Cette compatibilité existe déjà.";
            $message_class = "erreur";
        } else {
            $sql_insert = "INSERT INTO COHABITER (id_espece1, id_espece2)
                           VALUES (:id1, :id2)";

            $stid_insert = oci_parse($conn, $sql_insert);
            oci_bind_by_name($stid_insert, ":id1", $id1);
            oci_bind_by_name($stid_insert, ":id2", $id2);

            $ok = @oci_execute($stid_insert, OCI_NO_AUTO_COMMIT);

            if ($ok) {
                oci_commit($conn);
                $message = "Compatibilité ajoutée avec succès.";
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
}

/* =========================
   SUPPRIMER UNE COMPATIBILITE
========================= */
if (isset($_GET['supprimer_espece'])) {
    $id_autre_suppr = (int)$_GET['supprimer_espece'];

    if ($id_autre_suppr > 0 && $id_autre_suppr !== $id_espece) {
        $id1 = min($id_espece, $id_autre_suppr);
        $id2 = max($id_espece, $id_autre_suppr);

        $sql_delete = "DELETE FROM COHABITER
                       WHERE id_espece1 = :id1
                         AND id_espece2 = :id2";

        $stid_delete = oci_parse($conn, $sql_delete);
        oci_bind_by_name($stid_delete, ":id1", $id1);
        oci_bind_by_name($stid_delete, ":id2", $id2);

        $ok = @oci_execute($stid_delete, OCI_NO_AUTO_COMMIT);

        if ($ok) {
            oci_commit($conn);
            $message = "Compatibilité supprimée avec succès.";
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
   ESPECES COMPATIBLES ACTUELLES
========================= */
$compatibles = [];

$sql_compatibles = "SELECT
                        e.id_espece,
                        e.nom_usuel,
                        e.nom_latin
                    FROM COHABITER c
                    JOIN ESPECE e
                      ON e.id_espece = CASE
                            WHEN c.id_espece1 = :id_espece THEN c.id_espece2
                            ELSE c.id_espece1
                         END
                    WHERE c.id_espece1 = :id_espece
                       OR c.id_espece2 = :id_espece
                    ORDER BY e.nom_usuel";

$stid_compatibles = oci_parse($conn, $sql_compatibles);
oci_bind_by_name($stid_compatibles, ":id_espece", $id_espece);
oci_execute($stid_compatibles);

while ($row = oci_fetch_assoc($stid_compatibles)) {
    $compatibles[] = $row;
}
oci_free_statement($stid_compatibles);

/* =========================
   CANDIDATS POSSIBLES
   (toutes les autres espèces)
========================= */
$candidats = [];

$sql_candidats = "SELECT id_espece, nom_usuel, nom_latin
                  FROM ESPECE
                  WHERE id_espece <> :id_espece
                  ORDER BY nom_usuel";

$stid_candidats = oci_parse($conn, $sql_candidats);
oci_bind_by_name($stid_candidats, ":id_espece", $id_espece);
oci_execute($stid_candidats);

while ($row = oci_fetch_assoc($stid_candidats)) {
    $candidats[] = $row;
}
oci_free_statement($stid_candidats);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion de la cohabitation - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">

    <div class="page-header">
        <h1>Gérer la cohabitation</h1>
        <p style="color:#666;">
            Espèce : <strong><?= htmlspecialchars($espece['NOM_USUEL']) ?></strong>
            (<?= htmlspecialchars($espece['NOM_LATIN']) ?>)
        </p>
    </div>

    <?php if ($message !== ""): ?>
        <div class="<?= $message_class ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Espèces compatibles
        </h2>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom usuel</th>
                        <th>Nom latin</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($compatibles)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; color:#999; padding:20px;">
                            Aucune compatibilité enregistrée.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($compatibles as $comp): ?>
                        <tr>
                            <td><?= htmlspecialchars($comp['ID_ESPECE']) ?></td>
                            <td><?= htmlspecialchars($comp['NOM_USUEL']) ?></td>
                            <td><?= htmlspecialchars($comp['NOM_LATIN']) ?></td>
                            <td>
                                <a href="gestion_cohabitation.php?id_espece=<?= $id_espece ?>&supprimer_espece=<?= $comp['ID_ESPECE'] ?>"
                                   class="btn btn-red"
                                   onclick="return confirm('Supprimer cette compatibilité ?');">
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

    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Ajouter une compatibilité
        </h2>

        <form method="post">
            <label for="id_autre_espece">Choisir une espèce :</label>
            <select name="id_autre_espece" id="id_autre_espece" required>
                <option value="">-- Sélectionner une espèce --</option>
                <?php foreach ($candidats as $cand): ?>
                    <option value="<?= $cand['ID_ESPECE'] ?>">
                        #<?= $cand['ID_ESPECE'] ?> - <?= htmlspecialchars($cand['NOM_USUEL']) ?> (<?= htmlspecialchars($cand['NOM_LATIN']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="ajouter_compatibilite" class="btn btn-primary">
                Ajouter la compatibilité
            </button>
        </form>
    </div>

</div>

</body>
</html>