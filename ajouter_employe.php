<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_permission('ajouter_employe');

$conn         = getDatabaseConnection();
$message      = "";
$messageClass = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom     = trim($_POST["nom"]     ?? "");
    $prenom  = trim($_POST["prenom"]  ?? "");
    $salaire = trim($_POST["salaire"] ?? "");
    $poste   = trim($_POST["poste"]   ?? "");

    $postes_ok = ['soigneur','chef_soigneur','veterinaire','entretien','employe_boutique','responsable_boutique','technique','comptable','directeur','rh'];

    if ($nom === "" || $prenom === "" || $salaire === "" || $poste === "") {
        $message = "Tous les champs sont obligatoires.";
        $messageClass = "erreur";
    } elseif (!is_numeric($salaire) || $salaire < 0) {
        $message = "Salaire invalide.";
        $messageClass = "erreur";
    } elseif (!in_array($poste, $postes_ok, true)) {
        $message = "Poste invalide.";
        $messageClass = "erreur";
    } else {
        $mdp = password_hash("zoo123", PASSWORD_DEFAULT);

        $sql1 = "INSERT INTO PERSONNEL
                     (id_personne, prenom, nom, motdepasse, date_entreefonction,
                      date_sortie, salaire, type_poste, id_chef_soigneur, id_remplacant)
                 VALUES (
                     (SELECT NVL(MAX(id_personne), 0) + 1 FROM PERSONNEL),
                     :prenom, :nom, :mdp, TRUNC(SYSDATE),
                     NULL, :salaire, :poste, NULL, NULL
                 )";

        $stid1 = oci_parse($conn, $sql1);
        oci_bind_by_name($stid1, ":prenom",  $prenom);
        oci_bind_by_name($stid1, ":nom",     $nom);
        oci_bind_by_name($stid1, ":mdp",     $mdp);
        oci_bind_by_name($stid1, ":salaire", $salaire);
        oci_bind_by_name($stid1, ":poste",   $poste);

        if (@oci_execute($stid1, OCI_NO_AUTO_COMMIT)) {
            $stid_id = oci_parse($conn, "SELECT MAX(id_personne) AS id FROM PERSONNEL");
            oci_execute($stid_id);
            $row = oci_fetch_assoc($stid_id);
            $new_id = $row["ID"];
            oci_free_statement($stid_id);

            $sql2 = "INSERT INTO HISTORIQUE_EMPLOI
                         (id_historique, type_poste, date_debut, date_fin, id_personne)
                     VALUES (
                         (SELECT NVL(MAX(id_historique), 0) + 1 FROM HISTORIQUE_EMPLOI),
                         :poste, TRUNC(SYSDATE), NULL, :id
                     )";
            $stid2 = oci_parse($conn, $sql2);
            oci_bind_by_name($stid2, ":poste", $poste);
            oci_bind_by_name($stid2, ":id",    $new_id);

            if (@oci_execute($stid2, OCI_NO_AUTO_COMMIT)) {
                oci_commit($conn);
                $message = "Employé ajouté avec succès. ID : $new_id — Mot de passe par défaut : zoo123";
                $messageClass = "success";
            } else {
                oci_rollback($conn);
                $e = oci_error($stid2);
                $message = "Erreur historique : " . htmlspecialchars($e["message"]);
                $messageClass = "erreur";
            }
            oci_free_statement($stid2);
        } else {
            oci_rollback($conn);
            $e = oci_error($stid1);
            $message = "Erreur : " . htmlspecialchars($e["message"]);
            $messageClass = "erreur";
        }
        oci_free_statement($stid1);
    }
}
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un employé - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="admin-nav">
    <a href="accueil.php" class="logo">🌿 Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="personnel.php">Retour au personnel</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>
<div class="container">
    <div class="page-header"><h1>Ajouter un employé</h1></div>
    <div class="card form-card">
        <?php if ($message !== ""): ?>
            <p class="<?= $messageClass ?>"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="post">
            <label>Nom</label>
            <input type="text" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">

            <label>Prénom</label>
            <input type="text" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">

            <label>Salaire (€)</label>
            <input type="number" step="0.01" min="0" name="salaire" required value="<?= htmlspecialchars($_POST['salaire'] ?? '') ?>">

            <label>Poste</label>
            <select name="poste" required>
                <option value="">-- Sélectionner --</option>
                <?php foreach (['soigneur'=>'Soigneur','chef_soigneur'=>'Chef soigneur','veterinaire'=>'Vétérinaire','entretien'=>'Entretien','employe_boutique'=>'Employé boutique','responsable_boutique'=>'Responsable boutique','technique'=>'Technique','comptable'=>'Comptable','directeur'=>'Directeur','rh'=>'Ressources humaines'] as $k => $v): ?>
                    <option value="<?= $k ?>" <?= (($_POST['poste'] ?? '') === $k) ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>

            <p style="font-size:12px;color:#999;margin-top:12px;">
                Le mot de passe par défaut sera <strong>zoo123</strong>. L'employé devra le changer à sa première connexion.
            </p>

            <button type="submit" class="btn btn-primary">Ajouter l'employé</button>
            <a href="personnel.php" class="btn btn-blue" style="margin-left:10px;">Annuler</a>
        </form>
    </div>
</div>
</body>
</html>