<?php
// Fase 0: recibe el dibujo (PNG base64), lo envía a Gemini con el prompt de lectura
// y devuelve la respuesta cruda para evaluar qué tan bien lee la letra a mano.
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/php/config.php';

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- 1. Validar la entrada ---
$entrada = json_decode(file_get_contents('php://input'), true);
$imagen = $entrada['imagen'] ?? '';

// Quitar el prefijo "data:image/png;base64,"
$prefijo = 'data:image/png;base64,';
if (strpos($imagen, $prefijo) !== 0) {
    responder(['ok' => false, 'mensaje' => 'La imagen debe ser un PNG en base64.']);
}
$base64 = substr($imagen, strlen($prefijo));

// Solo caracteres válidos de base64 y tamaño razonable (< 5 MB)
if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $base64) || strlen($base64) > 5 * 1024 * 1024) {
    responder(['ok' => false, 'mensaje' => 'Imagen inválida o demasiado grande.']);
}

if (GEMINI_API_KEY === 'PON_AQUI_TU_API_KEY') {
    responder(['ok' => false, 'mensaje' => 'Falta poner la API key en php/config.php.']);
}

// --- 2. Prompt de lectura (solo transcribe, no resuelve) ---
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

$cuerpo = [
    'contents' => [[
        'parts' => [
            ['inline_data' => ['mime_type' => 'image/png', 'data' => $base64]],
            ['text' => $prompt],
        ],
    ]],
    'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => 0.1],
];

// --- 3. Llamada a Gemini con cURL ---
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODELO . ':generateContent';
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . GEMINI_API_KEY,
    ],
    CURLOPT_POSTFIELDS => json_encode($cuerpo),
]);
$respuesta = curl_exec($ch);
$codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorCurl = curl_error($ch);
curl_close($ch);

// --- 4. Errores comprensibles ---
if ($respuesta === false) {
    responder(['ok' => false, 'mensaje' => 'No se pudo conectar con Gemini: ' . $errorCurl
        . ' (si menciona SSL, revisa curl.cainfo en php.ini)']);
}
if ($codigo === 429) {
    responder(['ok' => false, 'mensaje' => 'Se alcanzó el límite de uso, espera un minuto.']);
}
if ($codigo === 400 || $codigo === 403) {
    responder(['ok' => false, 'mensaje' => "Gemini rechazó la petición (HTTP $codigo). ¿Clave inválida?",
        'crudo' => json_decode($respuesta, true)]);
}
if ($codigo !== 200) {
    responder(['ok' => false, 'mensaje' => "Error de Gemini (HTTP $codigo).",
        'crudo' => json_decode($respuesta, true)]);
}

// --- 5. Extraer el texto de la respuesta ---
$datos = json_decode($respuesta, true);
$texto = $datos['candidates'][0]['content']['parts'][0]['text'] ?? null;
if ($texto === null) {
    responder(['ok' => false, 'mensaje' => 'Gemini no devolvió texto.',
        'promptFeedback' => $datos['promptFeedback'] ?? null,
        'finishReason' => $datos['candidates'][0]['finishReason'] ?? null]);
}

responder(['ok' => true, 'lectura' => json_decode($texto, true), 'crudo_texto' => $texto]);
