<?php
session_start();
require_once("auth.php");
require_login();
header("Location: changer_mdp.php");
exit();
?>