<?php
// Panel de administración (solo rol "admin"): usuarios y estadísticas generales.
require __DIR__ . '/php/auth.php';
require __DIR__ . '/php/barra.php';
requerir_login();
$usuario = usuario_actual();
if ($usuario['rol'] !== 'admin') {
    http_response_code(403);
    die('Solo para administradores.');
}
$bd = conectar_bd();

$g = $bd->query("SELECT COUNT(*) AS total,
                        COALESCE(SUM(verificacion = 'verificado'), 0) AS verificados,
                        COALESCE(SUM(verificacion = 'inconsistente'), 0) AS inconsistentes
                 FROM problemas")->fetch_assoc();
$usuarios = $bd->query("SELECT u.id, u.nombre, u.email, u.rol, u.creado_en,
                               COUNT(p.id) AS problemas,
                               COALESCE(SUM(p.verificacion = 'verificado'), 0) AS verificados
                        FROM usuarios u LEFT JOIN problemas p ON p.usuario_id = u.id
                        GROUP BY u.id ORDER BY u.id")->fetch_all(MYSQLI_ASSOC);
$totalUsuarios = count($usuarios);
$porcentaje = $g['total'] > 0 ? round(100 * $g['verificados'] / $g['total']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SolveIt — Administración</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php barra_superior($usuario, 'admin'); ?>
  <main class="contenido">
    <h2>Administración</h2>
    <div class="estadisticas">
      <div><b><?= $totalUsuarios ?></b><span>usuarios</span></div>
      <div><b><?= (int)$g['total'] ?></b><span>problemas</span></div>
      <div><b><?= $porcentaje ?>%</b><span>verificados</span></div>
      <div><b><?= (int)$g['inconsistentes'] ?></b><span>no coincidieron</span></div>
    </div>

    <div class="panel tabla-scroll">
      <table class="tabla">
        <tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Registro</th><th>Problemas</th><th>Verificados</th></tr>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= e($u['nombre']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= e($u['rol']) ?></td>
            <td><?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></td>
            <td><?= (int)$u['problemas'] ?></td>
            <td><?= (int)$u['verificados'] ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <p class="ayuda">Para volver administrador a un usuario: en phpMyAdmin, tabla <code>usuarios</code>, cambia su columna <code>rol</code> a <code>admin</code>.</p>
  </main>
</body>
</html>
