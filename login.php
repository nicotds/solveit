<?php
require __DIR__ . '/php/auth.php';

if (usuario_actual()) { header('Location: index.php'); exit; }

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $clave = $_POST['clave'] ?? '';

    $bd = conectar_bd();
    $consulta = $bd->prepare('SELECT id, nombre, password_hash, rol FROM usuarios WHERE email = ?');
    $consulta->bind_param('s', $email);
    $consulta->execute();
    $usuario = $consulta->get_result()->fetch_assoc();

    // Mismo mensaje si falla el correo o la contraseña (no revela cuál está mal)
    if ($usuario && password_verify($clave, $usuario['password_hash'])) {
        iniciar_sesion_usuario($usuario['id'], $usuario['nombre'], $usuario['rol']);
        header('Location: index.php');
        exit;
    }
    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SolveIt — Iniciar sesión</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body class="centrado">
  <main class="tarjeta">
    <div class="marca">SolveIt</div>
    <h1>Iniciar sesión</h1>
    <p class="subtitulo">Entra para resolver y practicar tus ecuaciones.</p>
    <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
    <form method="post">
      <label>Correo <input type="email" name="email" value="<?= e($email) ?>" required></label>
      <label>Contraseña <input type="password" name="clave" required></label>
      <button type="submit">Entrar</button>
    </form>
    <p>¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
  </main>
</body>
</html>
