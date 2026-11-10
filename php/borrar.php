<?php
// Borra un problema PROPIO (y su imagen). Solo acepta POST con el token CSRF de la sesión.
require __DIR__ . '/auth.php';
require __DIR__ . '/problemas.php';

$usuario = usuario_actual();
if ($usuario === null) {
    header('Location: ../login.php');   // este archivo está dentro de php/
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificar_csrf($_POST['csrf'] ?? null)) {
    http_response_code(400);
    die('Petición no válida.');
}

$id = (int)($_POST['id'] ?? 0);
// borrar_problema() comprueba que el problema pertenezca al usuario en sesión
$borrado = $id > 0 && borrar_problema($id, $usuario['id']);

header('Location: ../historial.php?' . ($borrado ? 'borrado=1' : 'error=1'));
exit;
