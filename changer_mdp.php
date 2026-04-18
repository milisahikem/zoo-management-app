<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_login();

$conn = getDatabaseConnection();

$message = "";
$message_class = "";

$id_personne = current_user_id();
$forcer = must_change_password();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ancien = $_POST["ancien_mdp"] ?? "";
    $nouveau = $_POST["nouveau_mdp"] ?? "";
    $confirm = $_POST["confirm_mdp"] ?? "";

    $sql = "SELECT motdepasse
            FROM PERSONNEL
            WHERE id_personne = :id_personne";
    $stid = oci_parse($conn, $sql);
    oci_bind_by_name($stid, ":id_personne", $id_personne);
    oci_execute($stid);
    $row = oci_fetch_assoc($stid);
    oci_free_statement($stid);

    if (!$row) {
        $message = "Utilisateur introuvable.";
        $message_class = "erreur";
    } elseif (!password_verify($ancien, $row["MOTDEPASSE"])) {
        $message = "Ancien mot de passe incorrect.";
        $message_class = "erreur";
    } elseif (strlen($nouveau) < 6) {
        $message = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        $message_class = "erreur";
    } elseif ($nouveau !== $confirm) {
        $message = "La confirmation ne correspond pas.";
        $message_class = "erreur";
    } elseif ($nouveau === "zoo123") {
        $message = "Choisissez un mot de passe différent du mot de passe par défaut.";
        $message_class = "erreur";
    } else {
        $hash = password_hash($nouveau, PASSWORD_DEFAULT);

        $sql_update = "UPDATE PERSONNEL
                       SET motdepasse = :motdepasse
                       WHERE id_personne = :id_personne";
        $stid_update = oci_parse($conn, $sql_update);
        oci_bind_by_name($stid_update, ":motdepasse", $hash);
        oci_bind_by_name($stid_update, ":id_personne", $id_personne);

        $ok = @oci_execute($stid_update, OCI_NO_AUTO_COMMIT);

        if ($ok) {
            oci_commit($conn);

            if (isset($_SESSION["user"])) {
                $_SESSION["user"]["must_change_password"] = false;
            }
            $_SESSION["must_change_password"] = false;

            oci_free_statement($stid_update);
            oci_close($conn);

            header("Location: accueil.php");
            exit();
        } else {
            $e = oci_error($stid_update);
            oci_rollback($conn);
            $message = "Erreur lors de la mise à jour : " . htmlspecialchars($e["message"]);
            $message_class = "erreur";
            oci_free_statement($stid_update);
        }
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Changer le mot de passe - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="admin-nav">
    <a href="accueil.php" class="logo">Zoo'land Admin</a>
    <div class="nav-actions">
        <?php if (!$forcer): ?>
            <a href="accueil.php">Retour</a>
        <?php endif; ?>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Changer le mot de passe</h1>
    </div>

    <div class="form-card">
        <?php if ($forcer): ?>
            <div class="info">Première connexion : vous devez changer votre mot de passe.</div>
        <?php endif; ?>

        <?php if ($message !== ""): ?>
            <div class="<?= $message_class ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">
            <label for="ancien_mdp">Ancien mot de passe</label>
            <input type="password" name="ancien_mdp" id="ancien_mdp" required>

            <label for="nouveau_mdp">Nouveau mot de passe</label>
            <input type="password" name="nouveau_mdp" id="nouveau_mdp" required>

            <label for="confirm_mdp">Confirmer le nouveau mot de passe</label>
            <input type="password" name="confirm_mdp" id="confirm_mdp" required>

            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
</div>

</body>
</html>