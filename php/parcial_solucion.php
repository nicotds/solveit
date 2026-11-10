<?php
// Fragmento HTML común: panel de solución + panel de práctica. js/solucion.js lo rellena.
// $permitirPractica: false en el modo demo (no hay IA para generar ejercicios).
$permitirPractica = $permitirPractica ?? true;
?>
    <!-- Solución: sello de verificación, resultado y pasos -->
    <section id="panelSolucion" class="panel" hidden>
      <h2>Solución</h2>
      <div id="sello" class="sello"></div>
      <div id="resultadoFinal" class="resultado"></div>
      <details id="detallePasos" open>
        <summary id="resumenPasos">Pasos</summary>
        <ol id="listaPasos" class="pasos"></ol>
      </details>
      <p id="resumenFinal" class="resumen-final"></p>
      <p id="estadoGuardado" class="ayuda"></p>
      <?php if ($permitirPractica): ?>
        <button id="btnSimilar" class="herr">🎲 Dame un ejercicio parecido</button>
        <p id="avisoPractica" class="error" hidden></p>
      <?php endif; ?>
    </section>

    <?php if ($permitirPractica): ?>
    <!-- Práctica: la corrección la hace PHP -->
    <section id="panelPractica" class="panel" hidden>
      <h2>Practica con este ejercicio</h2>
      <div id="ejercicioPractica" class="vista-katex"></div>
      <label for="respuestaPractica">Tu respuesta:</label>
      <input type="text" id="respuestaPractica" autocomplete="off" placeholder="Ejemplo: 4   (o 2, 3 si hay dos soluciones)">
      <p class="ayuda">Puedes escribir fracciones como <code>1/3</code>. Con decimales usa punto. Si hay dos soluciones, sepáralas con coma.</p>
      <div class="fila-resolver">
        <button id="btnComprobar">Comprobar</button>
        <button id="btnRevelar" class="herr">Ver la respuesta</button>
        <button id="btnOtro" class="herr">Otro ejercicio</button>
        <a id="enlaceResolverEste" class="enlace-boton" href="#">Resolver este ejercicio con SolveIt</a>
      </div>
      <p id="resultadoPractica" class="resultado-practica" hidden></p>
    </section>
    <?php endif; ?>
