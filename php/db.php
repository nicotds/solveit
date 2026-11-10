<?php
// Conexión a MySQL con mysqli. Se incluye con require desde las demás páginas.
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // errores como excepciones

function conectar_bd() {
    static $conexion = null;          // una sola conexión por petición
    if ($conexion === null) {
        try {
            $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            $conexion->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            die('No se pudo conectar a la base de datos. ¿Está encendido MySQL y importaste sql/solveit.sql?');
        }
    }
    return $conexion;
}
