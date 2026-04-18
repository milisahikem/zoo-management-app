<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_section('nourrir_animaux');

$conn = getDatabaseConnection();
$uid = current_user_id();
$role = current_role();
$erreur = "";
$message = "";

if ($role === 'soigneur') {
    $sql_animaux = "SELECT a.id_animal, a.nom, e.nom_usuel AS espece
                    FROM ANIMAL a
                    LEFT JOIN ESPECE e ON a.id_espece = e.id_espece
                    WHERE a.id_soigneur_attitre = :id
                    ORDER BY a.nom";
    $stid_animaux = oci_parse($conn, $sql_animaux);
    oci_bind_by_name($stid_animaux, ':id', $uid);
} else {
    $sql_animaux = "SELECT a.id_animal, a.nom, e.nom_usuel AS espece
                    FROM ANIMAL a
                    LEFT JOIN ESPECE e ON a.id_espece = e.id_espece
                    JOIN ENCLOS en ON en.id_enclos = a.id_enclos
                    JOIN ZONE z ON z.id_zone = en.id_zone
                    WHERE z.id_personnel_chef = :id
                    ORDER BY a.nom";
    $stid_animaux = oci_parse($conn, $sql_animaux);
    oci_bind_by_name($stid_animaux, ':id', $uid);
}
oci_execute($stid_animaux);
$animaux = [];
while ($animal = oci_fetch_assoc($stid_animaux)) $animaux[] = $animal;
oci_free_statement($stid_animaux);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_animal = trim($_POST["id_animal"] ?? "");
    $date_nourriture = trim($_POST["date_nourriture"] ?? "");
    $dose_journaliere = trim($_POST["dose_journaliere"] ?? "");
    $ids_autorises = array_map(function($a){ return (string)$a['ID_ANIMAL']; }, $animaux);

    if ($id_animal === "" || $date_nourriture === "" || $dose_journaliere === "") {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!in_array((string)$id_animal, $ids_autorises, true)) {
        $erreur = "Vous ne pouvez pas nourrir cet animal.";
    } else {
        $sql_insert = "INSERT INTO NOURRIT (id_personne, id_animal, date_nourriture, dose_journaliere)
                       VALUES (:id_personne, :id_animal, TO_DATE(:date_nourriture, 'YYYY-MM-DD'), :dose_journaliere)";
        $stid_insert = oci_parse($conn, $sql_insert);
        oci_bind_by_name($stid_insert, ":id_personne", $uid);
        oci_bind_by_name($stid_insert, ":id_animal", $id_animal);
        oci_bind_by_name($stid_insert, ":date_nourriture", $date_nourriture);
        oci_bind_by_name($stid_insert, ":dose_journaliere", $dose_journaliere);
        if (oci_execute($stid_insert, OCI_NO_AUTO_COMMIT)) {
            oci_commit($conn);
            $message = "Nourrissage enregistré avec succès.";
        } else {
            $e = oci_error($stid_insert);
            $erreur = $e["message"] ?? "Erreur lors de l'enregistrement.";
            oci_rollback($conn);
        }
        oci_free_statement($stid_insert);
    }
}

if ($role === 'soigneur') {
    $sql_hist = "SELECT TO_CHAR(n.date_nourriture, 'DD/MM/YYYY') AS date_nourriture, n.dose_journaliere, a.nom AS nom_animal,
                        p.prenom || ' ' || p.nom AS employe
                 FROM NOURRIT n
                 JOIN ANIMAL a ON a.id_animal = n.id_animal
                 JOIN PERSONNEL p ON p.id_personne = n.id_personne
                 WHERE n.id_personne = :id
                 ORDER BY n.date_nourriture DESC";
} else {
    $sql_hist = "SELECT TO_CHAR(n.date_nourriture, 'DD/MM/YYYY') AS date_nourriture, n.dose_journaliere, a.nom AS nom_animal,
                        p.prenom || ' ' || p.nom AS employe
                 FROM NOURRIT n
                 JOIN ANIMAL a ON a.id_animal = n.id_animal
                 JOIN PERSONNEL p ON p.id_personne = n.id_personne
                 JOIN ENCLOS en ON en.id_enclos = a.id_enclos
                 JOIN ZONE z ON z.id_zone = en.id_zone
                 WHERE z.id_personnel_chef = :id
                 ORDER BY n.date_nourriture DESC";
}
$stid_hist = oci_parse($conn, $sql_hist);
oci_bind_by_name($stid_hist, ':id', $uid);
oci_execute($stid_hist);
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Nourrir les animaux - Zoo'land Admin</title><link rel="stylesheet" href="style.css"></head><body>
<nav class="admin-nav"><a href="accueil.php" class="logo">Zoo'land Admin</a><div class="nav-actions"><a href="accueil.php">Retour</a><a href="logout.php" style="color:#ffcccc;">Déconnexion</a></div></nav>
<div class="container"><div class="page-header"><h1>Nourrir les animaux</h1></div>
<div class="card" style="max-width:850px;margin:0 auto 20px auto;">
<?php if ($message !== ""): ?><div class="success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($erreur !== ""): ?><div class="erreur"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
<form method="post">
<label for="id_animal">Animal *</label>
<select name="id_animal" id="id_animal" required><option value="">-- Choisir un animal --</option><?php foreach ($animaux as $animal): ?><option value="<?= htmlspecialchars($animal['ID_ANIMAL']) ?>"><?= htmlspecialchars($animal['ID_ANIMAL'] . ' - ' . $animal['NOM'] . ' (' . ($animal['ESPECE'] ?? 'Espèce inconnue') . ')') ?></option><?php endforeach; ?></select>
<label for="date_nourriture">Date *</label><input type="date" name="date_nourriture" id="date_nourriture" required value="<?= date('Y-m-d') ?>">
<label for="dose_journaliere">Dose journalière *</label><input type="number" step="0.01" name="dose_journaliere" id="dose_journaliere" required>
<button type="submit" class="btn btn-primary">Enregistrer le nourrissage</button></form></div>
<div class="card"><h2 style="font-size:1rem;margin-bottom:15px;color:var(--primary-color);">Historique des nourrissages</h2><div class="table-responsive"><table><tr><th>Date</th><th>Animal</th><th>Dose</th><th>Employé</th></tr>
<?php $nb=0; while ($row = oci_fetch_assoc($stid_hist)): $nb++; ?><tr><td><?= htmlspecialchars($row['DATE_NOURRITURE']) ?></td><td><?= htmlspecialchars($row['NOM_ANIMAL']) ?></td><td><?= htmlspecialchars($row['DOSE_JOURNALIERE']) ?></td><td><?= htmlspecialchars($row['EMPLOYE']) ?></td></tr><?php endwhile; ?>
<?php if ($nb === 0): ?><tr><td colspan="4" style="text-align:center;color:#999;padding:20px;">Aucun nourrissage enregistré.</td></tr><?php endif; ?></table></div></div></div>
</body></html>
<?php oci_free_statement($stid_hist); oci_close($conn); ?>