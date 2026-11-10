<?php
// Verificación independiente: compara lo que dijo la IA con lo que calcula PHP por su cuenta.
// Es una función pura (no llama a Gemini ni a la base de datos), así se puede probar sola.
require_once __DIR__ . '/evaluador.php';

// Los números de la IA pueden venir redondeados, así que aquí la tolerancia es de 1e-6 (relativa).
function cercanos($a, $b)
{
    return abs($a - $b) <= 1e-6 * max(1.0, abs($a), abs($b));
}

// 4.0 -> "4", 2.5 -> "2.5", 0.3333333333333 -> "0.3333333333"
function formato_numero($v)
{
    $t = rtrim(rtrim(number_format($v, 10, '.', ''), '0'), '.');
    return ($t === '' || $t === '-0') ? '0' : $t;
}

// $expresion: la expresión confirmada por el usuario (ya validada).
// $ia: el JSON que devolvió Gemini al resolver (campos "valor" y "soluciones").
// Devuelve un arreglo con:
//   estado        'verificado' | 'no_verificado' | 'inconsistente' | 'error'
//   mensaje       (solo si estado = 'error': PHP no pudo calcularla, p. ej. división entre cero)
//   resultado_texto / resultado_latex   lo que PHP calculó (null si no se pudo verificar)
//   comprobaciones  expresiones con el valor sustituido, para mostrar el sello ("2*(4)+3=11")
function verificar_respuesta($expresion, $ia)
{
    $expresion = Evaluador::limpiar($expresion);
    $esEcuacion = strpos($expresion, '=') !== false;

    // ---------- Operación: PHP la calcula y se compara con el valor de la IA ----------
    if (!$esEcuacion) {
        try {
            $v = Evaluador::evaluar($expresion);
        } catch (EvaluadorError $e) {
            return ['estado' => 'error', 'mensaje' => $e->getMessage()];
        }
        $valorIA = $ia['valor'] ?? null;
        $coincide = is_numeric($valorIA) && cercanos((float)$valorIA, $v);
        return [
            'estado'          => $coincide ? 'verificado' : 'inconsistente',
            'tipo'            => 'operacion',
            'valor_php'       => $v,
            'resultado_texto' => formato_numero($v),
            'resultado_latex' => formato_numero($v),
            'comprobaciones'  => [$expresion . '=' . formato_numero($v)],
        ];
    }

    // ---------- Ecuación: PHP la resuelve por su lado y se comparan las soluciones ----------
    try {
        $r = Evaluador::resolverEcuacion($expresion);
    } catch (EvaluadorError $e) {
        return ['estado' => 'error', 'mensaje' => $e->getMessage()];
    }

    if ($r['tipo'] === 'no_soportada') {
        // PHP no sabe comprobar este tipo de ecuación: no se puede asegurar nada
        return ['estado' => 'no_verificado', 'tipo' => 'ecuacion', 'resultado_texto' => null,
                'resultado_latex' => null, 'soluciones_php' => [], 'comprobaciones' => []];
    }

    // Soluciones de la IA como números ordenados (si algo no es numérico, no coincidirá)
    $solIA = [];
    $todasNumericas = true;
    foreach (($ia['soluciones'] ?? []) as $s) {
        if (is_numeric($s)) $solIA[] = (float)$s; else $todasNumericas = false;
    }
    sort($solIA);

    $solPHP = $r['soluciones'];
    $coincide = $todasNumericas && count($solIA) === count($solPHP);
    if ($coincide) {
        foreach ($solPHP as $i => $s) {
            if (!cercanos($solIA[$i], $s)) $coincide = false;
        }
    }

    // Texto del resultado calculado por PHP
    if ($r['tipo'] === 'ninguna') {
        $texto = 'No tiene solución real';
        $latex = null;
    } elseif ($r['tipo'] === 'infinitas') {
        $texto = 'Tiene infinitas soluciones (cualquier valor de x sirve)';
        $latex = null;
    } else {
        $trozos = array_map(function ($s) { return 'x = ' . formato_numero($s); }, $solPHP);
        $texto = implode(' y ', $trozos);
        $latex = implode(' \quad\text{y}\quad ', $trozos);
    }

    // Comprobación por sustitución: la ecuación con la solución puesta donde estaba x
    $comprobaciones = [];
    foreach ($solPHP as $s) {
        $comprobaciones[] = str_replace('x', '(' . formato_numero($s) . ')', $expresion);
    }

    return [
        'estado'          => $coincide ? 'verificado' : 'inconsistente',
        'tipo'            => 'ecuacion',
        'tipo_solucion'   => $r['tipo'],
        'soluciones_php'  => $solPHP,
        'resultado_texto' => $texto,
        'resultado_latex' => $latex,
        'comprobaciones'  => $comprobaciones,
    ];
}
