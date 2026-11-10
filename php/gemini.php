<?php
// Utilidades para hablar con Gemini. Lo usan leer.php (imagen) y resolver.php (texto).
require_once __DIR__ . '/config.php';

// Envía una respuesta JSON al navegador y termina.
function responder($datos, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Limita las peticiones por usuario para cuidar la cuota de la API (1 cada $segundos).
// Debe llamarse con la sesión ya iniciada.
function limitar_peticiones($segundos = 4) {
    $ahora = time();
    $ultima = $_SESSION['ultima_peticion_ia'] ?? 0;
    if ($ahora - $ultima < $segundos) {
        responder(['ok' => false, 'mensaje' => 'Espera unos segundos antes de volver a intentarlo.'], 429);
    }
    $_SESSION['ultima_peticion_ia'] = $ahora;
}

// Llama a Gemini con UN modelo concreto. Devuelve [codigo_http, cuerpo, error_curl].
function llamar_modelo($modelo, $partes, $temperatura) {
    $cuerpo = [
        'contents' => [['parts' => $partes]],
        'generationConfig' => ['responseMimeType' => 'application/json', 'temperature' => $temperatura],
    ];
    $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . $modelo . ':generateContent');
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
    $error = curl_error($ch);
    curl_close($ch);
    return [$codigo, $respuesta, $error];
}

// Pide algo a Gemini con reintentos y modelo de respaldo cuando el servicio está saturado.
// Devuelve ['ok'=>true, 'json'=>array] o ['ok'=>false, 'mensaje'=>string].
function pedir_a_gemini($partes, $temperatura = 0.1) {
    if (GEMINI_API_KEY === 'PON_AQUI_TU_API_KEY') {
        return ['ok' => false, 'mensaje' => 'Falta poner la API key en php/config.php.'];
    }

    // Se prueba cada modelo en orden hasta que uno responda.
    $modelos = array_merge([GEMINI_MODELO], GEMINI_MODELOS_RESPALDO);
    $codigo = 0;
    $respuesta = false;
    $errorCurl = '';
    $fallos = [];   // código de error de cada modelo que falló

    foreach ($modelos as $modelo) {
        [$codigo, $respuesta, $errorCurl] = llamar_modelo($modelo, $partes, $temperatura);

        // 503 o fallo de red: puede ser momentáneo, se reintenta UNA vez con el mismo modelo
        if ($codigo === 503 || $respuesta === false) {
            sleep(1);
            [$codigo, $respuesta, $errorCurl] = llamar_modelo($modelo, $partes, $temperatura);
        }

        if ($codigo === 200) break;

        // 400/401/403: la petición o la clave están mal; otro modelo no lo arregla
        if (in_array($codigo, [400, 401, 403], true)) break;

        // 429 (cuota agotada), 404 (modelo retirado), 5xx...: probar con el siguiente modelo
        $fallos[] = $respuesta === false ? 0 : $codigo;
    }

    if ($codigo !== 200) {
        if (in_array($codigo, [400, 401, 403], true)) {
            return ['ok' => false, 'mensaje' => 'Gemini rechazó la petición (HTTP ' . $codigo . '). Revisa la API key en php/config.php.'];
        }
        if ($respuesta === false && count(array_unique($fallos)) === 1) {
            return ['ok' => false, 'mensaje' => 'No se pudo conectar con Gemini. Revisa tu conexión a internet. (' . $errorCurl . ')'];
        }
        if (count(array_diff($fallos, [429, 404])) === 0) {
            // Todos los modelos respondieron "cuota agotada" (o retirado)
            return ['ok' => false, 'mensaje' => 'Se agotó el límite diario gratuito de la IA en todos los modelos. '
                . 'Intenta más tarde o usa un ejemplo guardado.'];
        }
        return ['ok' => false, 'mensaje' => 'La IA está saturada en este momento. Intenta de nuevo en unos segundos.'];
    }

    // Juntar el texto de la respuesta (ignorando partes de "pensamiento")
    $datos = json_decode($respuesta, true);
    $texto = '';
    foreach ($datos['candidates'][0]['content']['parts'] ?? [] as $parte) {
        if (isset($parte['text']) && empty($parte['thought'])) $texto .= $parte['text'];
    }
    if ($texto === '') {
        return ['ok' => false, 'mensaje' => 'La IA no devolvió una respuesta. Intenta de nuevo.'];
    }

    $json = json_decode($texto, true);
    if (!is_array($json)) {
        return ['ok' => false, 'mensaje' => 'La IA devolvió una respuesta en un formato inesperado. Intenta de nuevo.'];
    }
    return ['ok' => true, 'json' => $json];
}
