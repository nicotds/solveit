<?php
// Imagen del pizarrón (PNG base64) -> expresión transcrita por Gemini. NO resuelve nada.
require __DIR__ . '/auth.php';
require __DIR__ . '/gemini.php';
require __DIR__ . '/imagen.php';

// Solo usuarios con sesión
if (usuario_actual() === null) {
    responder(['ok' => false, 'mensaje' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
}

// --- 1. Validar la imagen ---
$entrada = json_decode(file_get_contents('php://input'), true);
[$imagenValida, $errorImagen] = decodificar_png_dataurl($entrada['imagen'] ?? '');
if ($imagenValida === null) {
    responder(['ok' => false, 'mensaje' => $errorImagen], 400);
}
$base64 = $imagenValida['base64'];

// --- 2. Limitar peticiones y soltar el bloqueo de la sesión antes de la llamada larga ---
limitar_peticiones(4);
session_write_close();

// --- 3. Prompt de lectura (solo transcribe) ---
$prompt = <<<'TXT'
Eres un lector de matemática escrita a mano. Mira la imagen de un pizarrón digital.

Tu único trabajo es TRANSCRIBIR lo escrito. NO lo resuelvas ni lo corrijas.

1. Decide si hay una expresión matemática legible: una operación aritmética o una ecuación
   de una variable. Si el lienzo está vacío, son garabatos, hay dibujos que no son matemática,
   o es tan ambiguo que no se puede leer, responde con "legible": false.
2. Si es legible, transcríbela con esta sintaxis exacta:
   - números y decimales con punto
   - la variable es x
   - operadores: + - * / ^
   - multiplicación SIEMPRE con * (escribe 2*x, no 2x)
   - paréntesis ( )
   - raíz cuadrada: sqrt(...)
   - fracciones con / y paréntesis
3. Si algún símbolo es dudoso (por ejemplo un "+" que podría ser "×"), elige la lectura más
   probable y descríbelo en "dudas".

Responde SOLO con JSON válido, sin texto adicional:
{
  "legible": true,
  "expresion": "2*x+3=11",
  "latex": "2x+3=11",
  "tipo": "ecuacion",
  "dudas": ["el tercer símbolo podría ser + o ×"]
}
("tipo" es "ecuacion" u "operacion"; "dudas" es lista vacía si no hay dudas.)
Si no es legible: {"legible": false, "motivo": "explicación breve y amable en español"}
TXT;

$resultado = pedir_a_gemini([
    ['inline_data' => ['mime_type' => 'image/png', 'data' => $base64]],
    ['text' => $prompt],
], 0.1);

if (!$resultado['ok']) {
    responder(['ok' => false, 'mensaje' => $resultado['mensaje']]);
}
$lectura = $resultado['json'];

// --- 4. No legible ---
if (empty($lectura['legible'])) {
    $motivo = is_string($lectura['motivo'] ?? null) ? $lectura['motivo'] : 'No se pudo leer lo escrito.';
    responder(['ok' => false, 'mensaje' => $motivo . ' Intenta escribirlo más grande y claro.']);
}

// --- 5. Limpiar y validar la expresión con lista blanca ---
$expresion = is_string($lectura['expresion'] ?? null) ? $lectura['expresion'] : '';
// Por si la IA devolvió símbolos Unicode en vez de la sintaxis pedida
$expresion = strtr($expresion, ['×' => '*', '·' => '*', '÷' => '/', '−' => '-', '–' => '-', 'X' => 'x',
    '²' => '^2', '³' => '^3', '√' => 'sqrt', ',' => '.']);
$expresion = preg_replace('/\s+/', '', $expresion);

$sinSqrt = str_replace('sqrt', '', $expresion);
if ($expresion === '' || strlen($expresion) > 100
    || !preg_match('/^[0-9x+\-*\/^().=]+$/', $sinSqrt)
    || substr_count($expresion, '=') > 1) {
    responder(['ok' => false, 'mensaje' => 'No pude interpretar lo escrito como una operación o ecuación básica. '
        . 'Intenta de nuevo o escríbela con el teclado.']);
}

$dudas = [];
foreach (($lectura['dudas'] ?? []) as $d) {
    if (is_string($d) && $d !== '') $dudas[] = $d;
}

responder([
    'ok'        => true,
    'expresion' => $expresion,
    'latex'     => is_string($lectura['latex'] ?? null) ? $lectura['latex'] : $expresion,
    'tipo'      => strpos($expresion, '=') !== false ? 'ecuacion' : 'operacion', // lo decide PHP
    'dudas'     => $dudas,
]);
