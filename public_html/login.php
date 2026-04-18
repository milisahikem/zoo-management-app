<?php
session_start();
require_once("connex.inc.php");

if (isset($_SESSION['user'])) {
    header("Location: accueil.php");
    exit();
}

$erreur = "";
$identifiant = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifiant = trim($_POST["identifiant"] ?? "");
    $motdepasse  = $_POST["motdepasse"] ?? "";

    if ($identifiant === "" || $motdepasse === "") {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!ctype_digit($identifiant)) {
        $erreur = "Identifiant invalide.";
    } else {
        $conn = getDatabaseConnection();

        $sql = "SELECT id_personne,
                       prenom,
                       nom,
                       motdepasse,
                       type_poste
                FROM PERSONNEL
                WHERE id_personne = :id_personne";

        $stid = oci_parse($conn, $sql);
        $id_personne = (int)$identifiant;
        oci_bind_by_name($stid, ":id_personne", $id_personne);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            die("Erreur execute : " . htmlspecialchars($e["message"]));
        }

        $user = oci_fetch_assoc($stid);

        if ($user && password_verify($motdepasse, $user["MOTDEPASSE"])) {
            session_regenerate_id(true);

            /* mot de passe par défaut imposé à la première connexion */
            $must_change = password_verify("zoo123", $user["MOTDEPASSE"]);

            $_SESSION["user"] = [
                "id_personne" => (int)$user["ID_PERSONNE"],
                "prenom" => $user["PRENOM"],
                "nom" => $user["NOM"],
                "role" => strtolower(trim($user["TYPE_POSTE"])),
                "must_change_password" => $must_change
            ];

            /* compatibilité avec l'ancien code du projet */
            $_SESSION["role"] = strtolower(trim($user["TYPE_POSTE"]));
            $_SESSION["id_personne"] = (int)$user["ID_PERSONNE"];
            $_SESSION["must_change_password"] = $must_change;

            oci_free_statement($stid);
            oci_close($conn);

            if ($must_change) {
                header("Location: changer_mdp.php");
            } else {
                header("Location: accueil.php");
            }
            exit();
        } else {
            $erreur = "Identifiant ou mot de passe incorrect.";
        }

        oci_free_statement($stid);
        oci_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Zoo'land Admin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1>Zoo'land Admin</h1>
            <p>Connexion à l'espace d'administration</p>
        </div>

        <?php if ($erreur !== ""): ?>
            <div class="erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="post">
            <label for="identifiant">Identifiant</label>
            <input
                type="number"
                name="identifiant"
                id="identifiant"
                required
                value="<?= htmlspecialchars($identifiant) ?>"
                placeholder="Exemple : 201"
            >

            <label for="motdepasse">Mot de passe</label>
            <input
                type="password"
                name="motdepasse"
                id="motdepasse"
                required
                placeholder="Votre mot de passe"
            >

            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>
    </div>
</div>

</body>
</html>