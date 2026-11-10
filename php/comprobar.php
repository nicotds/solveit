<?php
// Corrige la respuesta que escribió el usuario en el modo práctica.
// Compara contra lo que CALCULÓ PHP (guardado en la sesión por similar.php), no contra la IA.
require __DIR__ . '/auth.php';
require __DIR__ . '/gemini.php';     // solo por la función responder()
require __DIR__ . '/verificar.php';

if (usuario_actual() === null) {
    responder(['ok' => false, 'mensaje' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
}

$practica = $_SESSION['practica'] ?? null;
if (!$practica) {
    responder(['ok' => false, 'mensaje' => 'Primero pide un ejercicio.']);
}
$entrada = json_decode(file_get_contents('php://input'), true);

// El usuario se rindió: se le muestra la respuesta correcta
if (!empty($entrada['revelar'])) {
    responder(['ok' => true, 'revelada' => true, 'respuesta_correcta' => $practica['texto']]);
}

$texto = is_string($entrada['respuesta'] ?? null) ? mb_substr($entrada['respuesta'], 0, 100) : '';
$esperado = $practica['esperado'];
sort($esperado);

// ---- Convertir lo escrito en una lista de números ----
$t = strtolower(trim($texto));
$t = preg_replace('/x\d?\s*=/', '', $t);                 // "x = 4", "x1 = 2" -> "4", "2"
if (count($esperado) === 1 && preg_match('/^-?\d+,\d+$/', $t)) {
    $t = str_replace(',', '.', $t);                       // "2,5" como decimal con coma
}
$piezas = preg_split('/\s*(?:;|,|\by\b)\s*/', $t, -1, PREG_SPLIT_NO_EMPTY);

$numeros = [];
foreach ($piezas as $pieza) {
    try {
        $numeros[] = Evaluador::evaluar($pieza);          // admite fracciones y operaciones: 1/2, 3*2
    } catch (EvaluadorError $e) {
        responder(['ok' => true, 'correcto' => false,
            'mensaje' => 'No entendí "' . $pieza . '". Escribe solo números (o fracciones como 1/2); '
                . 'si hay dos respuestas, sepáralas con coma o con "y".']);
    }
}
if (!$numeros) {
    responder(['ok' => true, 'correcto' => false, 'mensaje' => 'Escribe tu respuesta primero.']);
}
sort($numeros);

if (count($numeros) !== count($esperado)) {
    $falta = count($esperado) > 1
        ? 'Este ejercicio tiene ' . count($esperado) . ' soluciones: escríbelas todas, separadas por coma.'
        : 'Este ejercicio tiene una sola respuesta.';
    responder(['ok' => true, 'correcto' => false, 'mensaje' => $falta]);
}
foreach ($esperado as $i => $v) {
    if (!cercanos($numeros[$i], $v)) {
        responder(['ok' => true, 'correcto' => false, 'mensaje' => 'Todavía no. Revisa tus cuentas e inténtalo otra vez.']);
    }
}
responder(['ok' => true, 'correcto' => true, 'mensaje' => '¡Correcto! 🎉']);
