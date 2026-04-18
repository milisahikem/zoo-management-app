<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");
require_permission('calculer_ca');
$conn = getDatabaseConnection();
$mois = trim($_GET['mois'] ?? date('m'));
$annee = trim($_GET['annee'] ?? date('Y'));

$sql_total = "SELECT NVL(SUM(montantCA), 0) AS total_ca FROM GENERE_CA WHERE EXTRACT(MONTH FROM date_ca) = :mois AND EXTRACT(YEAR FROM date_ca) = :annee";
$stid_total = oci_parse($conn, $sql_total); oci_bind_by_name($stid_total, ':mois', $mois); oci_bind_by_name($stid_total, ':annee', $annee); oci_execute($stid_total); $total = oci_fetch_assoc($stid_total);

$sql_annee = "SELECT NVL(SUM(montantCA), 0) AS total_annee FROM GENERE_CA WHERE EXTRACT(YEAR FROM date_ca) = :annee";
$stid_a = oci_parse($conn, $sql_annee); oci_bind_by_name($stid_a, ':annee', $annee); oci_execute($stid_a); $total_annee = oci_fetch_assoc($stid_a);

$sql_par_boutique = "SELECT b.nom_boutique, NVL(SUM(g.montantCA), 0) AS total_boutique FROM BOUTIQUE b LEFT JOIN GENERE_CA g ON g.id_boutique = b.id_boutique AND EXTRACT(MONTH FROM g.date_ca) = :mois AND EXTRACT(YEAR FROM g.date_ca) = :annee GROUP BY b.nom_boutique ORDER BY b.nom_boutique";
$stid_b = oci_parse($conn, $sql_par_boutique); oci_bind_by_name($stid_b, ':mois', $mois); oci_bind_by_name($stid_b, ':annee', $annee); oci_execute($stid_b);
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Calcul du chiffre d'affaires - Zoo'land Admin</title><link rel="stylesheet" href="style.css"></head><body>
<nav class="admin-nav"><a href="accueil.php" class="logo">Zoo'land Admin</a><div class="nav-actions"><a href="accueil.php">Retour</a><a href="logout.php" style="color:#ffcccc;">Déconnexion</a></div></nav>
<div class="container"><div class="page-header"><h1>Calcul du chiffre d'affaires</h1></div>
<div class="card" style="margin-bottom:20px;"><form method="get" style="display:flex;gap:10px;align-items:end;"><div><label>Mois</label><input type="number" min="1" max="12" name="mois" value="<?= htmlspecialchars($mois) ?>"></div><div><label>Année</label><input type="number" min="2000" max="2100" name="annee" value="<?= htmlspecialchars($annee) ?>"></div><button class="btn btn-primary" type="submit">Calculer</button></form></div>
<div class="card" style="margin-bottom:20px;"><h2 style="color:var(--primary-color);margin-bottom:10px;">Total mensuel</h2><p style="font-size:1.5rem;font-weight:bold;"><?= htmlspecialchars($total['TOTAL_CA'] ?? 0) ?> €</p></div>
<div class="card" style="margin-bottom:20px;"><h2 style="color:var(--primary-color);margin-bottom:10px;">Total annuel</h2><p style="font-size:1.5rem;font-weight:bold;"><?= htmlspecialchars($total_annee['TOTAL_ANNEE'] ?? 0) ?> €</p></div>
<div class="card"><h2 style="color:var(--primary-color);margin-bottom:15px;">Détail par boutique pour <?= htmlspecialchars($mois) ?>/<?= htmlspecialchars($annee) ?></h2><div class="table-responsive"><table><tr><th>Boutique</th><th>Total CA (€)</th></tr>
<?php $nb=0; while($row=oci_fetch_assoc($stid_b)): $nb++; ?><tr><td><?= htmlspecialchars($row['NOM_BOUTIQUE']) ?></td><td><?= htmlspecialchars($row['TOTAL_BOUTIQUE']) ?></td></tr><?php endwhile; ?>
<?php if ($nb===0): ?><tr><td colspan="2" style="text-align:center;color:#999;padding:20px;">Aucun chiffre d'affaires trouvé.</td></tr><?php endif; ?></table></div></div></div>
</body></html>
<?php oci_free_statement($stid_total); oci_free_statement($stid_a); oci_free_statement($stid_b); oci_close($conn); ?>