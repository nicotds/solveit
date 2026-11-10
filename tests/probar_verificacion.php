<?php
// Pruebas de la verificación SIN llamar a Gemini: se inventan respuestas de la IA, buenas y malas,
// para comprobar que PHP las acepta o las marca como inconsistentes.
// Abrir en el navegador: http://localhost/solveit/tests/probar_verificacion.php
require __DIR__ . '/../php/verificar.php';

// [descripción, expresión, respuesta inventada de la IA, estado esperado]
$casos = [
    ['Operación correcta',                 '2+3*4',        ['valor' => 14],                 'verificado'],
    ['Operación con IA equivocada',        '2+3*4',        ['valor' => 20],                 'inconsistente'],
    ['Operación sin valor de la IA',       '2+3*4',        ['valor' => null],               'inconsistente'],
    ['Operación con valor en texto',       '10/4',         ['valor' => '2.5'],              'verificado'],
    ['Operación 1/0 (PHP no puede)',       '1/0',          ['valor' => 0],                  'error'],
    ['Operación con x sin ecuación',       '2x+1',         ['valor' => 5],                  'error'],

    ['Lineal correcta',                    '2x+3=11',      ['soluciones' => [4]],           'verificado'],
    ['Lineal con IA equivocada',           '2x+3=11',      ['soluciones' => [5]],           'inconsistente'],
    ['Lineal sin soluciones de la IA',     '2x+3=11',      ['soluciones' => []],            'inconsistente'],
    ['Lineal con IA dando dos soluciones', '2x+3=11',      ['soluciones' => [4, 7]],        'inconsistente'],
    ['Lineal con decimal exacto',          '3x=1',         ['soluciones' => [0.3333333333]], 'verificado'],
    ['Lineal con IA muy redondeada',       '3x=1',         ['soluciones' => [0.33]],        'inconsistente'],
    ['Solución no numérica',               '2x+3=11',      ['soluciones' => ['cuatro']],    'inconsistente'],

    ['Cuadrática correcta',                'x^2-5x+6=0',   ['soluciones' => [3, 2]],        'verificado'],
    ['Cuadrática con una sola raíz',       'x^2-5x+6=0',   ['soluciones' => [2]],           'inconsistente'],
    ['Cuadrática con raíz mala',           'x^2-5x+6=0',   ['soluciones' => [2, 4]],        'inconsistente'],
    ['Cuadrática doble raíz',              'x^2-2x+1=0',   ['soluciones' => [1]],           'verificado'],
    ['Sin solución real, IA de acuerdo',   'x^2+1=0',      ['soluciones' => []],            'verificado'],
    ['Sin solución real, IA inventa',      'x^2+1=0',      ['soluciones' => [1]],           'inconsistente'],
    ['Infinitas, IA de acuerdo',           '2x+3=2x+3',    ['soluciones' => []],            'verificado'],
    ['Sin solución (grado 0)',             'x+1=x+2',      ['soluciones' => []],            'verificado'],

    ['Fuera de alcance (cúbica)',          'x^3=8',        ['soluciones' => [2]],           'no_verificado'],
    ['Ecuación mal formada',               'x+=3',         ['soluciones' => [1]],           'error'],
];

$pasaron = 0;
$filas = [];
foreach ($casos as [$desc, $expr, $ia, $esperado]) {
    $r = verificar_respuesta($expr, $ia);
    $ok = $r['estado'] === $esperado;
    if ($ok) $pasaron++;
    $filas[] = [$ok, $desc, $expr, json_encode($ia), $esperado, $r['estado'], $r['resultado_texto'] ?? ($r['mensaje'] ?? '')];
}
$total = count($casos);

if (PHP_SAPI === 'cli') {
    foreach ($filas as [$ok, $desc, $expr, $ia, $esp, $obt, $texto]) {
        if (!$ok) echo "✗ $desc: $expr -> esperado $esp, obtenido $obt\n";
    }
    echo "$pasaron de $total casos correctos\n";
    exit($pasaron === $total ? 0 : 1);
}
function h($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pruebas de verificación</title>
<style>
  body { font-family: system-ui, sans-serif; max-width: 1000px; margin: 20px auto; padding: 0 10px; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: .9rem; }
  th { background: #eef1fb; } code { font-family: ui-monospace, Consolas, monospace; }
  .ok { color: #137333; font-weight: bold; text-align: center; } .mal { color: #b00; font-weight: bold; text-align: center; }
  tr.fallo { background: #fde8e8; }
  .resumen { font-size: 1.2rem; padding: 10px; border-radius: 6px; }
  .resumen.bien { background: #e3f6e8; } .resumen.mal { background: #fde8e8; }
</style>
</head>
<body>
<h1>Pruebas de verificación (sin IA)</h1>
<p>Se inventan respuestas de la IA, correctas y equivocadas, y se comprueba que PHP las clasifica bien.</p>
<p class="resumen <?= $pasaron === $total ? 'bien' : 'mal' ?>"><b><?= $pasaron ?> de <?= $total ?></b> casos correctos
  <?= $pasaron === $total ? '✓' : '— hay fallos' ?></p>
<table>
  <tr><th></th><th>Caso</th><th>Expresión</th><th>Respuesta inventada de la IA</th><th>Esperado</th><th>Obtenido</th><th>PHP calculó</th></tr>
  <?php foreach ($filas as [$ok, $desc, $expr, $ia, $esp, $obt, $texto]): ?>
    <tr class="<?= $ok ? '' : 'fallo' ?>">
      <td class="<?= $ok ? 'ok' : 'mal' ?>"><?= $ok ? '✓' : '✗' ?></td>
      <td><?= h($desc) ?></td><td><code><?= h($expr) ?></code></td><td><code><?= h($ia) ?></code></td>
      <td><?= h($esp) ?></td><td><?= h($obt) ?></td><td><?= h($texto) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</body>
</html>
