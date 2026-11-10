<?php
// =====================================================================
// SolveIt — Landing page pública (inicio.php)
// ---------------------------------------------------------------------
// Es la "puerta de entrada" del proyecto. A diferencia de index.php,
// esta página NO llama a requerir_login(): cualquiera puede verla.
// Solo revisa si hay sesión para cambiar los botones:
//   · sin sesión → "Iniciar sesión" y "Crear cuenta"
//   · con sesión → "Cerrar sesión" e "Ir al pizarrón" (y "Ver demo")
//
// Estilos: css/landing.css  ·  Animaciones: js/landing.js
// Todo es local (fuentes en fonts/): funciona sin internet.
// =====================================================================
require __DIR__ . '/php/auth.php';    // sesión + e() (no redirige a nadie)
require __DIR__ . '/php/iconos.php';  // los mismos iconos del pizarrón de la app

$usuario   = usuario_actual();        // null si nadie inició sesión
$conSesion = $usuario !== null;

// Datos del pie de página: complétalos con los de tu equipo.
$materia = '[materia]';
$autores = '[nombres de los autores]';

// Fondo del héroe: 'matematico' (por defecto) o 'color' (modo B, más sobrio).
// Para probarlo: inicio.php?fondo=color
$fondo = (($_GET['fondo'] ?? '') === 'color') ? 'color' : 'matematico';

// Botones de la barra según la sesión.
if ($conSesion) {
    $btnSecundario = ['url' => 'logout.php',   'texto' => 'Cerrar sesión'];
    $btnPrincipal  = ['url' => 'index.php',    'texto' => 'Ir al pizarrón'];
} else {
    $btnSecundario = ['url' => 'login.php',    'texto' => 'Iniciar sesión'];
    $btnPrincipal  = ['url' => 'registro.php', 'texto' => 'Crear cuenta'];
}

// Segundo botón del cierre. El modo demo (index.php?demo=1) exige sesión,
// porque index.php llama a requerir_login(); por eso solo se ofrece con sesión.
$ctaSecundario = $conSesion
    ? ['url' => 'index.php?demo=1', 'texto' => 'Ver demo']
    : ['url' => 'login.php',        'texto' => 'Iniciar sesión'];

// Nombre que aparece dentro de la maqueta (si hay sesión, el del usuario).
$nombreMaqueta  = $conSesion ? $usuario['nombre'] : 'Invitado';
$inicialMaqueta = mb_strtoupper(mb_substr(trim($nombreMaqueta), 0, 1));

// Trazos de "2x + 3 = 11" escritos "a mano" (SVG, viewBox 520 × 160).
// Cada elemento es un movimiento del lápiz; js/landing.js los dibuja en orden.
$trazosEcuacion = [
    'M30 62 C33 40 52 30 68 35 C86 41 86 62 74 78 C62 94 44 108 30 124 C50 122 70 121 92 122', // 2
    'M112 80 C122 92 140 112 156 126',                                                        // x
    'M157 78 C144 92 128 110 112 125',                                                        // x
    'M206 76 C205 92 205 108 204 124',                                                        // + (vertical)
    'M182 101 C196 100 212 99 228 99',                                                        // + (horizontal)
    'M262 48 C272 34 300 34 304 52 C307 68 290 78 276 80 C296 80 312 94 308 110 C304 128 276 134 258 120', // 3
    'M336 90 C352 89 370 88 388 88',                                                          // = (arriba)
    'M338 112 C354 111 372 110 390 110',                                                      // = (abajo)
    'M420 56 C428 50 436 44 442 38 C441 66 440 96 439 126',                                   // 1
    'M466 56 C474 50 482 44 488 38 C487 66 486 96 485 126',                                   // 1
];

// Ejemplos de la cinta del cierre: son los del modo demo (php/demo_datos.php).
$ejemplos = [
    '2<i>x</i> + 3 = 11',
    '3<i>x</i> − 5 = 10',
    '<i>x</i>/2 = 7',
    '(2 + 3) ⋅ 4',
    '<i>x</i><sup>2</sup> − 5<i>x</i> + 6 = 0',
    '√16 + 2<sup>3</sup>',
];

// Iconos extra que solo usa la landing (trazos estilo Lucide; heredan el color del texto).
function lp_icono($nombre)
{
    $trazos = [
        'check'     => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'alerta'    => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'candado'   => '<rect width="14" height="10" x="5" y="11" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'panel'     => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>',
        'atras'     => '<path d="m15 18-6-6 6-6"/>',
        'adelante'  => '<path d="m9 18 6-6-6-6"/>',
        'recargar'  => '<path d="M21 12a9 9 0 1 1-2.64-6.36L21 8"/><path d="M21 3v5h-5"/>',
        'compartir' => '<path d="M12 3v12"/><path d="m8 7 4-4 4 4"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/>',
        'mas'       => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'copiar'    => '<rect width="13" height="13" x="8" y="8" rx="2"/><path d="M4 16V6a2 2 0 0 1 2-2h10"/>',
        'ojo'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        'escudo'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'usuario'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m16 11 2 2 4-4"/>',
        'capas'     => '<path d="m12 2 10 5-10 5L2 7Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',
        'repetir'   => '<path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="m7 22-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/>',
        'pizarron'  => '<rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/>',
    ];
    if (!isset($trazos[$nombre])) {
        return '';
    }
    return '<svg class="icono" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"'
         . ' stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         . $trazos[$nombre] . '</svg>';
}

// Logo: una raíz cuadrada (como en la app) con un destello de 4 puntas al final.
$logo = '<svg class="lp-marca-icono" viewBox="0 0 32 32" aria-hidden="true" focusable="false">'
      . '<path d="M3 17.5 7 15.5l5.5 10.5L19 6.5h5.5" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>'
      . '<path d="M28.5 3.2c.36 2.05 1.15 2.84 3.2 3.2-2.05.36-2.84 1.15-3.2 3.2-.36-2.05-1.15-2.84-3.2-3.2 2.05-.36 2.84-1.15 3.2-3.2Z" fill="currentColor"/>'
      . '</svg>';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SolveIt — Escribe tu ecuación. Entiende cada paso.</title>
<meta name="description" content="SolveIt: escribe a mano una operación o ecuación, confirma lo que leyó la IA y obtén la solución paso a paso, comprobada de forma independiente con PHP.">
<meta name="theme-color" content="#1d1828">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%231d1828'/%3E%3Cpath d='M6 17.5 9 16l4 8 5.5-15H25' fill='none' stroke='%23ece8ff' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
<link rel="preload" href="fonts/newsreader-variable.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="fonts/inter-variable.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="css/landing.css">
<!-- Marca que hay JavaScript: solo entonces se ocultan los elementos que aparecen con animación -->
<script>document.documentElement.classList.add('lp-js');</script>
</head>
<body class="lp lp-fondo-<?= $fondo ?>">
  <a class="lp-saltar" href="#contenido">Saltar al contenido</a>

  <!-- ============ 1. Barra fija: logo + píldora de vidrio + botones ============ -->
  <header class="lp-barra" id="barra">
    <div class="lp-barra-in">
      <a class="lp-marca" href="#inicio"><?= $logo ?><span>SolveIt</span></a>

      <nav class="lp-nav" aria-label="Secciones">
        <span class="lp-nav-resalte" aria-hidden="true"></span>
        <a href="#inicio">Inicio</a>
        <a href="#como-funciona">Cómo funciona</a>
        <a href="#caracteristicas">Características</a>
        <a href="#demo">Demo</a>
      </nav>

      <div class="lp-acciones">
        <a class="lp-btn lp-btn--fantasma" href="<?= $btnSecundario['url'] ?>"><?= $btnSecundario['texto'] ?></a>
        <a class="lp-btn lp-btn--claro" href="<?= $btnPrincipal['url'] ?>"><?= $btnPrincipal['texto'] ?></a>
      </div>

      <!-- Solo en pantallas chicas -->
      <button class="lp-menu-btn" type="button" aria-expanded="false" aria-controls="menuMovil" aria-label="Abrir menú">
        <span aria-hidden="true"></span><span aria-hidden="true"></span>
      </button>
    </div>

    <div class="lp-menu" id="menuMovil" hidden>
      <nav aria-label="Secciones (menú)">
        <a href="#inicio">Inicio</a>
        <a href="#como-funciona">Cómo funciona</a>
        <a href="#caracteristicas">Características</a>
        <a href="#demo">Demo</a>
      </nav>
      <div class="lp-menu-acciones">
        <a class="lp-btn lp-btn--vidrio lp-btn--grande" href="<?= $btnSecundario['url'] ?>"><?= $btnSecundario['texto'] ?></a>
        <a class="lp-btn lp-btn--claro lp-btn--grande" href="<?= $btnPrincipal['url'] ?>"><?= $btnPrincipal['texto'] ?></a>
      </div>
    </div>
  </header>

  <main id="contenido">
    <!-- La "escena" agrupa el héroe y "Así funciona". Su fondo (.lp-escenario) se queda
         fijo con position: sticky mientras el contenido pasa por encima. -->
    <div class="lp-escena">
      <div class="lp-escenario" aria-hidden="true">
        <!-- Fondo del héroe (se desvanece al hacer scroll) -->
        <div class="lp-fondo">
          <div class="lp-fondo-base"></div>
          <div class="lp-fondo-cuadricula"></div>
          <div class="lp-fondo-eje"></div>
          <!-- Curva principal: una parábola (trayectoria) que se dibuja sola -->
          <svg class="lp-fondo-curva" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" focusable="false">
            <defs>
              <linearGradient id="lpGradCurva" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0" stop-color="#9b82ff" stop-opacity="0"/>
                <stop offset=".22" stop-color="#b9a8ff"/>
                <stop offset=".5" stop-color="#f4f1ff"/>
                <stop offset=".78" stop-color="#b9a8ff"/>
                <stop offset="1" stop-color="#9b82ff" stop-opacity="0"/>
              </linearGradient>
            </defs>
            <g fill="none" stroke="url(#lpGradCurva)" stroke-linecap="round">
              <path class="lp-curva lp-curva--halo" pathLength="1" d="M-60 940 Q720 -420 1500 940"/>
              <path class="lp-curva lp-curva--brillo" pathLength="1" d="M-60 940 Q720 -420 1500 940"/>
              <path class="lp-curva lp-curva--nucleo" pathLength="1" d="M-60 940 Q720 -420 1500 940"/>
            </g>
          </svg>
          <!-- Símbolos flotantes y punto luminoso (los dibuja js/landing.js) -->
          <canvas class="lp-fondo-lienzo" id="lienzoFondo"></canvas>
          <div class="lp-fondo-orbe"></div>
          <div class="lp-fondo-velo"></div>
        </div>
        <!-- Esfera violeta que sube al llegar a "Así funciona" -->
        <div class="lp-esfera">
          <div class="lp-esfera-cuerpo"></div>
          <div class="lp-esfera-luz"></div>
        </div>
      </div>

      <!-- ============ 2. Héroe ============ -->
      <section class="lp-heroe" id="inicio" aria-labelledby="tituloHeroe" data-lp-pausa>
        <div class="lp-heroe-pin">
          <div class="lp-heroe-contenido">
            <p class="lp-insignia">
              <span class="lp-insignia-tenue">Proyecto académico</span>
              <span class="lp-punto" aria-hidden="true"></span>
              <span class="lp-brillo">Verificado con PHP</span>
            </p>
            <h1 class="lp-titulo" id="tituloHeroe" data-lp-palabras>
              <span class="lp-linea">Escribe tu</span>
              <span class="lp-linea">ecuación. Entiende</span>
              <span class="lp-linea">cada paso.</span>
            </h1>
            <p class="lp-heroe-sub">Dibuja una operación en el pizarrón, confirma lo que leyó la IA y obtén la solución explicada y comprobada de forma independiente.</p>
          </div>
        </div>
      </section>

      <!-- ============ 3. Así funciona: esfera + maqueta de SolveIt ============ -->
      <section class="lp-asi" id="demo" aria-labelledby="tituloAsi">
        <p class="lp-insignia lp-insignia--clara" data-lp-revelar><span class="lp-punto lp-punto--claro" aria-hidden="true"></span> Así funciona</p>
        <h2 class="lp-titulo-2" id="tituloAsi" data-lp-palabras="vista">
          <span class="lp-linea">De tu trazo</span>
          <span class="lp-linea">a una solución</span>
          <span class="lp-linea">verificada</span>
        </h2>

        <!-- Maqueta hecha con HTML y CSS (no es una captura). El ejemplo es estático. -->
        <div class="lp-maqueta" id="maqueta">
          <div class="lp-maqueta-in">
            <div class="lp-ventana">
              <div class="lp-ventana-barra" aria-hidden="true">
                <div class="lp-ventana-izq">
                  <span class="lp-semaforo"><i></i><i></i><i></i></span>
                  <span class="lp-ventana-iconos"><?= lp_icono('panel') ?><?= lp_icono('atras') ?><?= lp_icono('adelante') ?></span>
                </div>
                <div class="lp-url"><?= lp_icono('candado') ?><span>localhost/solveit</span><?= lp_icono('recargar') ?></div>
                <div class="lp-ventana-der">
                  <span class="lp-ventana-iconos"><?= lp_icono('compartir') ?><?= lp_icono('mas') ?><?= lp_icono('copiar') ?></span>
                </div>
              </div>

              <div class="lp-app-barra" aria-hidden="true">
                <span class="lp-app-marca"><?= $logo ?> SolveIt</span>
                <span class="lp-app-nav">
                  <span class="is-activo"><?= lp_icono('pizarron') ?> Pizarrón</span>
                  <span><?= lp_icono('repetir') ?> Historial</span>
                </span>
                <span class="lp-app-usuario"><span class="lp-avatar"><?= e($inicialMaqueta) ?></span><?= e($nombreMaqueta) ?></span>
              </div>

              <div class="lp-app">
                <div class="lp-tabs" role="tablist" aria-label="Pasos de SolveIt (ejemplo)">
                  <button class="lp-tab" type="button" role="tab" id="tabEscribir" aria-controls="panelEscribir" aria-selected="true">Escribir<span class="lp-tab-progreso" aria-hidden="true"></span></button>
                  <button class="lp-tab" type="button" role="tab" id="tabConfirmar" aria-controls="panelConfirmar" aria-selected="false" tabindex="-1">Confirmar<span class="lp-tab-progreso" aria-hidden="true"></span></button>
                  <button class="lp-tab" type="button" role="tab" id="tabResolver" aria-controls="panelResolver" aria-selected="false" tabindex="-1">Resolver<span class="lp-tab-progreso" aria-hidden="true"></span></button>
                </div>

                <div class="lp-paneles">
                  <!-- Pestaña 1: Escribir -->
                  <div class="lp-panel is-activo" role="tabpanel" id="panelEscribir" aria-labelledby="tabEscribir" tabindex="0">
                    <div class="lp-panel-rejilla">
                      <div class="lp-carta" data-lp-pizarra>
                        <div class="lp-carta-cabeza">
                          <p class="lp-carta-titulo">Pizarrón</p>
                          <span class="lp-chip lp-chip--estado" aria-hidden="true">
                            <span class="lp-punto lp-punto--vivo"></span>
                            <span class="lp-estado-a">Escribiendo…</span><span class="lp-estado-b">Listo para leer</span>
                          </span>
                        </div>
                        <div class="lp-herramientas" aria-hidden="true">
                          <span class="lp-herr is-activo"><?= icono('lapiz') ?> Lápiz</span>
                          <span class="lp-herr"><?= icono('borrador') ?> Borrador</span>
                          <span class="lp-herr"><?= icono('deshacer') ?> Deshacer</span>
                        </div>
                        <div class="lp-pizarra">
                          <svg class="lp-trazo" viewBox="0 0 520 160" role="img" aria-label="Ecuación escrita a mano: 2x + 3 = 11">
                            <defs>
                              <radialGradient id="lpLuzLapiz">
                                <stop offset="0" stop-color="#ffffff" stop-opacity=".95"/>
                                <stop offset=".35" stop-color="#c9bcff" stop-opacity=".6"/>
                                <stop offset="1" stop-color="#7c5cff" stop-opacity="0"/>
                              </radialGradient>
                            </defs>
                            <g transform="translate(10 0) skewX(-5)">
                              <g class="lp-trazo-lineas">
                                <?php foreach ($trazosEcuacion as $d): ?><path d="<?= $d ?>"/><?php endforeach; ?>
                              </g>
                              <g class="lp-lapiz"><circle r="13" fill="url(#lpLuzLapiz)"/><circle r="3" fill="#fff"/></g>
                            </g>
                          </svg>
                        </div>
                        <div class="lp-pizarra-pie" aria-hidden="true">
                          <span class="lp-falso lp-falso--claro">Leer</span>
                          <span class="lp-falso"><?= icono('teclado') ?> Escribirla con el teclado</span>
                        </div>
                      </div>

                      <div class="lp-columna lp-extra">
                        <div class="lp-carta">
                          <p class="lp-carta-titulo">Escribe con</p>
                          <ul class="lp-chips">
                            <li>Mouse</li><li>Dedo</li><li>Lápiz táctil</li>
                          </ul>
                        </div>
                        <div class="lp-carta">
                          <p class="lp-carta-titulo">Si no se entiende</p>
                          <p class="lp-texto-chico">Si el trazo no es legible, SolveIt te avisa y no resuelve nada.</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Pestaña 2: Confirmar -->
                  <div class="lp-panel" role="tabpanel" id="panelConfirmar" aria-labelledby="tabConfirmar" tabindex="0">
                    <div class="lp-panel-rejilla">
                      <div class="lp-carta">
                        <div class="lp-carta-cabeza">
                          <p class="lp-carta-titulo">Lectura de la IA</p>
                          <span class="lp-chip">La IA solo propone</span>
                        </div>
                        <p class="lp-aviso"><?= lp_icono('alerta') ?><span><strong>Revisa:</strong> el tercer símbolo podría ser + o ×</span></p>
                        <p class="lp-etiqueta">Si algo está mal, corrígelo aquí:</p>
                        <div class="lp-campo">
                          <span class="lp-campo-texto" data-lp-revuelto="2*x+3=11" aria-hidden="true">2*x+3=11</span>
                          <span class="lp-sr">2*x+3=11</span>
                          <span class="lp-cursor" aria-hidden="true"></span>
                        </div>
                        <div class="lp-vista"><span class="lp-formula">2<i>x</i> + 3 = 11</span></div>
                        <div class="lp-fila" aria-hidden="true">
                          <span class="lp-falso lp-falso--claro">Resolver</span>
                          <span class="lp-fila-texto">Explicación:</span>
                          <span class="lp-nivel"><span class="is-activo">Básica</span><span>Avanzada</span></span>
                        </div>
                      </div>

                      <div class="lp-columna lp-extra">
                        <div class="lp-carta">
                          <p class="lp-carta-titulo">Tu trazo</p>
                          <svg class="lp-trazo lp-trazo--mini" viewBox="0 0 520 160" aria-hidden="true" focusable="false">
                            <g transform="translate(10 0) skewX(-5)">
                              <?php foreach ($trazosEcuacion as $d): ?><path d="<?= $d ?>"/><?php endforeach; ?>
                            </g>
                          </svg>
                        </div>
                        <div class="lp-carta">
                          <p class="lp-carta-titulo">Con el teclado</p>
                          <p class="lp-texto-chico">Usa <code>*</code> para multiplicar, <code>/</code> para dividir, <code>^</code> para potencias y <code>sqrt(…)</code> para raíces.</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Pestaña 3: Resolver -->
                  <div class="lp-panel" role="tabpanel" id="panelResolver" aria-labelledby="tabResolver" tabindex="0">
                    <div class="lp-panel-rejilla">
                      <div class="lp-carta">
                        <div class="lp-carta-cabeza">
                          <p class="lp-carta-titulo">Solución paso a paso</p>
                          <span class="lp-chip">Explicación básica</span>
                        </div>
                        <ol class="lp-sol">
                          <li style="--i:0">
                            <span class="lp-sol-num" aria-hidden="true">1</span>
                            <strong>Restar 3 en ambos lados</strong>
                            <span class="lp-formula">2<i>x</i> + 3 − 3 = 11 − 3 &nbsp;→&nbsp; 2<i>x</i> = 8</span>
                          </li>
                          <li style="--i:1">
                            <span class="lp-sol-num" aria-hidden="true">2</span>
                            <strong>Dividir entre 2</strong>
                            <span class="lp-formula"><i>x</i> = <span class="lp-frac"><span>8</span><span>2</span></span> &nbsp;→&nbsp; <i>x</i> = 4</span>
                          </li>
                        </ol>
                        <p class="lp-sello"><?= lp_icono('check') ?><span>Verificado · <span class="lp-formula">2(4) + 3 = 11</span></span></p>
                      </div>

                      <div class="lp-columna lp-extra">
                        <div class="lp-carta">
                          <p class="lp-etiqueta-mayus">Resultado</p>
                          <p class="lp-resultado lp-formula"><i>x</i> = 4</p>
                          <p class="lp-texto-chico">Lo calcula PHP, no se copia de la IA.</p>
                        </div>
                        <div class="lp-carta">
                          <p class="lp-carta-titulo">Practica</p>
                          <p class="lp-texto-chico">Ejercicio parecido:</p>
                          <p class="lp-ejercicio lp-formula">3<i>x</i> − 5 = 10</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <p class="lp-maqueta-nota">Ejemplo ilustrativo del flujo de la app.</p>
      </section>
    </div>

    <!-- ============ 4. Cómo funciona ============ -->
    <section class="lp-seccion lp-pasos" id="como-funciona" aria-labelledby="tituloPasos">
      <div class="lp-contenedor">
        <header class="lp-cabecera">
          <p class="lp-insignia" data-lp-revelar><span class="lp-punto" aria-hidden="true"></span> Cómo funciona</p>
          <h2 class="lp-titulo-3" id="tituloPasos" data-lp-revelar style="--i:1">Tres pasos, ninguno a ciegas</h2>
          <p class="lp-lead" data-lp-revelar style="--i:2">Tú escribes y confirmas; SolveIt resuelve y PHP comprueba el resultado.</p>
        </header>

        <ol class="lp-pasos-lista">
          <li class="lp-paso" data-lp-revelar style="--i:0">
            <div class="lp-paso-cabeza">
              <span class="lp-paso-icono"><?= icono('lapiz') ?></span>
              <span class="lp-paso-num" aria-hidden="true">01</span>
            </div>
            <h3>Escribe</h3>
            <p>Dibuja la operación o ecuación con el mouse, el dedo o un lápiz.</p>
          </li>
          <li class="lp-paso" data-lp-revelar style="--i:1">
            <div class="lp-paso-cabeza">
              <span class="lp-paso-icono"><?= lp_icono('ojo') ?></span>
              <span class="lp-paso-num" aria-hidden="true">02</span>
            </div>
            <h3>Confirma</h3>
            <p>La IA transcribe lo que leyó y tú lo corriges antes de resolver.</p>
          </li>
          <li class="lp-paso" data-lp-revelar style="--i:2">
            <div class="lp-paso-cabeza">
              <span class="lp-paso-icono"><?= lp_icono('escudo') ?></span>
              <span class="lp-paso-num" aria-hidden="true">03</span>
            </div>
            <h3>Resuelve y verifica</h3>
            <p>Recibes los pasos explicados y PHP comprueba el resultado por su cuenta.</p>
          </li>
        </ol>
      </div>
    </section>

    <!-- ============ 5. Características ============ -->
    <section class="lp-seccion lp-caracteristicas" id="caracteristicas" aria-labelledby="tituloCarac">
      <div class="lp-contenedor">
        <header class="lp-cabecera">
          <p class="lp-insignia" data-lp-revelar><span class="lp-punto" aria-hidden="true"></span> Características</p>
          <h2 class="lp-titulo-3" id="tituloCarac" data-lp-revelar style="--i:1">Pensado para entender,<br> no solo para responder</h2>
        </header>

        <div class="lp-rejilla" data-lp-pausa>
          <article class="lp-carac" data-lp-revelar data-lp-foco style="--i:0">
            <div class="lp-carac-visual" aria-hidden="true">
              <div class="lp-mini">
                <p class="lp-mini-etiqueta">Si algo está mal, corrígelo aquí:</p>
                <div class="lp-mini-campo"><span>2*x</span><span class="lp-mini-marca">+</span><span>3=11</span><span class="lp-cursor"></span></div>
                <div class="lp-mini-acciones">
                  <span class="lp-chip"><?= lp_icono('usuario') ?> Tú decides</span>
                  <span class="lp-chip"><?= icono('teclado') ?> Teclado</span>
                </div>
              </div>
            </div>
            <h3>Tú tienes la última palabra</h3>
            <p>Corriges la lectura antes de resolver, o escribes con el teclado.</p>
          </article>

          <article class="lp-carac" data-lp-revelar data-lp-foco style="--i:1">
            <div class="lp-carac-visual" aria-hidden="true">
              <div class="lp-mini lp-mini-verif">
                <div class="lp-mini-fila"><span class="lp-mini-tag">IA</span><span class="lp-formula"><i>x</i> = 4</span></div>
                <div class="lp-mini-fila lp-mini-fila--ok"><span class="lp-mini-tag">PHP</span><span class="lp-formula">2(4) + 3 = 11</span><?= lp_icono('check') ?></div>
              </div>
            </div>
            <h3>Verificación independiente</h3>
            <p>Un evaluador propio en PHP recalcula el resultado; si no coincide con la IA, no se presenta como correcto.</p>
          </article>

          <article class="lp-carac" data-lp-revelar data-lp-foco style="--i:2">
            <div class="lp-carac-visual" aria-hidden="true">
              <div class="lp-mini">
                <div class="lp-mini-nivel"><span class="lp-mini-nivel-pista"></span><span>Básica</span><span>Avanzada</span></div>
                <p class="lp-mini-formula lp-formula">2<i>x</i> = 8 &nbsp;→&nbsp; <i>x</i> = 4</p>
              </div>
            </div>
            <h3>Explicación a tu nivel</h3>
            <p>Modo básico o avanzado, con los pasos y su fórmula.</p>
          </article>

          <article class="lp-carac" data-lp-revelar data-lp-foco style="--i:3">
            <div class="lp-carac-visual" aria-hidden="true">
              <ul class="lp-mini lp-mini-historial">
                <li><span class="lp-formula">2<i>x</i> + 3 = 11</span><span class="lp-mini-ok">Verificado</span></li>
                <li><span class="lp-formula"><i>x</i>/2 = 7</span><span class="lp-mini-ok">Verificado</span></li>
                <li class="lp-mini-nuevo"><span>Ejercicio parecido</span><span class="lp-formula">3<i>x</i> − 5 = 10</span></li>
              </ul>
            </div>
            <h3>Practica</h3>
            <p>Historial de tus problemas y ejercicios parecidos con corrección automática.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- ============ 6. Cierre (CTA) ============ -->
    <section class="lp-seccion lp-cta" id="probar" aria-labelledby="tituloCta">
      <div class="lp-contenedor">
        <div class="lp-cta-tarjeta" data-lp-revelar data-lp-pausa>
          <span class="lp-borde-luz" aria-hidden="true"><span class="lp-borde-luz-punto"></span></span>
          <span class="lp-cta-amanecer" aria-hidden="true"></span>

          <h2 class="lp-titulo-2" id="tituloCta">Pruébalo ahora</h2>
          <div class="lp-cta-botones">
            <span class="lp-iman"><a class="lp-btn lp-btn--claro lp-btn--grande" href="<?= $btnPrincipal['url'] ?>"><?= $btnPrincipal['texto'] ?></a></span>
            <span class="lp-iman"><a class="lp-btn lp-btn--vidrio lp-btn--grande" href="<?= $ctaSecundario['url'] ?>"><?= $ctaSecundario['texto'] ?></a></span>
          </div>
          <?php if (!$conSesion): ?>
            <p class="lp-cta-nota">El modo demo (sin IA) se abre después de iniciar sesión.</p>
          <?php endif; ?>
          <p class="lp-alcance">Alcance: operaciones básicas y ecuaciones de una variable.</p>

          <!-- Cinta infinita con ejemplos reales del modo demo -->
          <div class="lp-cinta">
            <div class="lp-cinta-pista">
              <ul aria-label="Ejemplos que puede resolver">
                <?php foreach ($ejemplos as $ej): ?><li class="lp-formula"><?= $ej ?></li><?php endforeach; ?>
              </ul>
              <ul aria-hidden="true">
                <?php foreach ($ejemplos as $ej): ?><li class="lp-formula"><?= $ej ?></li><?php endforeach; ?>
              </ul>
            </div>
            <span class="lp-desenfoque lp-desenfoque--izq" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
            <span class="lp-desenfoque lp-desenfoque--der" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- ============ 7. Pie ============ -->
  <footer class="lp-pie">
    <div class="lp-contenedor lp-pie-in">
      <div class="lp-pie-izq">
        <a class="lp-marca lp-marca--chica" href="#inicio"><?= $logo ?><span>SolveIt</span></a>
        <p>SolveIt · Proyecto de <?= e($materia) ?> · <?= e($autores) ?></p>
      </div>
      <p class="lp-pie-tec">Hecho con HTML, CSS, PHP, JavaScript y MySQL.</p>
    </div>
    <div class="lp-contenedor">
      <p class="lp-pie-creditos">Animaciones adaptadas de <a href="https://github.com/ibelick/motion-primitives" rel="noopener">Motion-Primitives</a> (licencia MIT) a JavaScript y CSS puros. Tipografías Newsreader e Inter (licencia SIL OFL).</p>
    </div>
  </footer>

  <!-- Grano fino sobre el fondo, como en la referencia -->
  <div class="lp-grano" aria-hidden="true"></div>

  <script src="js/landing.js"></script>
</body>
</html>
