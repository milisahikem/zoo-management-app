<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();
require_permission('gestion_parents');

$conn = getDatabaseConnection();

$id_animal = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_animal <= 0) {
    die("<div class='container'><div class='erreur'>Animal non spécifié.</div></div>");
}

$message = "";
$message_class = "";

/* =========================
   VERIFIER QUE L'ANIMAL EXISTE
========================= */
$sql_animal = "SELECT a.id_animal,
                      a.nom,
                      es.nom_usuel
               FROM ANIMAL a
               JOIN ESPECE es ON a.id_espece = es.id_espece
               WHERE a.id_animal = :id_animal";

$stid_animal = oci_parse($conn, $sql_animal);
oci_bind_by_name($stid_animal, ":id_animal", $id_animal);
oci_execute($stid_animal);
$animal = oci_fetch_assoc($stid_animal);
oci_free_statement($stid_animal);

if (!$animal) {
    oci_close($conn);
    die("<div class='container'><div class='erreur'>Animal introuvable.</div></div>");
}

/* =========================
   AJOUT D'UN PARENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_parent'])) {
    $id_parent = isset($_POST['id_parent']) ? (int)$_POST['id_parent'] : 0;

    if ($id_parent <= 0) {
        $message = "Veuillez sélectionner un parent.";
        $message_class = "erreur";
    } elseif ($id_parent === $id_animal) {
        $message = "Un animal ne peut pas être son propre parent.";
        $message_class = "erreur";
    } else {
        /* vérifier si relation déjà existante */
        $sql_check = "SELECT COUNT(*) AS NB
                      FROM EST_PARENT
                      WHERE id_animal_parent = :id_parent
                        AND id_animal_enfant = :id_enfant";

        $stid_check = oci_parse($conn, $sql_check);
        oci_bind_by_name($stid_check, ":id_parent", $id_parent);
        oci_bind_by_name($stid_check, ":id_enfant", $id_animal);
        oci_execute($stid_check);
        $check = oci_fetch_assoc($stid_check);
        oci_free_statement($stid_check);

        if ($check && (int)$check['NB'] > 0) {
            $message = "Ce parent est déjà associé à cet animal.";
            $message_class = "erreur";
        } else {
            $sql_insert = "INSERT INTO EST_PARENT (id_animal_parent, id_animal_enfant)
                           VALUES (:id_parent, :id_enfant)";

            $stid_insert = oci_parse($conn, $sql_insert);
            oci_bind_by_name($stid_insert, ":id_parent", $id_parent);
            oci_bind_by_name($stid_insert, ":id_enfant", $id_animal);

            $ok = @oci_execute($stid_insert, OCI_NO_AUTO_COMMIT);

            if ($ok) {
                oci_commit($conn);
                $message = "Parent ajouté avec succès.";
                $message_class = "success";
            } else {
                $e = oci_error($stid_insert);
                oci_rollback($conn);
                $message = "Erreur lors de l'ajout du parent : " . htmlspecialchars($e['message']);
                $message_class = "erreur";
            }

            oci_free_statement($stid_insert);
        }
    }
}

/* =========================
   SUPPRESSION D'UN PARENT
========================= */
if (isset($_GET['supprimer_parent'])) {
    $id_parent_suppr = (int)$_GET['supprimer_parent'];

    if ($id_parent_suppr > 0) {
        $sql_delete = "DELETE FROM EST_PARENT
                       WHERE id_animal_parent = :id_parent
                         AND id_animal_enfant = :id_enfant";

        $stid_delete = oci_parse($conn, $sql_delete);
        oci_bind_by_name($stid_delete, ":id_parent", $id_parent_suppr);
        oci_bind_by_name($stid_delete, ":id_enfant", $id_animal);

        $ok = @oci_execute($stid_delete, OCI_NO_AUTO_COMMIT);

        if ($ok) {
            oci_commit($conn);
            $message = "Lien parent supprimé avec succès.";
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
   LISTE DES PARENTS ACTUELS
========================= */
$parents = [];
$sql_parents = "SELECT a.id_animal,
                       a.nom,
                       es.nom_usuel
                FROM EST_PARENT ep
                JOIN ANIMAL a ON ep.id_animal_parent = a.id_animal
                JOIN ESPECE es ON a.id_espece = es.id_espece
                WHERE ep.id_animal_enfant = :id_animal
                ORDER BY a.nom";

$stid_parents = oci_parse($conn, $sql_parents);
oci_bind_by_name($stid_parents, ":id_animal", $id_animal);
oci_execute($stid_parents);

while ($row = oci_fetch_assoc($stid_parents)) {
    $parents[] = $row;
}
oci_free_statement($stid_parents);

/* =========================
   LISTE DES CANDIDATS PARENTS
   (tous les autres animaux)
========================= */
$candidats = [];
$sql_candidats = "SELECT a.id_animal,
                         a.nom,
                         es.nom_usuel
                  FROM ANIMAL a
                  JOIN ESPECE es ON a.id_espece = es.id_espece
                  WHERE a.id_animal <> :id_animal
                  ORDER BY a.nom";

$stid_candidats = oci_parse($conn, $sql_candidats);
oci_bind_by_name($stid_candidats, ":id_animal", $id_animal);
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
    <title>Gestion des parents - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="animal_detail.php?id=<?= $id_animal ?>">Retour à l'animal</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">

    <div class="page-header">
        <h1>Gérer les parents</h1>
        <p style="color:#666;">
            Animal : <strong><?= htmlspecialchars($animal['NOM']) ?></strong>
            (<?= htmlspecialchars($animal['NOM_USUEL']) ?>)
        </p>
    </div>

    <?php if ($message !== ""): ?>
        <div class="<?= $message_class ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 style="font-family:'Playfair Display', serif; color:var(--primary-color); margin-bottom:15px;">
            Parents actuels
        </h2>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Espèce</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($parents)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center; color:#999; padding:20px;">
                            Aucun parent enregistré.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($parents as $parent): ?>
                        <tr>
                            <td><?= htmlspecialchars($parent['ID_ANIMAL']) ?></td>
                            <td><?= htmlspecialchars($parent['NOM']) ?></td>
                            <td><?= htmlspecialchars($parent['NOM_USUEL']) ?></td>
                            <td>
                                <a href="gestion_parents.php?id=<?= $id_animal ?>&supprimer_parent=<?= $parent['ID_ANIMAL'] ?>"
                                   class="btn btn-red"
                                   onclick="return confirm('Supprimer ce lien parent ?');">
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
            Ajouter un parent
        </h2>

        <form method="post">
            <label for="id_parent">Choisir un parent :</label>
            <select name="id_parent" id="id_parent" required>
                <option value="">-- Sélectionner un animal --</option>
                <?php foreach ($candidats as $cand): ?>
                    <option value="<?= $cand['ID_ANIMAL'] ?>">
                        #<?= $cand['ID_ANIMAL'] ?> - <?= htmlspecialchars($cand['NOM']) ?> (<?= htmlspecialchars($cand['NOM_USUEL']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="ajouter_parent" class="btn btn-primary">
                Ajouter le parent
            </button>
        </form>
    </div>

</div>

</body>
</html>