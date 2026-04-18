<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_permission('animaux');

$conn = getDatabaseConnection();
$role = current_role();
$id = current_user_id();
$search = trim($_GET['search'] ?? "");

if ($role === 'soigneur') {
    $sql = "SELECT a.*, e.nom_usuel 
            FROM ANIMAL a
            JOIN ESPECE e ON a.id_espece = e.id_espece
            WHERE a.id_soigneur_attitre = :id";
} elseif ($role === 'chef_soigneur') {
    $sql = "SELECT a.*, e.nom_usuel 
            FROM ANIMAL a
            JOIN ESPECE e ON a.id_espece = e.id_espece
            JOIN ENCLOS en ON a.id_enclos = en.id_enclos
            JOIN ZONE z ON en.id_zone = z.id_zone
            WHERE z.id_personnel_chef = :id";
} else {
    $sql = "SELECT a.*, e.nom_usuel 
            FROM ANIMAL a
            JOIN ESPECE e ON a.id_espece = e.id_espece
            WHERE 1=1";
}

if ($search !== "") {
    $sql .= " AND LOWER(a.nom) LIKE LOWER(:search)";
}
$sql .= " ORDER BY a.nom";

$stid = oci_parse($conn, $sql);
if ($role === 'soigneur' || $role === 'chef_soigneur') {
    oci_bind_by_name($stid, ":id", $id);
}
if ($search !== "") {
    $like = "%" . $search . "%";
    oci_bind_by_name($stid, ":search", $like);
}
oci_execute($stid);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Animaux</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="admin-nav">
    <a href="accueil.php" class="logo">🌿 Zoo'land Admin</a>
    <div class="nav-actions">
        <a href="accueil.php">Retour</a>
        <a href="logout.php" style="color:#ffcccc;">Déconnexion</a>
    </div>
</nav>
<div class="container">
    <div class="page-header">
        <h1>🐾 Liste des animaux</h1>
        <?php if (can_access('ajouter_animal')): ?><a href="ajouter_animal.php" class="btn btn-primary">+ Ajouter un animal</a><?php endif; ?>
    </div>
    <div class="card">
        <form method="get" style="margin-bottom:15px;display:flex;gap:10px;">
            <input type="text" name="search" placeholder="Rechercher un animal..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-blue">Rechercher</button>
        </form>
        <div class="table-responsive">
            <table>
                <tr><th>ID</th><th>Nom</th><th>Espèce</th><th>Poids</th><th>Régime</th><th>Détails</th></tr>
                <?php $nb=0; while ($row = oci_fetch_assoc($stid)): $nb++; ?>
                    <tr>
                        <td><?= $row['ID_ANIMAL'] ?></td>
                        <td><?= htmlspecialchars($row['NOM']) ?></td>
                        <td><?= htmlspecialchars($row['NOM_USUEL']) ?></td>
                        <td><?= htmlspecialchars($row['POIDS']) ?> kg</td>
                        <td><?= htmlspecialchars($row['REGIME_ALIMENTAIRE']) ?></td>
                        <td><a href="animal_detail.php?id=<?= $row['ID_ANIMAL'] ?>" class="btn btn-sm">Voir</a></td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($nb === 0): ?><tr><td colspan="6" style="text-align:center;color:#999;padding:20px;">Aucun animal trouvé.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
<?php oci_free_statement($stid); oci_close($conn); ?>