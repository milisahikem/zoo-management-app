<?php
function getDatabaseConnection() {
    require_once("myparam.inc.php");
    $conn = oci_connect(MYUSER, MYPASS, MYHOST);
    if (!$conn) {
        $e = oci_error();
        die("Erreur de connexion : " . htmlentities($e['message'], ENT_QUOTES));
    }
    return $conn;
}

?>
