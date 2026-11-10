<?php
// Crea (una sola vez) una cuenta de demostración con problemas de ejemplo ya resueltos y verificados.
// Ejecutar en el navegador: http://localhost/solveit/sql/cargar_demo.php
// Cuenta creada:  demo@solveit.local  /  contraseña: demo1234
// Es seguro ejecutarlo varias veces: no duplica nada.
// (Borra este archivo si algún día el proyecto se publica en un servidor real.)
require __DIR__ . '/../php/db.php';
require __DIR__ . '/../php/verificar.php';
require __DIR__ . '/../php/demo_datos.php';
require __DIR__ . '/../php/problemas.php';

// Solo desde este mismo computador
if (PHP_SAPI !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    die('Solo se puede ejecutar desde localhost.');
}
if (PHP_SAPI !== 'cli') header('Content-Type: text/plain; charset=utf-8');

$bd = conectar_bd();
$email = 'demo@solveit.local';

// 1. La cuenta de demostración
$q = $bd->prepare('SELECT id FROM usuarios WHERE email = ?');
$q->bind_param('s', $email);
$q->execute();
$fila = $q->get_result()->fetch_assoc();
if ($fila) {
    $usuarioId = (int)$fila['id'];
    echo "La cuenta demo ya existía (id $usuarioId).\n";
} else {
    $nombre = 'Demo';
    $hash = password_hash('demo1234', PASSWORD_DEFAULT);
    $q = $bd->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)');
    $q->bind_param('sss', $nombre, $email, $hash);
    $q->execute();
    $usuarioId = $bd->insert_id;
    echo "Cuenta demo creada (id $usuarioId): $email / demo1234\n";
}

// 2. Los ejemplos: cada uno pasa por la verificación de PHP; solo se guardan los verificados
foreach (demo_ejemplos() as $ej) {
    $q = $bd->prepare('SELECT id FROM problemas WHERE usuario_id = ? AND expresion = ?');
    $q->bind_param('is', $usuarioId, $ej['expresion']);
    $q->execute();
    if ($q->get_result()->num_rows > 0) {
        echo "  = ya estaba: {$ej['expresion']}\n";
        continue;
    }

    $v = verificar_respuesta($ej['expresion'], $ej['ia']);
    if ($v['estado'] !== 'verificado') {
        echo "  ✗ NO verificado, no se guarda: {$ej['expresion']} ({$v['estado']})\n";
        continue;
    }
    guardar_problema($usuarioId, [
        'expresion'       => $ej['expresion'],
        'tipo'            => $v['tipo'],
        'nivel'           => 'basico',
        'verificacion'    => 'verificado',
        'resultado_texto' => $v['resultado_texto'],
        'resultado_latex' => $v['resultado_latex'],
        'comprobaciones'  => $v['comprobaciones'],
        'resultado_ia'    => $ej['ia']['resultado_texto'],
        'pasos'           => $ej['ia']['pasos'],
        'resumen'         => $ej['ia']['resumen'],
    ], null);
    echo "  ✓ guardado: {$ej['expresion']}  ->  {$v['resultado_texto']}\n";
}
echo "\nListo. Inicia sesión con demo@solveit.local / demo1234\n";
