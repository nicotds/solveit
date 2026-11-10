<?php
// Expresión confirmada (texto) -> solución paso a paso (Gemini) + verificación independiente (PHP)
// + guardado en el historial. Con "demo": usa un ejemplo guardado y NO llama a Gemini.
require __DIR__ . '/auth.php';
require __DIR__ . '/gemini.php';
require __DIR__ . '/verificar.php';
require __DIR__ . '/problemas.php';
require __DIR__ . '/demo_datos.php';

$usuario = usuario_actual();
if ($usuario === null) {
    responder(['ok' => false, 'mensaje' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
}

// --- 1. Validar la entrada (lista blanca) ---
$entrada = json_decode(file_get_contents('php://input'), true);
$expresion = Evaluador::limpiar(is_string($entrada['expresion'] ?? null) ? $entrada['expresion'] : '');
$nivel = ($entrada['nivel'] ?? '') === 'avanzado' ? 'avanzado' : 'basico';
$modoDemo = !empty($entrada['demo']);

if (!Evaluador::validar($expresion)) {
    responder(['ok' => false, 'mensaje' => 'La expresión está vacía o tiene caracteres no permitidos.'], 400);
}

// --- 2. Comprobar con PHP que la expresión se puede calcular, ANTES de gastar una llamada a la IA ---
$prueba = verificar_respuesta($expresion, []);
if ($prueba['estado'] === 'error') {
    responder(['ok' => false, 'mensaje' => 'No se puede resolver: ' . $prueba['mensaje']]);
}

// --- 3. Obtener la solución: de un ejemplo guardado (demo) o de Gemini ---
if ($modoDemo) {
    $ia = demo_buscar($expresion);
    if ($ia === null) {
        responder(['ok' => false, 'mensaje' => 'En el modo demo solo se pueden resolver los ejemplos guardados.']);
    }
} else {
    limitar_peticiones(4);
    session_write_close();   // liberar la sesión durante la llamada larga

    $descripcionNivel = $nivel === 'basico' ? 'basico = para quien empieza' : 'avanzado = conciso y técnico';
    $prompt = <<<TXT
Eres un profesor de matemática paciente. Resuelve esto: $expresion
Nivel de explicación: $nivel  ($descripcionNivel)

Reglas:
- Solo operaciones aritméticas y ecuaciones de una variable (lineales o cuadráticas simples).
- Si está fuera de ese alcance, responde {"soportado": false, "motivo": "..."}.
- Da los números finales también como valores numéricos en "soluciones" / "valor".
- Si un valor no es exacto, escríbelo con al menos 8 decimales (por ejemplo 0.33333333, no 0.33).
- En "latex" de cada paso usa LaTeX válido, sin signos de dólar.
- Todo el texto en español.

Responde SOLO con JSON válido, sin texto adicional:
{
  "soportado": true,
  "tipo": "ecuacion",
  "resultado_texto": "x = 4",
  "soluciones": [4],
  "valor": null,
  "pasos": [
    {"titulo": "Restar 3 en ambos lados", "latex": "2x = 8", "explicacion": "..."},
    {"titulo": "Dividir entre 2", "latex": "x = 4", "explicacion": "..."}
  ],
  "resumen": "una frase final"
}
("tipo" es "ecuacion" u "operacion". En una ecuación "soluciones" es una lista de números y "valor" es null;
en una operación "valor" es el número resultante y "soluciones" es una lista vacía.
Si la ecuación no tiene solución real o tiene infinitas, "soluciones" es una lista vacía.)
TXT;

    $resultado = pedir_a_gemini([['text' => $prompt]], 0.2);
    if (!$resultado['ok']) {
        responder(['ok' => false, 'mensaje' => $resultado['mensaje']]);
    }
    $ia = $resultado['json'];

    if (empty($ia['soportado'])) {
        $motivo = is_string($ia['motivo'] ?? null) ? $ia['motivo'] : 'Este tipo de problema está fuera del alcance.';
        responder(['ok' => false, 'mensaje' => $motivo . ' SolveIt resuelve operaciones y ecuaciones de una variable.']);
    }
}

// --- 4. Limpiar los pasos (es texto externo: se recorta y se fuerza a texto) ---
$pasos = [];
foreach (array_slice(is_array($ia['pasos'] ?? null) ? $ia['pasos'] : [], 0, 15) as $p) {
    if (!is_array($p)) continue;
    $pasos[] = [
        'titulo'      => mb_substr((string)($p['titulo'] ?? ''), 0, 150),
        'latex'       => mb_substr((string)($p['latex'] ?? ''), 0, 300),
        'explicacion' => mb_substr((string)($p['explicacion'] ?? ''), 0, 600),
    ];
}

// --- 5. Verificación independiente con PHP ---
$v = verificar_respuesta($expresion, $ia);

$respuesta = [
    'ok'              => true,
    'expresion'       => $expresion,
    'nivel'           => $nivel,
    'tipo'            => strpos($expresion, '=') !== false ? 'ecuacion' : 'operacion',
    'verificacion'    => $v['estado'],             // verificado | no_verificado | inconsistente
    'resultado_texto' => $v['resultado_texto'],    // calculado por PHP (null si no se pudo verificar)
    'resultado_latex' => $v['resultado_latex'],
    'comprobaciones'  => $v['comprobaciones'],
    'resultado_ia'    => mb_substr((string)($ia['resultado_texto'] ?? ''), 0, 200),   // lo que dijo la IA
    'pasos'           => $pasos,
    'resumen'         => mb_substr((string)($ia['resumen'] ?? ''), 0, 300),
];

// --- 6. Guardar en el historial (si algo falla, la solución igual se muestra) ---
$imagenRuta = null;
try {
    if (!empty($entrada['imagen'])) {
        [$img, $errorImg] = decodificar_png_dataurl($entrada['imagen']);
        if ($img !== null) {
            $imagenRuta = guardar_png_pizarron($img['binario']);
        }
    }
    $respuesta['id'] = guardar_problema($usuario['id'], $respuesta, $imagenRuta);
} catch (Throwable $e) {
    borrar_png_pizarron($imagenRuta);
    $respuesta['id'] = null;
}

responder($respuesta);
