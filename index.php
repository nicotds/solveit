<?php
// Página principal (protegida): pizarrón. La lectura con IA llega en la Fase 4.
require __DIR__ . '/php/auth.php';
requerir_login();
$usuario = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SolveIt</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>
  <header class="barra">
    <strong>SolveIt</strong>
    <span>Hola, <?= e($usuario['nombre']) ?> · <a href="logout.php">Cerrar sesión</a></span>
  </header>

  <main class="contenido">
    <h2>Escribe tu operación o ecuación</h2>

    <div class="herramientas">
      <button id="btnLapiz" class="herr">✏️ Lápiz</button>
      <button id="btnBorrador" class="herr">🧽 Borrador</button>
      <button id="btnDeshacer" class="herr">↩️ Deshacer</button>
      <button id="btnLimpiar" class="herr">🗑️ Limpiar</button>
    </div>

    <!-- Las líneas guía van en el CSS de este contenedor, NO dentro del canvas -->
    <div class="marco-pizarron">
      <canvas id="lienzo" width="800" height="350"></canvas>
    </div>

    <p class="acciones">
      <!-- Provisional (Fase 3): comprueba lo que se enviaría a la IA. Se reemplaza por "Leer" en la Fase 4 -->
      <button id="btnVista">Ver imagen que se enviaría</button>
      <span id="aviso" class="error" hidden></span>
    </p>
    <img id="vista" alt="" hidden class="vista">
  </main>

  <script src="js/pizarron.js"></script>
  <script>
    // Provisional de la Fase 3
    const aviso = document.getElementById('aviso');
    const vista = document.getElementById('vista');
    document.getElementById('btnVista').onclick = () => {
      if (Pizarron.estaVacio()) {
        aviso.textContent = 'Primero escribe algo en el pizarrón';
        aviso.hidden = false;
        vista.hidden = true;
        return;
      }
      aviso.hidden = true;
      vista.src = Pizarron.exportarPNG();
      vista.hidden = false;
    };
  </script>
</body>
</html>
