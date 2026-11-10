<?php
require __DIR__ . '/php/auth.php';

if (usuario_actual()) { header('Location: index.php'); exit; }

$error = '';
$nombre = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $clave  = $_POST['clave'] ?? '';
    $clave2 = $_POST['clave2'] ?? '';

    // Validaciones
    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $error = 'Escribe tu nombre (máximo 80 caracteres).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
        $error = 'El correo no es válido.';
    } elseif (strlen($clave) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($clave !== $clave2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $bd = conectar_bd();

        // ¿Ya existe ese correo?
        $consulta = $bd->prepare('SELECT id FROM usuarios WHERE email = ?');
        $consulta->bind_param('s', $email);
        $consulta->execute();
        if ($consulta->get_result()->num_rows > 0) {
            $error = 'Ese correo ya está registrado.';
        } else {
            // Guardar con la contraseña cifrada (nunca en texto plano)
            $hash = password_hash($clave, PASSWORD_DEFAULT);
            $insertar = $bd->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)');
            $insertar->bind_param('sss', $nombre, $email, $hash);
            $insertar->execute();

            iniciar_sesion_usuario($bd->insert_id, $nombre, 'usuario');
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SolveIt — Registro</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body class="centrado">
  <main class="tarjeta">
    <div class="marca">SolveIt</div>
    <h1>Crear cuenta</h1>
    <p class="subtitulo">Guarda tu historial y practica con ejercicios nuevos.</p>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
      <label>Nombre <input type="text" name="nombre" value="<?= e($nombre) ?>" required maxlength="80"></label>
      <label>Correo <input type="email" name="email" value="<?= e($email) ?>" required maxlength="120"></label>
      <label>Contraseña <input type="password" name="clave" required minlength="6"></label>
      <label>Repite la contraseña <input type="password" name="clave2" required minlength="6"></label>
      <button type="submit">Registrarme</button>
    </form>
    <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
  </main>
</body>
</html>
