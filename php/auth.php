<?php
// Manejo de sesiones. Se incluye al inicio de cada página que lo necesite.
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Devuelve los datos del usuario con sesión iniciada, o null si no hay.
function usuario_actual() {
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }
    return [
        'id'     => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'rol'    => $_SESSION['usuario_rol'],
    ];
}

// Páginas protegidas: si no hay sesión, redirige al login.
function requerir_login() {
    if (usuario_actual() === null) {
        header('Location: login.php');
        exit;
    }
}

// Guarda al usuario en la sesión (se llama después de registrarse o iniciar sesión).
function iniciar_sesion_usuario($id, $nombre, $rol) {
    session_regenerate_id(true); // evita la fijación de sesión
    $_SESSION['usuario_id']     = (int)$id;
    $_SESSION['usuario_nombre'] = $nombre;
    $_SESSION['usuario_rol']    = $rol;
}

// Protección CSRF para acciones que cambian datos (borrar): el formulario lleva un token
// secreto de la sesión; una página ajena no puede conocerlo.
function token_csrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function verificar_csrf($token) {
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// Atajo para imprimir texto de forma segura en HTML.
function e($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}
