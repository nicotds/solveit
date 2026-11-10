<?php
// Casos de prueba del evaluador. Abrir en el navegador: http://localhost/solveit/tests/probar_evaluador.php
// Cada caso dice qué esperamos; la página muestra ✓ si coincide y ✗ si no.
require __DIR__ . '/../php/evaluador.php';

// Tipos de caso:
//   valor     -> evaluar() debe dar ese número
//   ecuacion  -> resolverEcuacion() debe dar ese tipo y esas soluciones
//   error     -> evaluar() debe lanzar un error CONTROLADO (sin romper la página)
//   rechazo   -> validar() debe devolver false (lista blanca)
$casos = [
    // --- Tabla de la sección 7.3 del documento ---
    ['valor', '2+3*4', 14],
    ['valor', '(2+3)*4', 20],
    ['valor', '2^3^2', 512],
    ['valor', '-3+5', 2],
    ['valor', '10/4', 2.5],
    ['valor', 'sqrt(16)', 4],
    ['valor', '3(2+1)', 9],
    ['ecuacion', '2x+3=11', 'unica', [4]],
    ['ecuacion', 'x^2-5x+6=0', 'dos', [2, 3]],
    ['error', '1/0'],
    ['rechazo', 'abc'],
    ['rechazo', 'system("ls")'],

    // --- Más aritmética ---
    ['valor', '0.1+0.2', 0.3],
    ['valor', '-2^2', -4],
    ['valor', '2^-1', 0.5],
    ['valor', '2*-3', -6],
    ['valor', '2--3', 5],
    ['valor', '(2)(3)(4)', 24],
    ['valor', '2sqrt(9)', 6],
    ['valor', 'sqrt(9)sqrt(4)', 6],
    ['valor', '20/5', 4],
    ['valor', '15-7', 8],
    ['valor', '(2+3)(4-1)', 15],
    ['valor', '100/4/5', 5],
    ['valor', '2^(1+2)', 8],
    ['valor', '.5+.5', 1],
    ['valor', '  2 + 3 ', 5],

    // --- Errores controlados ---
    ['error', 'sqrt(-1)'],
    ['error', '1/(2-2)'],
    ['error', '2+'],
    ['error', '(2+3'],
    ['error', '2+3)'],
    ['error', '()'],
    ['error', '*3'],
    ['error', '1.2.3'],
    ['error', 'sqrt4'],
    ['error', '0^-1'],
    ['error', '9^9^9^9'],
    ['error', '(-8)^0.5'],
    ['error', '2x'],               // x sin valor
    ['error', '2x=4'],             // evaluar() no acepta ecuaciones

    // --- Lista blanca ---
    ['rechazo', ''],
    ['rechazo', '2+3;'],
    ['rechazo', 'eval(1)'],
    ['rechazo', '$x'],
    ['rechazo', 'sqrtsqrt'],
    ['rechazo', 'sin(1)'],
    ['rechazo', 'y+1'],
    ['rechazo', '1=2=3'],
    ['rechazo', str_repeat('1+', 80) . '1'],     // demasiado largo
    ['rechazo', "2+3\n; phpinfo()"],

    // --- Ecuaciones ---
    ['ecuacion', '3x-5=10', 'unica', [5]],
    ['ecuacion', 'x/2=7', 'unica', [14]],
    ['ecuacion', '2(x+1)=10', 'unica', [4]],
    ['ecuacion', '2x+3=3x-1', 'unica', [4]],
    ['ecuacion', '0.5x=3', 'unica', [6]],
    ['ecuacion', '7=2x+1', 'unica', [3]],
    ['ecuacion', 'x^2=4', 'dos', [-2, 2]],
    ['ecuacion', '(x+1)(x-1)=0', 'dos', [-1, 1]],
    ['ecuacion', 'x^2-2x+1=0', 'unica', [1]],
    ['ecuacion', 'x^2+1=0', 'ninguna', []],
    ['ecuacion', 'x^2+x=x^2+4', 'unica', [4]],        // la x² se cancela: queda lineal
    ['ecuacion', '2x+3=2x+3', 'infinitas', []],
    ['ecuacion', 'x+1=x+2', 'ninguna', []],
    ['ecuacion', '2x^2-8=0', 'dos', [-2, 2]],
    ['ecuacion', 'x^3=8', 'no_soportada', []],        // grado 3: fuera de alcance
    ['ecuacion', '1/x=2', 'no_soportada', []],        // no es polinomio
    ['ecuacion', 'sqrt(x)=2', 'no_soportada', []],    // no es polinomio
];

// Ejecuta un caso y devuelve [pasó?, texto de lo obtenido, texto de lo esperado]
function ejecutar($caso)
{
    $tipo = $caso[0];
    $entrada = $caso[1];
    try {
        switch ($tipo) {
            case 'valor':
                $r = Evaluador::evaluar($entrada);
                return [Evaluador::iguales($r, $caso[2]), "$r", (string)$caso[2]];

            case 'ecuacion':
                $r = Evaluador::resolverEcuacion($entrada);
                $ok = $r['tipo'] === $caso[2] && count($r['soluciones']) === count($caso[3]);
                if ($ok) {
                    foreach ($caso[3] as $i => $v) {
                        if (!Evaluador::iguales($r['soluciones'][$i], $v)) $ok = false;
                    }
                }
                return [$ok, $r['tipo'] . ' [' . implode(', ', $r['soluciones']) . ']',
                             $caso[2] . ' [' . implode(', ', $caso[3]) . ']'];

            case 'error':
                $r = Evaluador::evaluar($entrada);
                return [false, "resultado $r", 'error controlado'];

            case 'rechazo':
                $acepta = Evaluador::validar($entrada);
                return [!$acepta, $acepta ? 'aceptada' : 'rechazada', 'rechazada'];
        }
    } catch (EvaluadorError $e) {
        // Un error controlado es lo ESPERADO en 'error'; en los demás casos es un fallo
        return [$tipo === 'error', 'error: ' . $e->getMessage(), $tipo === 'error' ? 'error controlado' : 'sin error'];
    } catch (Throwable $e) {
        // Cualquier otra excepción (TypeError, etc.) significa que el evaluador se rompió
        return [false, 'ROMPIÓ: ' . $e->getMessage(), 'sin romperse'];
    }
    return [false, 'tipo de caso desconocido', ''];
}

$filas = [];
$pasaron = 0;
foreach ($casos as $caso) {
    [$ok, $obtenido, $esperado] = ejecutar($caso);
    if ($ok) $pasaron++;
    $filas[] = [$ok, $caso[0], $caso[1], $esperado, $obtenido];
}
$total = count($casos);

// Salida en texto si se ejecuta desde la terminal
if (PHP_SAPI === 'cli') {
    foreach ($filas as [$ok, $tipo, $entrada, $esperado, $obtenido]) {
        if (!$ok) echo "✗ [$tipo] " . json_encode($entrada, JSON_UNESCAPED_UNICODE) . " esperado: $esperado | obtenido: $obtenido\n";
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
<title>Pruebas del evaluador</title>
<style>
  body { font-family: system-ui, sans-serif; max-width: 900px; margin: 20px auto; padding: 0 10px; }
  table { border-collapse: collapse; width: 100%; }
  th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: .93rem; }
  th { background: #eef1fb; }
  code { font-family: ui-monospace, Consolas, monospace; }
  .ok { color: #137333; font-weight: bold; text-align: center; }
  .mal { color: #b00; font-weight: bold; text-align: center; }
  tr.fallo { background: #fde8e8; }
  .resumen { font-size: 1.2rem; padding: 10px; border-radius: 6px; }
  .resumen.bien { background: #e3f6e8; } .resumen.mal { background: #fde8e8; }
</style>
</head>
<body>
<h1>Pruebas del evaluador</h1>
<p class="resumen <?= $pasaron === $total ? 'bien' : 'mal' ?>">
  <b><?= $pasaron ?> de <?= $total ?></b> casos correctos
  <?= $pasaron === $total ? '✓' : '— hay fallos (filas en rojo)' ?>
</p>
<table>
  <tr><th></th><th>Tipo</th><th>Entrada</th><th>Esperado</th><th>Obtenido</th></tr>
  <?php foreach ($filas as [$ok, $tipo, $entrada, $esperado, $obtenido]): ?>
    <tr class="<?= $ok ? '' : 'fallo' ?>">
      <td class="<?= $ok ? 'ok' : 'mal' ?>"><?= $ok ? '✓' : '✗' ?></td>
      <td><?= h($tipo) ?></td>
      <td><code><?= h(strlen($entrada) > 40 ? substr($entrada, 0, 37) . '…' : $entrada) ?></code></td>
      <td><?= h($esperado) ?></td>
      <td><?= h($obtenido) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</body>
</html>
