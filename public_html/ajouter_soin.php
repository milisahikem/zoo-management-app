<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_permission('ajouter_soin');

$conn = getDatabaseConnection();
$erreur = "";
$role = current_role();
$id_personne = current_user_id();

$stid_next = oci_parse($conn, "SELECT NVL(MAX(id_soin),0)+1 AS NEXT_ID FROM SOINS");
oci_execute($stid_next);
$row_next = oci_fetch_assoc($stid_next);
$next_id = $row_next['NEXT_ID'] ?? 1;
oci_free_statement($stid_next);

if ($role === 'soigneur') {
    $sql_animaux = "SELECT id_animal, nom FROM ANIMAL WHERE id_soigneur_attitre = :id ORDER BY nom";
    $stid_animaux = oci_parse($conn, $sql_animaux);
    oci_bind_by_name($stid_animaux, ':id', $id_personne);
} elseif ($role === 'chef_soigneur') {
    $sql_animaux = "SELECT a.id_animal, a.nom
                    FROM ANIMAL a
                    JOIN ENCLOS en ON en.id_enclos = a.id_enclos
                    JOIN ZONE z ON z.id_zone = en.id_zone
                    WHERE z.id_personnel_chef = :id
                    ORDER BY a.nom";
    $stid_animaux = oci_parse($conn, $sql_animaux);
    oci_bind_by_name($stid_animaux, ':id', $id_personne);
} else {
    $sql_animaux = "SELECT id_animal, nom FROM ANIMAL ORDER BY nom";
    $stid_animaux = oci_parse($conn, $sql_animaux);
}
oci_execute($stid_animaux);
$animaux = [];
while ($a = oci_fetch_assoc($stid_animaux)) $animaux[] = $a;
oci_free_statement($stid_animaux);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_animal = trim($_POST["id_animal"] ?? "");
    $type_soin = strtolower(trim($_POST["type_soin"] ?? ""));
    $dose = trim($_POST["dosejournaliereSoin"] ?? "");
    $date_soin = trim($_POST["date_soin"] ?? "");

    $ids_autorises = array_map(function($a){ return (string)$a['ID_ANIMAL']; }, $animaux);

    if ($id_animal === "" || $type_soin === "" || $dose === "" || $date_soin === "") {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!in_array($type_soin, ['simple','complexe'], true)) {
        $erreur = "Type de soin invalide.";
    } elseif (($role === 'soigneur' || $role === 'chef_soigneur') && $type_soin !== 'simple') {
        $erreur = "Seul un vétérinaire peut enregistrer un soin complexe.";
    } elseif (!in_array((string)$id_animal, $ids_autorises, true)) {
        $erreur = "Vous ne pouvez pas enregistrer un soin pour cet animal.";
    } else {
        $stid_next2 = oci_parse($conn, "SELECT NVL(MAX(id_soin),0)+1 AS NEXT_ID FROM SOINS");
        oci_execute($stid_next2);
        $row_next2 = oci_fetch_assoc($stid_next2);
        $new_id = $row_next2['NEXT_ID'] ?? 1;
        oci_free_statement($stid_next2);

        $sql = "INSERT INTO SOINS (id_soin, type_soin, dosejournaliereSoin, date_soin, id_animal, id_personne)
                VALUES (:id_soin, :type_soin, :dose, TO_DATE(:date_soin, 'YYYY-MM-DD'), :id_animal, :id_personne)";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":id_soin", $new_id);
        oci_bind_by_name($stid, ":type_soin", $type_soin);
        oci_bind_by_name($stid, ":dose", $dose);
        oci_bind_by_name($stid, ":date_soin", $date_soin);
        oci_bind_by_name($stid, ":id_animal", $id_animal);
        oci_bind_by_name($stid, ":id_personne", $id_personne);

        if (oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
            oci_commit($conn);
            header("Location: soins.php");
            exit();
        }

        $e = oci_error($stid);
        $erreur = $e["message"] ?? "Erreur lors de l'ajout du soin.";
        oci_rollback($conn);
        oci_free_statement($stid);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Ajouter un soin</title><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="admin-nav"><a href="accueil.php" class="logo">🌿 Zoo'land Admin</a><div class="nav-actions"><a href="soins.php">Retour</a><a href="logout.php" style="color:#ffcccc;">Déconnexion</a></div></nav>
<div class="container"><div class="page-header"><h1>Ajouter un soin</h1></div>
<div class="card" style="max-width:800px;margin:auto;">
<?php if ($erreur !== ""): ?><div class="erreur"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
<form method="post">
<label>ID soin</label><input type="text" value="<?= htmlspecialchars($next_id) ?>" disabled>
<label for="id_animal">Animal *</label>
<select name="id_animal" id="id_animal" required>
    <option value="">-- Choisir un animal --</option>
    <?php foreach ($animaux as $animal): ?>
        <option value="<?= htmlspecialchars($animal['ID_ANIMAL']) ?>" <?= (($_POST['id_animal'] ?? '') == $animal['ID_ANIMAL']) ? 'selected' : '' ?>><?= htmlspecialchars($animal['ID_ANIMAL'] . ' - ' . $animal['NOM']) ?></option>
    <?php endforeach; ?>
</select>
<label for="type_soin">Type *</label>
<select name="type_soin" id="type_soin" required>
    <option value="">-- Choisir --</option>
    <option value="simple" <?= (($_POST['type_soin'] ?? '') === 'simple') ? 'selected' : '' ?>>Simple</option>
    <?php if ($role === 'veterinaire'): ?><option value="complexe" <?= (($_POST['type_soin'] ?? '') === 'complexe') ? 'selected' : '' ?>>Complexe</option><?php endif; ?>
</select>
<label for="dosejournaliereSoin">Dose *</label><input type="number" step="0.01" name="dosejournaliereSoin" id="dosejournaliereSoin" required value="<?= htmlspecialchars($_POST['dosejournaliereSoin'] ?? '') ?>">
<label for="date_soin">Date *</label><input type="date" name="date_soin" id="date_soin" required value="<?= htmlspecialchars($_POST['date_soin'] ?? date('Y-m-d')) ?>">
<button type="submit" class="btn btn-primary">Ajouter le soin</button>
</form></div></div>
</body></html>
<?php oci_close($conn); ?>