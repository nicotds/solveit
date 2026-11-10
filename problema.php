<?php
// Ver un problema guardado (?id=). Solo se puede abrir si es del usuario en sesión.
require __DIR__ . '/php/auth.php';
require __DIR__ . '/php/barra.php';
require __DIR__ . '/php/problemas.php';
requerir_login();
$usuario = usuario_actual();

$id = (int)($_GET['id'] ?? 0);
$fila = $id > 0 ? obtener_problema($id, $usuario['id']) : null;   // filtra por dueño

if ($fila === null) {
    http_response_code(404);
}
$solucion = $fila ? problema_a_solucion($fila) : null;

// Datos para el JavaScript, escapados para que no puedan cerrar la etiqueta <script>
$datosJson = $solucion ? json_encode($solucion, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) : 'null';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SolveIt — Problema</title>
<link rel="stylesheet" href="lib/katex/katex.min.css">
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <?php barra_superior($usuario, 'problema'); ?>

  <main class="contenido">
    <p><a href="historial.php">← Volver al historial</a></p>

    <?php if (!$fila): ?>
      <div class="panel vacio">No encontramos ese problema. Puede que ya lo hayas borrado.</div>
    <?php else: ?>
      <h2>Problema guardado</h2>
      <p class="ayuda">Resuelto el <?= e(date('d/m/Y H:i', strtotime($fila['creado_en']))) ?> · explicación <?= e($fila['nivel']) ?></p>

      <div class="panel">
        <div id="exprProblema" class="vista-katex"></div>
        <?php if ($fila['imagen_ruta']): ?>
          <details>
            <summary>Ver lo que escribiste en el pizarrón</summary>
            <img class="vista" src="<?= e($fila['imagen_ruta']) ?>" alt="Lo escrito en el pizarrón">
          </details>
        <?php endif; ?>
      </div>

      <?php $permitirPractica = true; require __DIR__ . '/php/parcial_solucion.php'; ?>

      <div class="acciones-pie">
        <a class="enlace-boton" href="index.php?e=<?= e(rawurlencode($fila['expresion'])) ?>">Resolver de nuevo</a>
        <form method="post" action="php/borrar.php" onsubmit="return confirm('¿Borrar este problema? No se puede deshacer.');">
          <input type="hidden" name="id" value="<?= (int)$fila['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(token_csrf()) ?>">
          <button class="herr peligro" type="submit">Borrar</button>
        </form>
      </div>
    <?php endif; ?>
  </main>

  <script src="lib/katex/katex.min.js"></script>
  <script src="js/solucion.js"></script>
  <script src="js/practica.js"></script>
  <script>
    const DATOS = <?= $datosJson ?>;
    if (DATOS) {
      renderKatex(document.getElementById('exprProblema'), aLatex(DATOS.expresion), true);
      mostrarSolucionEn(DATOS);
    }
  </script>
</body>
</html>
