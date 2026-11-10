<?php
// Página principal (protegida): pizarrón + lectura con IA + confirmación + solución.
// Con ?demo=1 funciona SIN IA, con ejemplos guardados (Plan B de la presentación).
require __DIR__ . '/php/auth.php';
require __DIR__ . '/php/barra.php';
require __DIR__ . '/php/demo_datos.php';
requerir_login();
$usuario = usuario_actual();
$demo = ($_GET['demo'] ?? '') === '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SolveIt</title>
<link rel="stylesheet" href="lib/katex/katex.min.css">
<link rel="stylesheet" href="css/estilos.css">
</head>
<body data-demo="<?= $demo ? '1' : '0' ?>">
  <?php barra_superior($usuario, 'pizarron'); ?>

  <main class="contenido">
    <?php if ($demo): ?>
      <div class="aviso-demo">
        <strong>Modo demo (sin IA).</strong> Elige un ejemplo guardado; se resuelve sin conectarse a Gemini
        y PHP lo verifica igual. <a href="index.php">Salir del modo demo</a>
      </div>
      <h2>1. Elige un ejemplo</h2>
      <div class="ejemplos">
        <?php foreach (demo_ejemplos() as $ej): ?>
          <button class="herr ejemplo-demo" data-expr="<?= e($ej['expresion']) ?>"><?= e($ej['expresion']) ?></button>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <h2>1. Escribe tu operación o ecuación</h2>

      <div class="herramientas">
        <button id="btnLapiz" class="herr"><?= icono('lapiz') ?> Lápiz</button>
        <button id="btnBorrador" class="herr"><?= icono('borrador') ?> Borrador</button>
        <button id="btnDeshacer" class="herr"><?= icono('deshacer') ?> Deshacer</button>
        <button id="btnLimpiar" class="herr"><?= icono('limpiar') ?> Limpiar</button>
      </div>

      <!-- Las líneas guía van en el CSS de este contenedor, NO dentro del canvas -->
      <div class="marco-pizarron">
        <canvas id="lienzo" width="800" height="350"></canvas>
      </div>

      <p class="acciones">
        <button id="btnLeer">Leer</button>
        <button id="btnTeclado" class="herr"><?= icono('teclado') ?> Escribirla con el teclado</button>
        <a href="index.php?demo=1" class="enlace-chico">Modo demo</a>
      </p>
    <?php endif; ?>
    <?php if ($demo): ?>
      <p><button id="btnTeclado" class="herr"><?= icono('teclado') ?> Escribir otra con el teclado</button></p>
    <?php endif; ?>
    <p id="avisoLectura" class="error" hidden></p>

    <!-- Paso de confirmación: la IA solo propone, el usuario decide -->
    <section id="panelConfirmar" class="panel" hidden>
      <h2>2. Revisa<?= $demo ? '' : ' lo que se leyó' ?></h2>
      <div id="cajaDudas" class="dudas" hidden>
        <strong>Revisa estos puntos:</strong>
        <ul id="listaDudas"></ul>
      </div>
      <label for="campoExpresion">Si algo está mal, corrígelo aquí:</label>
      <input type="text" id="campoExpresion" autocomplete="off" spellcheck="false"
             placeholder="Ejemplo: 2*x+3=11">
      <p id="errorCampo" class="error" hidden></p>
      <div id="vistaPrevia" class="vista-katex"></div>
      <p class="ayuda">Usa <code>*</code> para multiplicar, <code>/</code> para dividir, <code>^</code> para potencias y <code>sqrt(…)</code> para raíces.</p>
      <div class="fila-resolver">
        <button id="btnResolver" disabled>Resolver</button>
        <span class="nivel">Explicación:
          <label><input type="radio" name="nivel" value="basico" checked> Básica</label>
          <label><input type="radio" name="nivel" value="avanzado"> Avanzada</label>
        </span>
      </div>
      <p id="avisoResolver" class="error" hidden></p>
    </section>

    <?php $permitirPractica = !$demo; require __DIR__ . '/php/parcial_solucion.php'; ?>
  </main>

  <script src="lib/katex/katex.min.js"></script>
  <script src="js/solucion.js"></script>
  <?php if (!$demo): ?><script src="js/pizarron.js"></script><?php endif; ?>
  <script src="js/solver.js"></script>
  <script src="js/practica.js"></script>
</body>
</html>
