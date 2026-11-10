<?php
// "Dame un ejercicio parecido": Gemini propone un ejercicio nuevo y PHP calcula la respuesta correcta
// por su cuenta. La respuesta esperada se guarda en la SESIÓN (el navegador nunca la recibe hasta
// que el usuario pida verla), y comprobar.php corrige lo que escriba el usuario.
require __DIR__ . '/auth.php';
require __DIR__ . '/gemini.php';
require __DIR__ . '/verificar.php';

if (usuario_actual() === null) {
    responder(['ok' => false, 'mensaje' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
}

$entrada = json_decode(file_get_contents('php://input'), true);
$original = Evaluador::limpiar(is_string($entrada['expresion'] ?? null) ? $entrada['expresion'] : '');
if (!Evaluador::validar($original)) {
    responder(['ok' => false, 'mensaje' => 'La expresión de partida no es válida.'], 400);
}

limitar_peticiones(4);
session_write_close();

$prompt = <<<TXT
Eres un generador de ejercicios de matemática básica.
Ejercicio original: $original

Crea UN ejercicio nuevo del mismo tipo y dificultad, pero con números distintos.
Reglas:
- Si el original es una ecuación, la nueva es una ecuación de una variable x (si es cuadrática, con raíces reales).
- Si el original es una operación, la nueva es una operación aritmética.
- Sintaxis exacta: números con punto decimal, variable x, operadores + - * / ^, multiplicación SIEMPRE con *,
  paréntesis ( ) y raíz cuadrada sqrt(...).
- No lo resuelvas.

Responde SOLO con JSON válido: {"expresion": "2*x+5=17"}
TXT;

// Hasta 2 intentos: PHP descarta cualquier ejercicio que no pueda resolver por sí mismo
$ejercicio = null;
$esperado = null;
$ultimoError = 'No se pudo generar un ejercicio. Intenta de nuevo.';
for ($intento = 0; $intento < 2 && $ejercicio === null; $intento++) {
    $r = pedir_a_gemini([['text' => $prompt]], 0.9);
    if (!$r['ok']) {
        $ultimoError = $r['mensaje'];
        break;
    }
    $candidato = Evaluador::limpiar(is_string($r['json']['expresion'] ?? null) ? $r['json']['expresion'] : '');
    $candidato = strtr($candidato, ['×' => '*', '÷' => '/', '−' => '-']);
    if ($candidato === '' || $candidato === $original || !Evaluador::validar($candidato)) continue;

    $v = verificar_respuesta($candidato, []);   // PHP calcula la respuesta correcta
    if ($v['estado'] === 'error' || $v['estado'] === 'no_verificado') continue;

    if ($v['tipo'] === 'operacion') {
        $esperado = [$v['valor_php']];
    } elseif (in_array($v['tipo_solucion'], ['unica', 'dos'], true)) {
        $esperado = $v['soluciones_php'];
    } else {
        continue;   // sin solución o infinitas: no sirve para practicar
    }
    $ejercicio = $candidato;
    $textoEsperado = $v['resultado_texto'];
}

if ($ejercicio === null) {
    responder(['ok' => false, 'mensaje' => $ultimoError]);
}

// Reabrir la sesión (se cerró antes de la llamada larga) para guardar la respuesta esperada
session_start();
$_SESSION['practica'] = ['expresion' => $ejercicio, 'esperado' => $esperado, 'texto' => $textoEsperado];
session_write_close();

responder([
    'ok'        => true,
    'expresion' => $ejercicio,
    'tipo'      => strpos($ejercicio, '=') !== false ? 'ecuacion' : 'operacion',
    'cantidad'  => count($esperado),   // cuántas respuestas se esperan (1 o 2)
]);
