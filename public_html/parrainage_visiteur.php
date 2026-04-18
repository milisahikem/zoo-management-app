<?php
session_start();
require_once("connex.inc.php");

$conn = getDatabaseConnection();

$message = "";
$message_class = "";

/* =========================
   LISTE ANIMAUX
========================= */
$animaux = [];
$sql_animaux = "SELECT a.id_animal,
                       a.nom,
                       e.nom_usuel
                FROM ANIMAL a
                JOIN ESPECE e ON a.id_espece = e.id_espece
                ORDER BY a.nom";
$stid_animaux = oci_parse($conn, $sql_animaux);
oci_execute($stid_animaux);
while ($row = oci_fetch_assoc($stid_animaux)) {
    $animaux[] = $row;
}
oci_free_statement($stid_animaux);

/* =========================
   LISTE NIVEAUX
========================= */
$niveaux = [];
$sql_niveaux = "SELECT id_niveau, libelle
                FROM NIVEAU
                ORDER BY id_niveau";
$stid_niveaux = oci_parse($conn, $sql_niveaux);
oci_execute($stid_niveaux);
while ($row = oci_fetch_assoc($stid_niveaux)) {
    $niveaux[] = $row;
}
oci_free_statement($stid_niveaux);

/* =========================
   PRESTATIONS PAR NIVEAU
========================= */
$prestations_par_niveau = [];

$sql_prestations = "SELECT n.id_niveau,
                           n.libelle,
                           p.type
                    FROM DONNE_ACCES d
                    JOIN NIVEAU n ON d.id_niveau = n.id_niveau
                    JOIN PRESTATION p ON d.id_prestation = p.id_prestation
                    ORDER BY n.id_niveau, p.type";

$stid_prestations = oci_parse($conn, $sql_prestations);
oci_execute($stid_prestations);

while ($row = oci_fetch_assoc($stid_prestations)) {
    $id_niv = (int)$row['ID_NIVEAU'];
    if (!isset($prestations_par_niveau[$id_niv])) {
        $prestations_par_niveau[$id_niv] = [
            'libelle' => $row['LIBELLE'],
            'prestations' => []
        ];
    }
    $prestations_par_niveau[$id_niv]['prestations'][] = $row['TYPE'];
}
oci_free_statement($stid_prestations);

/* =========================
   REGLES MONTANT PAR NIVEAU
========================= */
function montant_valide_par_niveau($libelle_niveau, $montant) {
    $niveau = strtolower(trim($libelle_niveau));

    if ($niveau === 'bronze') {
        return $montant >= 0 && $montant < 200;
    }
    if ($niveau === 'argent') {
        return $montant >= 200 && $montant < 280;
    }
    if ($niveau === 'or') {
        return $montant >= 280;
    }

    return false;
}

/* =========================
   AJOUT PARRAINAGE VISITEUR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $id_animal = isset($_POST['id_animal']) ? (int)$_POST['id_animal'] : 0;
    $id_niveau = isset($_POST['id_niveau']) ? (int)$_POST['id_niveau'] : 0;
    $montant   = isset($_POST['montant']) ? (float)$_POST['montant'] : -1;

    if ($nom === '' || $prenom === '' || $id_animal <= 0 || $id_niveau <= 0 || $montant < 0) {
        $message = "Veuillez remplir correctement tous les champs.";
        $message_class = "erreur";
    } else {
        $sql_niveau = "SELECT libelle
                       FROM NIVEAU
                       WHERE id_niveau = :id_niveau";
        $stid_niveau = oci_parse($conn, $sql_niveau);
        oci_bind_by_name($stid_niveau, ":id_niveau", $id_niveau);
        oci_execute($stid_niveau);
        $niveau_row = oci_fetch_assoc($stid_niveau);
        oci_free_statement($stid_niveau);

        if (!$niveau_row) {
            $message = "Niveau invalide.";
            $message_class = "erreur";
        } elseif (!montant_valide_par_niveau($niveau_row['LIBELLE'], $montant)) {
            $message = "Le montant ne correspond pas au niveau choisi.";
            $message_class = "erreur";
        } else {
            $sql_visiteur = "SELECT id_visiteur
                             FROM VISITEUR
                             WHERE UPPER(nom) = UPPER(:nom)
                               AND UPPER(prenom) = UPPER(:prenom)";

            $stid_visiteur = oci_parse($conn, $sql_visiteur);
            oci_bind_by_name($stid_visiteur, ":nom", $nom);
            oci_bind_by_name($stid_visiteur, ":prenom", $prenom);
            oci_execute($stid_visiteur);
            $visiteur = oci_fetch_assoc($stid_visiteur);
            oci_free_statement($stid_visiteur);

            if ($visiteur) {
                $id_visiteur = (int)$visiteur['ID_VISITEUR'];
            } else {
                $sql_new_id = "SELECT NVL(MAX(id_visiteur), 800) + 1 AS NEW_ID
                               FROM VISITEUR";
                $stid_new_id = oci_parse($conn, $sql_new_id);
                oci_execute($stid_new_id);
                $new_id_row = oci_fetch_assoc($stid_new_id);
                oci_free_statement($stid_new_id);

                $id_visiteur = (int)$new_id_row['NEW_ID'];

                $sql_insert_visiteur = "INSERT INTO VISITEUR (id_visiteur, nom, prenom)
                                        VALUES (:id_visiteur, :nom, :prenom)";
                $stid_insert_visiteur = oci_parse($conn, $sql_insert_visiteur);
                oci_bind_by_name($stid_insert_visiteur, ":id_visiteur", $id_visiteur);
                oci_bind_by_name($stid_insert_visiteur, ":nom", $nom);
                oci_bind_by_name($stid_insert_visiteur, ":prenom", $prenom);

                $ok_visiteur = @oci_execute($stid_insert_visiteur, OCI_NO_AUTO_COMMIT);

                if (!$ok_visiteur) {
                    $e = oci_error($stid_insert_visiteur);
                    oci_rollback($conn);
                    $message = "Erreur lors de la création du visiteur : " . htmlspecialchars($e['message']);
                    $message_class = "erreur";
                    oci_free_statement($stid_insert_visiteur);
                    $id_visiteur = 0;
                }

                oci_free_statement($stid_insert_visiteur);
            }

            if ($id_visiteur > 0) {
                $sql_check = "SELECT COUNT(*) AS NB
                              FROM PARRAINER
                              WHERE id_visiteur = :id_visiteur
                                AND id_animal = :id_animal
                                AND TRUNC(date_parrainage) = TRUNC(SYSDATE)";

                $stid_check = oci_parse($conn, $sql_check);
                oci_bind_by_name($stid_check, ":id_visiteur", $id_visiteur);
                oci_bind_by_name($stid_check, ":id_animal", $id_animal);
                oci_execute($stid_check);
                $check = oci_fetch_assoc($stid_check);
                oci_free_statement($stid_check);

                if ($check && (int)$check['NB'] > 0) {
                    $message = "Vous avez déjà parrainé cet animal aujourd'hui.";
                    $message_class = "erreur";
                    oci_rollback($conn);
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
                        $message = "Merci, votre parrainage a bien été enregistré.";
                        $message_class = "success";
                    } else {
                        $e = oci_error($stid_insert);
                        oci_rollback($conn);
                        $message = "Erreur lors du parrainage : " . htmlspecialchars($e['message']);
                        $message_class = "erreur";
                    }

                    oci_free_statement($stid_insert);
                }
            }
        }
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Parrainer un animal - Zoo'land</title>
    <link rel="stylesheet" href="style_visiteur.css">
    <style>
        .parrainage-section {
            background: #f9f9f9;
            padding: 80px 10vw;
        }

        .parrainage-card {
            max-width: 980px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(0,0,0,0.08);
            padding: 40px;
        }

        .parrainage-card h2 {
            font-family: var(--font-titre);
            color: var(--primary-color);
            font-size: 2.6rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .parrainage-card .intro {
            text-align: center;
            color: #666;
            margin-bottom: 28px;
            font-size: 1.05rem;
        }

        .parrainage-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .parrainage-grid .full {
            grid-column: 1 / -1;
        }

        .parrainage-card label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .parrainage-card input,
        .parrainage-card select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            font-family: var(--font-texte);
            background: #fff;
        }

        .parrainage-card input:focus,
        .parrainage-card select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(46,90,39,0.10);
        }

        .parrainage-message {
            margin-bottom: 20px;
        }

        .prestations-box {
            display: none;
            background: #f4f8f2;
            border-left: 4px solid var(--accent-color);
            border-radius: 10px;
            padding: 18px 20px;
        }

        .prestations-box h3 {
            font-family: var(--font-titre);
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .prestations-box ul {
            margin: 0;
            padding-left: 20px;
            color: #555;
        }

        .prestations-box p {
            margin-top: 12px;
            color: #666;
            font-weight: 600;
        }

        .parrainage-actions {
            text-align: center;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .parrainage-card {
                padding: 24px;
            }

            .parrainage-grid {
                grid-template-columns: 1fr;
            }

            .parrainage-grid .full {
                grid-column: auto;
            }

            .parrainage-card h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">ZOO'LAND</div>
    <ul class="nav-links">
        <li><a href="index.php">Accueil</a></li>
        <li><a href="index.php#animaux">Animaux</a></li>
        <li><a href="index.php#parrainage">Parrainage</a></li>
    </ul>
</nav>

<header class="hero" style="background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.60)), url('images/accueil.avif');">
    <div class="hero-content">
        <h1>Parrainez un animal</h1>
        <p>
            Soutenez concrètement le zoo en choisissant un animal, un niveau de contribution
            et découvrez les prestations associées à votre geste.
        </p>
    </div>
</header>

<section class="parrainage-section">
    <div class="parrainage-card">
        <h2>Formulaire de parrainage</h2>
        <p class="intro">Un geste concret pour soutenir les animaux du zoo.</p>

        <?php if ($message !== ""): ?>
            <div class="parrainage-message <?= $message_class ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" id="formParrainage">
            <div class="parrainage-grid">
                <div>
                    <label for="nom">Nom</label>
                    <input type="text" name="nom" id="nom" required>
                </div>

                <div>
                    <label for="prenom">Prénom</label>
                    <input type="text" name="prenom" id="prenom" required>
                </div>

                <div>
                    <label for="id_animal">Animal à parrainer</label>
                    <select name="id_animal" id="id_animal" required>
                        <option value="">-- Choisir un animal --</option>
                        <?php foreach ($animaux as $animal): ?>
                            <option value="<?= $animal['ID_ANIMAL'] ?>">
                                <?= htmlspecialchars($animal['NOM']) ?> (<?= htmlspecialchars($animal['NOM_USUEL']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="id_niveau">Niveau de contribution</label>
                    <select name="id_niveau" id="id_niveau" required>
                        <option value="">-- Choisir un niveau --</option>
                        <?php foreach ($niveaux as $niveau): ?>
                            <option value="<?= $niveau['ID_NIVEAU'] ?>">
                                <?= htmlspecialchars($niveau['LIBELLE']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="full prestations-box" id="bloc-prestations">
                    <h3>Prestations incluses</h3>
                    <ul id="liste-prestations"></ul>
                    <p id="regle-montant"></p>
                </div>

                <div class="full">
                    <label for="montant">Montant (€)</label>
                    <input type="number" step="0.01" min="0" name="montant" id="montant" required>
                </div>

                <div class="full parrainage-actions">
                    <button type="submit" class="btn-primary">Valider le parrainage</button>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
const prestationsParNiveau = <?= json_encode($prestations_par_niveau, JSON_UNESCAPED_UNICODE) ?>;

const selectNiveau = document.getElementById('id_niveau');
const blocPrestations = document.getElementById('bloc-prestations');
const listePrestations = document.getElementById('liste-prestations');
const regleMontant = document.getElementById('regle-montant');

function mettreAJourPrestations() {
    const id = selectNiveau.value;

    listePrestations.innerHTML = '';
    regleMontant.textContent = '';

    if (!id || !prestationsParNiveau[id]) {
        blocPrestations.style.display = 'none';
        return;
    }

    blocPrestations.style.display = 'block';

    prestationsParNiveau[id].prestations.forEach(function(item) {
        const li = document.createElement('li');
        li.textContent = item;
        listePrestations.appendChild(li);
    });

    const libelle = (prestationsParNiveau[id].libelle || '').toLowerCase();

    if (libelle === 'bronze') {
        regleMontant.textContent = "Montant conseillé : inférieur à 200 €.";
    } else if (libelle === 'argent') {
        regleMontant.textContent = "Montant conseillé : entre 200 € et 279,99 €.";
    } else if (libelle === 'or') {
        regleMontant.textContent = "Montant conseillé : à partir de 280 €.";
    }
}

selectNiveau.addEventListener('change', mettreAJourPrestations);
</script>

</body>
</html>