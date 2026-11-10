<?php
// Acceso a la tabla "problemas". Todas las consultas usan sentencias preparadas.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/imagen.php';

// Guarda un problema resuelto. $r es la respuesta que armó resolver.php. Devuelve el id nuevo.
function guardar_problema($usuarioId, $r, $imagenRuta)
{
    $bd = conectar_bd();

    // Todo lo que no cabe en columnas propias va en pasos_json (así problema.php lo puede mostrar completo)
    $detalle = json_encode([
        'pasos'          => $r['pasos'],
        'resumen'        => $r['resumen'],
        'comprobaciones' => $r['comprobaciones'],
        'resultado_latex' => $r['resultado_latex'],
        'resultado_ia'   => $r['resultado_ia'],
    ], JSON_UNESCAPED_UNICODE);

    $resultado = $r['resultado_texto'] ?? ($r['resultado_ia'] !== '' ? $r['resultado_ia'] : '—');
    $resultado = mb_substr((string)$resultado, 0, 250);

    $sql = 'INSERT INTO problemas (usuario_id, expresion, latex, tipo, resultado_texto, pasos_json, verificacion, nivel, imagen_ruta)
            VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?)';
    $q = $bd->prepare($sql);
    $q->bind_param('isssssss', $usuarioId, $r['expresion'], $r['tipo'], $resultado, $detalle,
                   $r['verificacion'], $r['nivel'], $imagenRuta);
    $q->execute();
    return $bd->insert_id;
}

// Devuelve un problema SOLO si pertenece al usuario; si no, null.
function obtener_problema($id, $usuarioId)
{
    $q = conectar_bd()->prepare('SELECT * FROM problemas WHERE id = ? AND usuario_id = ?');
    $q->bind_param('ii', $id, $usuarioId);
    $q->execute();
    return $q->get_result()->fetch_assoc() ?: null;
}

// Borra un problema propio y su imagen del disco. Devuelve true si borró algo.
function borrar_problema($id, $usuarioId)
{
    $problema = obtener_problema($id, $usuarioId);
    if (!$problema) {
        return false;
    }
    $q = conectar_bd()->prepare('DELETE FROM problemas WHERE id = ? AND usuario_id = ?');
    $q->bind_param('ii', $id, $usuarioId);
    $q->execute();
    borrar_png_pizarron($problema['imagen_ruta']);
    return true;
}

// Reconstruye, a partir de una fila de la BD, el mismo arreglo que devuelve resolver.php.
function problema_a_solucion($fila)
{
    $detalle = json_decode($fila['pasos_json'], true) ?: [];
    return [
        'id'              => (int)$fila['id'],
        'expresion'       => $fila['expresion'],
        'tipo'            => $fila['tipo'],
        'nivel'           => $fila['nivel'],
        'verificacion'    => $fila['verificacion'],
        // Si no se pudo verificar, no hay resultado de PHP: se muestra el de la IA marcado como tal
        'resultado_texto' => $fila['verificacion'] === 'no_verificado' ? null : $fila['resultado_texto'],
        'resultado_latex' => $detalle['resultado_latex'] ?? null,
        'comprobaciones'  => $detalle['comprobaciones'] ?? [],
        'resultado_ia'    => $detalle['resultado_ia'] ?? '',
        'pasos'           => $detalle['pasos'] ?? [],
        'resumen'         => $detalle['resumen'] ?? '',
    ];
}
