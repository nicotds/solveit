/* =====================================================================
   SolveIt — Landing page (js/landing.js)
   ---------------------------------------------------------------------
   JavaScript puro, sin librerías. Partes:
     1. Utilidades
     2. Barra: resalte deslizante, sección activa y menú móvil
     3. Titulares que aparecen palabra por palabra
     4. Scroll: variables --p, --q, --r y --m para el CSS
     5. Fondo matemático (canvas): símbolos flotantes y punto en la curva
     6. Maqueta: pestañas, trazo a mano y lectura "revuelta"
     7. Revelado de secciones, pausas, foco de luz y botones imán
     8. Arranque

   Créditos: varias animaciones son adaptaciones (reescritas en JS/CSS puro,
   sin React ni Motion) de componentes de Motion-Primitives:
   https://github.com/ibelick/motion-primitives — MIT License, Copyright (c) 2024 ibelick.
     TextEffect          → partirEnPalabras()
     InView/AnimatedGroup→ iniciarRevelado()
     AnimatedBackground  → resalte de la barra (iniciarBarra)
     TransitionPanel     → cambio de pestañas (CSS .lp-panel)
     TextScramble        → revolver()
     Spotlight           → iniciarFocos()
     Magnetic            → iniciarImanes()
     TextShimmer, BorderTrail, InfiniteSlider, ProgressiveBlur → solo CSS
   ===================================================================== */
(function () {
  'use strict';

  // ---------- 1. Utilidades ----------
  const $ = (selector, raiz = document) => raiz.querySelector(selector);
  const $$ = (selector, raiz = document) => Array.from(raiz.querySelectorAll(selector));
  const limitar = (valor, min = 0, max = 1) => Math.min(max, Math.max(min, valor));

  // Si el sistema pide "reducir movimiento", todo queda estático.
  const reducido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  // Efectos de mouse solo con un puntero fino (no en pantallas táctiles).
  const punteroFino = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  // Modo del fondo: lo decide la clase del <body> (inicio.php?fondo=color → modo B).
  const fondoMatematico = !document.body.classList.contains('lp-fondo-color');

  // ---------- 2. Barra ----------
  function iniciarBarra() {
    const barra = $('#barra');
    const nav = $('.lp-nav', barra);
    const resalte = $('.lp-nav-resalte', barra);
    const enlaces = $$('.lp-nav a', barra);
    let actual = null; // enlace de la sección que se está viendo

    // Fondo que se desliza hasta el enlace (adaptado de AnimatedBackground)
    function moverResalte(enlace) {
      if (!enlace || nav.offsetParent === null) { resalte.style.opacity = '0'; return; }
      resalte.style.width = enlace.offsetWidth + 'px';
      resalte.style.transform = 'translateX(' + enlace.offsetLeft + 'px)';
      resalte.style.opacity = '1';
    }
    enlaces.forEach((enlace) => {
      enlace.addEventListener('pointerenter', () => moverResalte(enlace));
      enlace.addEventListener('focus', () => moverResalte(enlace));
    });
    nav.addEventListener('pointerleave', () => moverResalte(actual));
    nav.addEventListener('focusout', (e) => { if (!nav.contains(e.relatedTarget)) moverResalte(actual); });
    window.addEventListener('resize', () => moverResalte(actual));

    // Sección activa: la última cuyo borde superior ya pasó el 45 % de la pantalla.
    // Se calcula en cada scroll (no con un observador) para acertar aunque la página
    // "salte", por ejemplo con la tecla Fin.
    const pares = enlaces
      .map((enlace) => ({ enlace, seccion: document.getElementById(enlace.hash.slice(1)) }))
      .filter((par) => par.seccion)
      .sort((a, b) => (a.seccion.compareDocumentPosition(b.seccion) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));

    function marcarSeccion() {
      const linea = window.innerHeight * 0.45;
      let nuevo = pares[0] ? pares[0].enlace : null;
      pares.forEach((par) => { if (par.seccion.getBoundingClientRect().top <= linea) nuevo = par.enlace; });
      if (nuevo === actual) return;
      actual = nuevo;
      enlaces.forEach((enlace) => {
        if (enlace === actual) enlace.setAttribute('aria-current', 'true');
        else enlace.removeAttribute('aria-current');
      });
      if (!nav.matches(':hover') && !nav.contains(document.activeElement)) moverResalte(actual);
    }
    return { marcarSeccion };
  }

  // Menú de hamburguesa: se abre con el botón y se cierra con Esc,
  // al elegir un enlace, al hacer clic fuera o al agrandar la ventana.
  function iniciarMenu() {
    const barra = $('#barra');
    const boton = $('.lp-menu-btn', barra);
    const menu = $('#menuMovil');
    if (!boton || !menu) return;

    function abrir() {
      menu.hidden = false;
      boton.setAttribute('aria-expanded', 'true');
      boton.setAttribute('aria-label', 'Cerrar menú');
      const primero = $('a', menu);
      if (primero) primero.focus();
    }
    function cerrar(devolverFoco) {
      if (menu.hidden) return;
      menu.hidden = true;
      boton.setAttribute('aria-expanded', 'false');
      boton.setAttribute('aria-label', 'Abrir menú');
      if (devolverFoco) boton.focus();
    }
    boton.addEventListener('click', () => (menu.hidden ? abrir() : cerrar(false)));
    menu.addEventListener('click', (e) => { if (e.target.closest('a')) cerrar(false); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrar(true); });
    document.addEventListener('click', (e) => { if (!barra.contains(e.target)) cerrar(false); });
    window.matchMedia('(min-width: 901px)').addEventListener('change', (e) => { if (e.matches) cerrar(false); });
  }

  // ---------- 3. Palabra por palabra (adaptado de TextEffect) ----------
  // Envuelve cada palabra en un <span> con su número (--i) para escalonar la animación.
  // Los lectores de pantalla leen el texto completo de un span oculto.
  function partirEnPalabras(titulo) {
    const textoCompleto = titulo.textContent.replace(/\s+/g, ' ').trim();
    let numero = 0;
    $$('.lp-linea', titulo).forEach((linea) => {
      const palabras = linea.textContent.trim().split(/\s+/);
      linea.textContent = '';
      palabras.forEach((palabra, k) => {
        const span = document.createElement('span');
        span.className = 'lp-palabra';
        span.textContent = palabra;
        span.style.setProperty('--i', numero++);
        linea.appendChild(span);
        if (k < palabras.length - 1) linea.appendChild(document.createTextNode(' '));
      });
      linea.setAttribute('aria-hidden', 'true');
    });
    const paraLectores = document.createElement('span');
    paraLectores.className = 'lp-sr';
    paraLectores.textContent = textoCompleto;
    titulo.prepend(paraLectores);
    titulo.classList.add('is-partido');
  }

  // ---------- 4. Scroll ----------
  // Calcula cuatro números de 0 a 1 y se los pasa al CSS como variables:
  //   --p: avance del héroe (desenfoque y desvanecido del texto)
  //   --q: avance de la esfera (sube y se enciende; el fondo matemático se va)
  //   --m: avance de la maqueta (sube y pasa de escala .92 a 1)
  //   --r: salida de la escena (la esfera se desvanece cuando termina "Así funciona")
  function iniciarScroll(fondo, barraNav) {
    const barra = $('#barra');
    const heroe = $('#inicio');
    const escena = $('.lp-escena');
    const escenario = $('.lp-escenario');
    const maqueta = $('#maqueta');
    const anteriores = new Map(); // último valor puesto en cada elemento (evita reescribirlo)

    function poner(elemento, nombre, valor) {
      const texto = valor.toFixed(4);
      const previos = anteriores.get(elemento) || {};
      if (previos[nombre] === texto) return;
      previos[nombre] = texto;
      anteriores.set(elemento, previos);
      elemento.style.setProperty(nombre, texto);
    }

    let pendiente = false;
    function actualizar() {
      pendiente = false;
      const y = window.scrollY;
      const alto = window.innerHeight;

      barra.classList.toggle('is-scrolled', y > 40);
      barraNav.marcarSeccion();

      // El héroe mide más que la pantalla: esa diferencia es lo que dura el efecto.
      const recorrido = Math.max(alto * 0.3, heroe.offsetHeight - alto);
      const p = limitar(y / recorrido);
      const q = limitar((y - alto * 0.3) / (alto * 0.9));
      // Cuando el final de la escena sube más allá del borde inferior, el fondo deja de estar fijo
      const r = limitar((alto - escena.getBoundingClientRect().bottom) / (alto * 0.5));
      poner(heroe, '--p', p);
      poner(escenario, '--p', p);
      poner(escenario, '--q', q);
      poner(escenario, '--r', r);

      if (maqueta) {
        const arriba = maqueta.getBoundingClientRect().top;
        poner(maqueta, '--m', limitar((alto - arriba) / (alto * 0.6)));
      }

      // Cuando el fondo matemático ya no se ve, se oculta y se pausa el canvas.
      const fondoVisible = q < 1;
      escenario.classList.toggle('is-fondo-oculto', !fondoVisible);
      if (fondo) fondo.ponerEstado(fondoVisible, p);
    }
    const pedir = () => {
      if (pendiente) return;
      pendiente = true;
      requestAnimationFrame(actualizar);
    };
    window.addEventListener('scroll', pedir, { passive: true });
    window.addEventListener('resize', pedir);
    actualizar();
  }

  // ---------- 5. Fondo matemático (canvas) ----------
  // Pocos símbolos (15–28) que flotan despacio. Cada uno tiene una "profundidad" z:
  // los lejanos (z ≈ 0) son pequeños, tenues y borrosos; los cercanos, grandes y nítidos,
  // y se mueven más con el mouse y el scroll (parallax).
  function crearFondoMatematico() {
    const lienzo = $('#lienzoFondo');
    if (!lienzo || !lienzo.getContext) return null;
    const ctx = lienzo.getContext('2d');

    const SIMBOLOS = ['∑', '∫', 'π', '√', '∞', 'x²', 'Δ', 'θ', '±', '÷', '×'];
    const ECUACIONES = ['2x + 3 = 11', 'x² − 5x + 6 = 0', 'a² + b² = c²', 'x/2 = 7', '√16 + 2³', '(2 + 3) · 4'];
    const FUENTE = '"Newsreader", "LP KaTeX Main", "Cambria Math", "Times New Roman", serif';

    // La misma parábola del SVG en inicio.php (viewBox 1440 × 900):
    // M -60 940  Q 720 -420  1500 940
    const P0 = { x: -60, y: 940 };
    const P1 = { x: 720, y: -420 };
    const P2 = { x: 1500, y: 940 };
    const CAJA = { ancho: 1440, alto: 900 };

    let ancho = 0, alto = 0, dpr = 1;
    let escala = 1, desX = 0, desY = 0; // cómo se ajusta el viewBox (slice) a la pantalla
    let elementos = [];
    let visible = true, progresoHeroe = 0;
    let corriendo = false, cuadro = 0, anterior = 0;
    const inicioVuelo = performance.now() + 3300; // el punto sale cuando la curva ya se dibujó
    const raton = { x: 0, y: 0, sx: 0, sy: 0 };

    function medir() {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      ancho = lienzo.clientWidth;
      alto = lienzo.clientHeight;
      lienzo.width = Math.round(ancho * dpr);
      lienzo.height = Math.round(alto * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      // preserveAspectRatio="xMidYMid slice": escalar para CUBRIR y centrar
      escala = Math.max(ancho / CAJA.ancho, alto / CAJA.alto);
      desX = (ancho - CAJA.ancho * escala) / 2;
      desY = (alto - CAJA.alto * escala) / 2;
    }

    // Cada símbolo se dibuja UNA vez en un canvas pequeño (con su desenfoque);
    // en cada cuadro solo se copia esa imagen. Así el bucle es muy barato.
    function crearSprite(texto, tamano, z) {
      const desenfoque = (1 - z) * 2.6;
      const alfa = 0.12 + z * 0.4;
      const cursiva = /[a-zθπ]/.test(texto) ? 'italic ' : ''; // variables en cursiva, como en un libro
      const fuente = cursiva + '300 ' + tamano + 'px ' + FUENTE;
      ctx.font = fuente;
      const margen = Math.ceil(desenfoque * 3 + 4);
      const w = Math.ceil(ctx.measureText(texto).width + margen * 2);
      const h = Math.ceil(tamano * 1.5 + margen * 2);
      const c = document.createElement('canvas');
      c.width = Math.ceil(w * dpr);
      c.height = Math.ceil(h * dpr);
      const g = c.getContext('2d');
      g.scale(dpr, dpr);
      if (desenfoque > 0.3 && 'filter' in g) g.filter = 'blur(' + desenfoque.toFixed(1) + 'px)';
      g.font = fuente;
      g.textBaseline = 'middle';
      g.fillStyle = 'rgba(220, 210, 255, ' + alfa.toFixed(2) + ')';
      g.fillText(texto, margen, h / 2);
      return { c, w, h };
    }

    function crearElementos() {
      const cantidad = window.innerWidth < 640 ? 15 : window.innerWidth < 1100 ? 21 : 28;
      elementos = [];
      for (let i = 0; i < cantidad; i++) {
        const esEcuacion = i % 4 === 0;
        const texto = esEcuacion ? ECUACIONES[(i / 4) % ECUACIONES.length] : SIMBOLOS[i % SIMBOLOS.length];
        const z = Math.pow(Math.random(), 1.3); // hay más lejanos que cercanos
        const tamano = esEcuacion ? 14 + z * 12 : 18 + z * 30;
        // Se prueban varias posiciones y se queda la que no choca con los demás
        let x = 0, y = 0;
        for (let intento = 0; intento < 14; intento++) {
          x = Math.random() * ancho;
          y = Math.random() * alto;
          // Los cercanos (grandes) no se ponen justo detrás del titular
          if (z > 0.55 && Math.abs(x - ancho / 2) < ancho * 0.25 && y > alto * 0.4) continue;
          if (elementos.every((otro) => Math.hypot(otro.x - x, otro.y - y) > 120)) break;
        }
        elementos.push({
          x, y, z,
          vx: (Math.random() - 0.5) * (4 + z * 8),  // píxeles por segundo
          vy: -(3 + z * 9),                         // suben despacio
          giro: (Math.random() - 0.5) * 0.5,
          vGiro: (Math.random() - 0.5) * 0.06,
          sprite: crearSprite(texto, tamano, z),
        });
      }
      elementos.sort((a, b) => a.z - b.z); // primero los lejanos
    }

    // Punto de la parábola (Bézier cuadrática) para t entre 0 y 1, en píxeles.
    // Como el punto de control está a la mitad en x, x avanza parejo con t:
    // el punto va más lento arriba, como un proyectil de verdad.
    function puntoCurva(t) {
      const u = 1 - t;
      const x = u * u * P0.x + 2 * u * t * P1.x + t * t * P2.x;
      const y = u * u * P0.y + 2 * u * t * P1.y + t * t * P2.y;
      return { x: desX + x * escala, y: desY + y * escala - progresoHeroe * 90 }; // mismo parallax que el SVG
    }

    // Punto luminoso que recorre la curva en bucle, con una estela
    function dibujarCometa(ahora) {
      const VUELO = 5200, PAUSA = 1600;
      const transcurrido = ahora - inicioVuelo;
      if (transcurrido < 0) return;
      const fase = transcurrido % (VUELO + PAUSA);
      if (fase > VUELO) return;
      const t = fase / VUELO;
      const alfa = Math.min(1, t * 8, (1 - t) * 8);

      const LARGO = 0.07, PASOS = 16;
      ctx.lineCap = 'round';
      for (let k = PASOS; k > 0; k--) {
        const t2 = t - (LARGO * (k - 1)) / PASOS;
        if (t2 <= 0) continue;
        const a = puntoCurva(Math.max(0, t - (LARGO * k) / PASOS));
        const b = puntoCurva(t2);
        const fuerza = 1 - k / PASOS;
        ctx.strokeStyle = 'rgba(220, 210, 255, ' + (alfa * fuerza * 0.85).toFixed(3) + ')';
        ctx.lineWidth = 0.6 + 2.4 * fuerza;
        ctx.beginPath();
        ctx.moveTo(a.x, a.y);
        ctx.lineTo(b.x, b.y);
        ctx.stroke();
      }
      const p = puntoCurva(t);
      const halo = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, 24);
      halo.addColorStop(0, 'rgba(255, 255, 255, ' + alfa + ')');
      halo.addColorStop(0.22, 'rgba(205, 192, 255, ' + (alfa * 0.75).toFixed(3) + ')');
      halo.addColorStop(1, 'rgba(124, 92, 255, 0)');
      ctx.fillStyle = halo;
      ctx.beginPath();
      ctx.arc(p.x, p.y, 24, 0, Math.PI * 2);
      ctx.fill();
    }

    function dibujarSimbolos(dt) {
      const MARGEN = 90;
      const altoTotal = alto + MARGEN * 2;
      const scroll = reducido ? 0 : window.scrollY; // sin parallax si se pidió reducir movimiento
      for (const el of elementos) {
        el.x += el.vx * dt;
        el.y += el.vy * dt;
        el.giro += el.vGiro * dt;
        // Al salir por un borde, vuelven a entrar por el contrario
        if (el.y < -MARGEN) el.y += altoTotal;
        if (el.x < -MARGEN) el.x += ancho + MARGEN * 2;
        else if (el.x > ancho + MARGEN) el.x -= ancho + MARGEN * 2;
        // Parallax: los cercanos se mueven más con el mouse y con el scroll
        const px = el.x + raton.sx * 28 * el.z;
        let py = el.y + raton.sy * 18 * el.z - scroll * (0.05 + el.z * 0.25);
        py = (((py + MARGEN) % altoTotal) + altoTotal) % altoTotal - MARGEN;
        const s = el.sprite;
        ctx.save();
        ctx.translate(px, py);
        ctx.rotate(el.giro);
        ctx.drawImage(s.c, -s.w / 2, -s.h / 2, s.w, s.h);
        ctx.restore();
      }
    }

    function pintar(ahora) {
      const dt = limitar((ahora - anterior) / 1000, 0, 0.05); // segundos desde el cuadro anterior
      anterior = ahora;
      raton.sx += (raton.x - raton.sx) * 0.05; // suaviza el movimiento del mouse
      raton.sy += (raton.y - raton.sy) * 0.05;
      ctx.clearRect(0, 0, ancho, alto);
      dibujarSimbolos(dt);
      dibujarCometa(ahora);
      cuadro = corriendo ? requestAnimationFrame(pintar) : 0;
    }

    // Versión fija (movimiento reducido): un solo dibujo, sin punto
    function pintarQuieto() {
      ctx.clearRect(0, 0, ancho, alto);
      dibujarSimbolos(0);
    }

    // Arranca o detiene el bucle: solo corre si el fondo se ve y la pestaña está activa.
    function revisar() {
      const debeCorrer = visible && !document.hidden && !reducido;
      if (debeCorrer && !corriendo) {
        corriendo = true;
        anterior = performance.now();
        cuadro = requestAnimationFrame(pintar);
      } else if (!debeCorrer && corriendo) {
        corriendo = false;
        cancelAnimationFrame(cuadro);
        cuadro = 0;
      }
    }

    function preparar() {
      medir();
      crearElementos();
      if (reducido) pintarQuieto();
    }

    preparar();
    revisar();

    // Cuando terminan de cargar las fuentes, se rehacen los símbolos con la tipografía correcta.
    if (document.fonts && document.fonts.load) {
      Promise.all([
        document.fonts.load('300 20px "Newsreader"'),
        document.fonts.load('20px "LP KaTeX Main"'),
      ]).then(preparar, () => {});
    }

    let esperaRedimension = 0;
    window.addEventListener('resize', () => {
      clearTimeout(esperaRedimension);
      esperaRedimension = setTimeout(preparar, 150);
    });
    document.addEventListener('visibilitychange', revisar);
    if (punteroFino && !reducido) {
      window.addEventListener('pointermove', (e) => {
        raton.x = (e.clientX / window.innerWidth) * 2 - 1;
        raton.y = (e.clientY / window.innerHeight) * 2 - 1;
      }, { passive: true });
    }

    return {
      // Lo llama el scroll: si el fondo ya no se ve, se pausa
      ponerEstado(estaVisible, p) {
        progresoHeroe = p;
        if (estaVisible !== visible) {
          visible = estaVisible;
          revisar();
        }
      },
    };
  }

  // ---------- 6. Maqueta ----------
  let turnoTrazo = 0; // si empieza otra animación del trazo, la anterior se cancela

  // Deja el trazo listo para animarse (oculto) o, con movimiento reducido, completo.
  function prepararTrazo(svg) {
    $$('.lp-trazo-lineas path', svg).forEach((camino) => {
      const largo = camino.getTotalLength();
      camino.style.strokeDasharray = largo + ' ' + largo;
      camino.style.strokeDashoffset = reducido ? '0' : String(largo);
      // Oculto del todo: con puntas redondeadas, un trazo "vacío" dejaría un puntito
      camino.style.opacity = reducido ? '1' : '0';
    });
  }

  // Dibuja "2x + 3 = 11" trazo por trazo; un punto luminoso hace de lápiz.
  function escribirAMano(tarjeta) {
    const svg = $('svg.lp-trazo', tarjeta);
    const caminos = $$('.lp-trazo-lineas path', svg);
    const lapiz = $('.lp-lapiz', svg);
    const turno = ++turnoTrazo;
    tarjeta.classList.remove('is-listo');
    if (reducido) { prepararTrazo(svg); tarjeta.classList.add('is-listo'); return; }

    const VELOCIDAD = 0.62; // unidades del dibujo por milisegundo
    const PAUSA = 70;       // ms entre trazos (cuando se "levanta" el lápiz)
    const largos = caminos.map((c) => c.getTotalLength());
    const tiempos = [];
    let total = 280; // un respiro antes de empezar
    largos.forEach((largo) => {
      tiempos.push({ inicio: total, duracion: largo / VELOCIDAD });
      total += largo / VELOCIDAD + PAUSA;
    });
    caminos.forEach((c, i) => { c.style.strokeDashoffset = String(largos[i]); c.style.opacity = '0'; });

    const t0 = performance.now();
    function paso(ahora) {
      if (turno !== turnoTrazo) return;
      const t = ahora - t0;
      let punta = null;
      caminos.forEach((camino, i) => {
        const avance = limitar((t - tiempos[i].inicio) / tiempos[i].duracion);
        camino.style.strokeDashoffset = (largos[i] * (1 - avance)).toFixed(2);
        camino.style.opacity = avance > 0 ? '1' : '0';
        if (avance > 0 && avance < 1) punta = camino.getPointAtLength(largos[i] * avance);
      });
      if (punta) {
        lapiz.setAttribute('transform', 'translate(' + punta.x.toFixed(1) + ' ' + punta.y.toFixed(1) + ')');
        lapiz.style.opacity = '1';
      }
      if (t < total) {
        requestAnimationFrame(paso);
      } else {
        lapiz.style.opacity = '0';
        tarjeta.classList.add('is-listo');
      }
    }
    requestAnimationFrame(paso);
  }

  // La "lectura de la IA" aparece revuelta y se ordena de izquierda a derecha
  // (adaptado de TextScramble, con caracteres matemáticos).
  function revolver(elemento, texto, duracion) {
    const tarjeta = elemento.closest('.lp-carta');
    if (reducido) { elemento.textContent = texto; if (tarjeta) tarjeta.classList.add('is-listo'); return; }
    const CARACTERES = '0123456789x+-*/=()^';
    const turno = (elemento.lpTurno = (elemento.lpTurno || 0) + 1);
    const ESPERA = 260, CADA = 45; // ms antes de empezar y entre cambios
    if (tarjeta) tarjeta.classList.remove('is-listo');
    const t0 = performance.now() + ESPERA;
    let ultimoCambio = 0;
    function paso(ahora) {
      if (elemento.lpTurno !== turno) return;
      const avance = (ahora - t0) / duracion;
      if (avance >= 1) {
        elemento.textContent = texto;
        if (tarjeta) tarjeta.classList.add('is-listo');
        return;
      }
      if (ahora - ultimoCambio >= CADA) {
        ultimoCambio = ahora;
        let mezcla = '';
        for (let i = 0; i < texto.length; i++) {
          mezcla += avance * texto.length > i ? texto[i] : CARACTERES[Math.floor(Math.random() * CARACTERES.length)];
        }
        elemento.textContent = mezcla;
      }
      requestAnimationFrame(paso);
    }
    requestAnimationFrame(paso);
  }

  // Pestañas Escribir / Confirmar / Resolver con roles ARIA.
  // Si nadie las toca, rotan solas: la barra de progreso es una animación CSS
  // y, cuando termina (animationend), se pasa a la siguiente.
  function iniciarMaqueta() {
    const maqueta = $('#maqueta');
    if (!maqueta) return;
    const pestanas = $$('[role="tab"]', maqueta);
    const paneles = pestanas.map((p) => document.getElementById(p.getAttribute('aria-controls')));
    const lista = $('[role="tablist"]', maqueta);
    let indice = 0;
    let rotando = !reducido;
    let yaEmpezo = false;

    $$('svg.lp-trazo', maqueta).forEach((svg) => { if ($('.lp-trazo-lineas', svg)) prepararTrazo(svg); });

    function animarPanel(panel) {
      panel.classList.remove('is-animando');
      void panel.offsetWidth; // fuerza al navegador a "olvidar" la animación para repetirla
      panel.classList.add('is-animando');
      const pizarra = $('[data-lp-pizarra]', panel);
      if (pizarra) escribirAMano(pizarra);
      const lectura = $('[data-lp-revuelto]', panel);
      if (lectura) revolver(lectura, lectura.dataset.lpRevuelto, 1000);
    }

    function reiniciarProgreso() {
      maqueta.classList.remove('is-rotando');
      if (!rotando) return;
      void maqueta.offsetWidth;
      maqueta.classList.add('is-rotando');
    }

    function activar(nuevo, opciones = {}) {
      indice = (nuevo + pestanas.length) % pestanas.length;
      pestanas.forEach((pestana, k) => {
        const elegida = k === indice;
        pestana.setAttribute('aria-selected', String(elegida));
        pestana.tabIndex = elegida ? 0 : -1;
        paneles[k].classList.toggle('is-activo', elegida);
      });
      if (opciones.porUsuario) rotando = false; // si el usuario elige, ya no rotan solas
      if (opciones.enfocar) pestanas[indice].focus();
      reiniciarProgreso();
      animarPanel(paneles[indice]);
    }

    pestanas.forEach((pestana, k) => pestana.addEventListener('click', () => activar(k, { porUsuario: true })));
    lista.addEventListener('keydown', (e) => {
      const destino = { ArrowRight: indice + 1, ArrowLeft: indice - 1, Home: 0, End: pestanas.length - 1 }[e.key];
      if (destino === undefined) return;
      e.preventDefault();
      activar(destino, { porUsuario: true, enfocar: true });
    });
    maqueta.addEventListener('animationend', (e) => {
      if (e.animationName === 'lp-progreso' && rotando) activar(indice + 1);
    });

    // Pausa mientras el mouse o el foco están dentro de la maqueta
    const pausar = (si) => maqueta.classList.toggle('is-pausado', si);
    maqueta.addEventListener('pointerenter', () => pausar(true));
    maqueta.addEventListener('pointerleave', () => pausar(maqueta.contains(document.activeElement)));
    maqueta.addEventListener('focusin', () => pausar(true));
    maqueta.addEventListener('focusout', (e) => { if (!maqueta.contains(e.relatedTarget)) pausar(false); });

    // Empieza la primera vez que se ve al menos un 25 %, y se pausa cuando sale del todo.
    // (Dos umbrales: 0 avisa al entrar o salir por completo; 0.25 avisa para arrancar.)
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(([entrada]) => {
        maqueta.classList.toggle('is-fuera', !entrada.isIntersecting);
        if (entrada.intersectionRatio >= 0.25 && !yaEmpezo) {
          yaEmpezo = true;
          activar(0);
        }
      }, { threshold: [0, 0.25] }).observe(maqueta);
    } else {
      activar(0);
    }
  }

  // ---------- 7. Revelado, pausas, focos e imanes ----------
  // Las secciones aparecen con fundido + subida + desenfoque al entrar en pantalla
  // (adaptado de InView y AnimatedGroup "blur-slide"). El retraso escalonado sale de --i.
  function iniciarRevelado() {
    const elementos = $$('[data-lp-revelar], [data-lp-palabras="vista"]');
    if (reducido || !('IntersectionObserver' in window)) {
      elementos.forEach((el) => el.classList.add('is-visible'));
      return;
    }
    const observador = new IntersectionObserver((entradas) => {
      entradas.forEach((entrada) => {
        if (!entrada.isIntersecting) return;
        entrada.target.classList.add('is-visible');
        observador.unobserve(entrada.target);
      });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.15 });
    elementos.forEach((el) => observador.observe(el));
  }

  // Pausa las animaciones infinitas (brillo, cinta, luz del borde…) de las zonas
  // que no están en pantalla: el CSS lo hace con la clase .is-fuera.
  function iniciarPausas() {
    if (!('IntersectionObserver' in window)) return;
    const observador = new IntersectionObserver((entradas) => {
      entradas.forEach((entrada) => entrada.target.classList.toggle('is-fuera', !entrada.isIntersecting));
    });
    $$('[data-lp-pausa]').forEach((zona) => observador.observe(zona));
  }

  // Foco de luz que sigue al mouse dentro de cada tarjeta (adaptado de Spotlight)
  function iniciarFocos() {
    if (!punteroFino) return;
    $$('[data-lp-foco]').forEach((tarjeta) => {
      tarjeta.addEventListener('pointermove', (e) => {
        const caja = tarjeta.getBoundingClientRect();
        tarjeta.style.setProperty('--mx', Math.round(e.clientX - caja.left) + 'px');
        tarjeta.style.setProperty('--my', Math.round(e.clientY - caja.top) + 'px');
      });
    });
  }

  // Botones que se acercan un poco al cursor (adaptado de Magnetic).
  // Se mide la zona que los envuelve (que no se mueve) para que el cálculo sea estable.
  function iniciarImanes() {
    if (!punteroFino || reducido) return;
    const INTENSIDAD = 0.3, RANGO = 110;
    $$('.lp-iman').forEach((zona) => {
      const boton = zona.firstElementChild;
      zona.addEventListener('pointermove', (e) => {
        const caja = zona.getBoundingClientRect();
        const dx = e.clientX - (caja.left + caja.width / 2);
        const dy = e.clientY - (caja.top + caja.height / 2);
        const fuerza = Math.max(0, 1 - Math.hypot(dx, dy) / RANGO);
        boton.style.transform = 'translate(' + (dx * INTENSIDAD * fuerza).toFixed(1) + 'px, ' + (dy * INTENSIDAD * fuerza).toFixed(1) + 'px)';
      });
      zona.addEventListener('pointerleave', () => { boton.style.transform = ''; });
    });
  }

  // ---------- 8. Arranque ----------
  const fondo = fondoMatematico ? crearFondoMatematico() : null;
  const barraNav = iniciarBarra();
  iniciarMenu();
  if (!reducido) $$('[data-lp-palabras]').forEach(partirEnPalabras);
  // El titular del héroe aparece al cargar; el de "Así funciona", al verse (iniciarRevelado)
  requestAnimationFrame(() => {
    const titulo = $('#tituloHeroe');
    if (titulo) titulo.classList.add('is-visible');
  });
  iniciarScroll(fondo, barraNav);
  iniciarMaqueta();
  iniciarRevelado();
  iniciarPausas();
  iniciarFocos();
  iniciarImanes();
})();
