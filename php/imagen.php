<?php
// Validación y guardado de las imágenes del pizarrón (PNG en base64).

const IMAGEN_MAX_BASE64 = 5 * 1024 * 1024;   // 5 MB

// Valida "data:image/png;base64,...." y devuelve [datos, null] o [null, 'mensaje de error'].
// datos = ['base64' => ..., 'binario' => ...]
function decodificar_png_dataurl($dataUrl)
{
    $prefijo = 'data:image/png;base64,';
    if (!is_string($dataUrl) || strpos($dataUrl, $prefijo) !== 0) {
        return [null, 'La imagen debe ser un PNG en base64.'];
    }
    $base64 = substr($dataUrl, strlen($prefijo));
    if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $base64) || strlen($base64) > IMAGEN_MAX_BASE64) {
        return [null, 'Imagen inválida o demasiado grande.'];
    }
    $binario = base64_decode($base64, true);
    if ($binario === false) {
        return [null, 'Imagen inválida.'];
    }
    // Comprobar que de verdad es un PNG (no basta con el prefijo) y de tamaño razonable
    $info = @getimagesizefromstring($binario);
    if (!$info || $info[2] !== IMAGETYPE_PNG || $info[0] > 4000 || $info[1] > 4000) {
        return [null, 'El archivo no es un PNG válido.'];
    }
    return [['base64' => $base64, 'binario' => $binario], null];
}

// Guarda el PNG con un nombre aleatorio. Devuelve la ruta relativa ("uploads/pizarrones/ab12....png") o null.
function guardar_png_pizarron($binario)
{
    $carpeta = __DIR__ . '/../uploads/pizarrones';
    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
        return null;
    }
    $nombre = bin2hex(random_bytes(8)) . '.png';
    if (file_put_contents($carpeta . '/' . $nombre, $binario) === false) {
        return null;
    }
    return 'uploads/pizarrones/' . $nombre;
}

// Borra una imagen guardada. Solo acepta rutas con el formato exacto que genera guardar_png_pizarron().
function borrar_png_pizarron($ruta)
{
    if (is_string($ruta) && preg_match('#^uploads/pizarrones/[a-f0-9]{16}\.png$#', $ruta)) {
        $archivo = __DIR__ . '/../' . $ruta;
        if (is_file($archivo)) {
            @unlink($archivo);
        }
    }
}
