<?php
// Cierra la sesión y vuelve al login.
require __DIR__ . '/php/auth.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
