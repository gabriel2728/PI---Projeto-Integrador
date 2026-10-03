<?php
require_once __DIR__ . '/sessao.php';
session_destroy();
header("Location: login.php");
exit;
?>
