<?php
// Historial: lista los problemas del usuario en sesión (y solo los suyos).
require __DIR__ . '/php/auth.php';
require __DIR__ . '/php/barra.php';
requerir_login();
$usuario = usuario_actual();
$bd = conectar_bd();

// Estadísticas personales
$q = $bd->prepare("SELECT COUNT(*) AS total,
                          COALESCE(SUM(verificacion = 'verificado'), 0) AS verificados,
                          COALESCE(SUM(tipo = 'ecuacion'), 0) AS ecuaciones,
                          COALESCE(SUM(tipo = 'operacion'), 0) AS operaciones
                   FROM problemas WHERE usuario_id = ?");
$q->bind_param('i', $usuario['id']);
$q->execute();
$est = $q->get_result()->fetch_assoc();
$porcentaje = $est['total'] > 0 ? round(100 * $est['verificados'] / $est['total']) : 0;

// Lista (los 200 más recientes)
$q = $bd->prepare('SELECT id, expresion, tipo, resultado_texto, verificacion, nivel, imagen_ruta, creado_en
                   FROM problemas WHERE usuario_id = ? ORDER BY id DESC LIMIT 200');
$q->bind_param('i', $usuario['id']);
$q->execute();
$problemas = $q->get_result()->fetch_all(MYSQLI_ASSOC);

$etiquetas = [
    'verificado'    => ['✓ Verificado', 'bueno'],
    'inconsistente' => ['⚠ No coincidió', 'malo'],
    'no_verificado' => ['Sin verificar', 'medio'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SolveIt — Historial</title>
<link rel="stylesheet" href="lib/katex/katex.min.css">
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php barra_superior($usuario, 'historial'); ?>

  <main class="contenido">
    <h2>Mi historial</h2>

    <?php if (isset($_GET['borrado'])): ?><p class="exito">Problema borrado.</p><?php endif; ?>
    <?php if (isset($_GET['error'])): ?><p class="error">No se pudo borrar ese problema.</p><?php endif; ?>

    <div class="estadisticas">
      <div><b><?= (int)$est['total'] ?></b><span>problemas</span></div>
      <div><b><?= $porcentaje ?>%</b><span>verificados</span></div>
      <div><b><?= (int)$est['ecuaciones'] ?></b><span>ecuaciones</span></div>
      <div><b><?= (int)$est['operaciones'] ?></b><span>operaciones</span></div>
    </div>

    <?php if (!$problemas): ?>
      <div class="panel vacio">
        Aún no has resuelto nada. <a href="index.php">Ve al pizarrón</a> y escribe tu primera ecuación.
      </div>
    <?php endif; ?>

    <?php foreach ($problemas as $p): ?>
      <?php [$textoEtiqueta, $claseEtiqueta] = $etiquetas[$p['verificacion']]; ?>
      <article class="fila-historial">
        <div class="fh-expr">
          <span class="expr" data-expr="<?= e($p['expresion']) ?>"><?= e($p['expresion']) ?></span>
          <small>= <?= e($p['resultado_texto']) ?></small>
        </div>
        <div class="fh-meta">
          <span class="insignia <?= $claseEtiqueta ?>"><?= $textoEtiqueta ?></span>
          <small><?= e(date('d/m/Y H:i', strtotime($p['creado_en']))) ?> · <?= e($p['nivel']) ?></small>
        </div>
        <div class="fh-acciones">
          <a class="enlace-boton" href="problema.php?id=<?= (int)$p['id'] ?>">Abrir</a>
          <form method="post" action="php/borrar.php"
                onsubmit="return confirm('¿Borrar este problema? No se puede deshacer.');">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="csrf" value="<?= e(token_csrf()) ?>">
            <button class="herr peligro" type="submit">Borrar</button>
          </form>
        </div>
      </article>
    <?php endforeach; ?>
  </main>

  <script src="lib/katex/katex.min.js"></script>
  <script src="js/solucion.js"></script>
  <script>renderizarExpresiones('.expr');</script>
</body>
</html>
