<?php
// Página provisional de la Fase 1: comprueba que Apache y la base de datos responden.
require __DIR__ . '/php/db.php';
$bd = conectar_bd();
$n = $bd->query('SELECT COUNT(*) AS n FROM usuarios')->fetch_assoc()['n'];
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>SolveIt</title></head>
<body>
  <h1>SolveIt</h1>
  <p>✓ Apache y PHP funcionan.</p>
  <p>✓ Base de datos conectada. Usuarios registrados: <?= (int)$n ?></p>
</body>
</html>
