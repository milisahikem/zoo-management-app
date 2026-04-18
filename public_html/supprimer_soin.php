<?php
session_start();
require_once("connex.inc.php");
require_once("auth.php");

require_permission('supprimer_soin');

if (!isset($_GET['id_soin']) || !ctype_digit($_GET['id_soin'])) {
    die("<div class='erreur'>ID soin invalide.</div>");
}

$id_soin = $_GET['id_soin'];
$conn = getDatabaseConnection();

$sql = "DELETE FROM SOINS WHERE id_soin = :id_soin";
$stid = oci_parse($conn, $sql);
oci_bind_by_name($stid, ":id_soin", $id_soin);

if (oci_execute($stid, OCI_NO_AUTO_COMMIT)) {
    oci_commit($conn);
    header("Location: soins.php");
    exit();
} else {
    $e = oci_error($stid);
    oci_rollback($conn);
    die("<div class='erreur'>Erreur lors de la suppression : " . htmlspecialchars($e['message']) . "</div>");
}

oci_free_statement($stid);
oci_close($conn);
?>