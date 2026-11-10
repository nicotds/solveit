<?php
require_once __DIR__ . '/iconos.php';

// Barra superior común a las páginas con sesión. $actual: 'pizarron' | 'historial' | 'admin' | 'problema'
function barra_superior($usuario, $actual)
{
    $enlace = function ($clave, $url, $texto) use ($actual) {
        $clase = $actual === $clave ? ' class="activo"' : '';
        return '<a href="' . $url . '"' . $clase . '>' . $texto . '</a>';
    };
    echo '<header class="barra"><div class="barra-in">';
    echo '<a class="marca" href="index.php">SolveIt</a>';
    echo '<nav>' . $enlace('pizarron', 'index.php', 'Pizarrón') . $enlace('historial', 'historial.php', 'Historial');
    if ($usuario['rol'] === 'admin') {
        echo $enlace('admin', 'admin.php', 'Admin');
    }
    echo '</nav>';
    $inicial = e(mb_substr(trim($usuario['nombre']), 0, 1));
    echo '<span class="usuario"><span class="avatar" aria-hidden="true">' . $inicial . '</span>'
       . '<span class="nombre">' . e($usuario['nombre']) . '</span> <a href="logout.php">Salir</a></span>';
    echo '</div></header>';
}
