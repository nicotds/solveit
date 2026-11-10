// Pizarrón digital: dibujo a mano alzada, borrador, deshacer, limpiar y exportar a PNG.
// Expone el objeto global "Pizarron" para que solver.js (Fase 4) lo use.
const Pizarron = (function () {
  const lienzo = document.getElementById('lienzo');
  const ctx = lienzo.getContext('2d', { willReadFrequently: true });

  const GROSOR_LAPIZ = 4;      // fino por defecto
  const GROSOR_BORRADOR = 24;
  const MAX_HISTORIAL = 30;    // estados que se pueden deshacer

  let dibujando = false;
  let modoBorrador = false;
  const historial = [];        // capturas del lienzo ANTES de cada trazo

  // --- Preparar el lienzo con fondo blanco SÓLIDO (el PNG no puede ser transparente) ---
  function pintarFondo() {
    ctx.globalCompositeOperation = 'source-over';
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, lienzo.width, lienzo.height);
  }

  function configurarPincel() {
    ctx.strokeStyle = modoBorrador ? '#fff' : '#000'; // borrar = pintar de blanco
    ctx.lineWidth = modoBorrador ? GROSOR_BORRADOR : GROSOR_LAPIZ;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
  }

  // --- Coordenadas correctas aunque el CSS escale el canvas ---
  function posicion(e) {
    const r = lienzo.getBoundingClientRect();
    return {
      x: (e.clientX - r.left) * (lienzo.width / r.width),
      y: (e.clientY - r.top) * (lienzo.height / r.height)
    };
  }

  // --- Historial para deshacer ---
  function guardarEstado() {
    historial.push(ctx.getImageData(0, 0, lienzo.width, lienzo.height));
    if (historial.length > MAX_HISTORIAL) historial.shift();
    actualizarBotones();
  }

  function deshacer() {
    if (historial.length === 0) return;
    ctx.putImageData(historial.pop(), 0, 0);
    actualizarBotones();
    avisarCambio();
  }

  function limpiar() {
    guardarEstado(); // así "Limpiar" también se puede deshacer
    pintarFondo();
    avisarCambio();
  }

  // --- Eventos de dibujo (Pointer Events: mouse, dedo y lápiz con el mismo código) ---
  lienzo.addEventListener('pointerdown', e => {
    if (e.pointerType === 'mouse' && e.button !== 0) return; // solo botón izquierdo
    e.preventDefault();
    guardarEstado();
    dibujando = true;
    lienzo.setPointerCapture(e.pointerId);
    configurarPincel();
    const p = posicion(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
    ctx.lineTo(p.x + 0.01, p.y); // permite dibujar puntos
    ctx.stroke();
  });

  lienzo.addEventListener('pointermove', e => {
    if (!dibujando) return;
    const p = posicion(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
  });

  function terminarTrazo() {
    if (!dibujando) return;
    dibujando = false;
    avisarCambio();
  }
  lienzo.addEventListener('pointerup', terminarTrazo);
  lienzo.addEventListener('pointercancel', terminarTrazo);

  // --- Analizar el lienzo: ¿está vacío? ¿cuál es el área escrita? ---
  function areaEscrita() {
    const datos = ctx.getImageData(0, 0, lienzo.width, lienzo.height).data;
    let minX = lienzo.width, minY = lienzo.height, maxX = -1, maxY = -1;
    for (let y = 0; y < lienzo.height; y++) {
      for (let x = 0; x < lienzo.width; x++) {
        const i = (y * lienzo.width + x) * 4;
        if (datos[i] < 200) { // pixel oscuro (no blanco)
          if (x < minX) minX = x;
          if (x > maxX) maxX = x;
          if (y < minY) minY = y;
          if (y > maxY) maxY = y;
        }
      }
    }
    return maxX < 0 ? null : { minX, minY, maxX, maxY };
  }

  function estaVacio() {
    return areaEscrita() === null;
  }

  // --- Exportar a PNG recortado al área escrita, con margen y fondo blanco ---
  function exportarPNG() {
    const area = areaEscrita();
    if (!area) return null;
    const margen = 24;
    const x = Math.max(0, area.minX - margen);
    const y = Math.max(0, area.minY - margen);
    const ancho = Math.min(lienzo.width, area.maxX + margen) - x;
    const alto = Math.min(lienzo.height, area.maxY + margen) - y;

    const copia = document.createElement('canvas');
    copia.width = ancho;
    copia.height = alto;
    const c = copia.getContext('2d');
    c.fillStyle = '#fff';
    c.fillRect(0, 0, ancho, alto);
    c.drawImage(lienzo, x, y, ancho, alto, 0, 0, ancho, alto);
    return copia.toDataURL('image/png'); // "data:image/png;base64,..."
  }

  // --- Botones y avisos ---
  const btnLapiz = document.getElementById('btnLapiz');
  const btnBorrador = document.getElementById('btnBorrador');
  const btnDeshacer = document.getElementById('btnDeshacer');

  function actualizarBotones() {
    btnLapiz.classList.toggle('activo', !modoBorrador);
    btnBorrador.classList.toggle('activo', modoBorrador);
    btnDeshacer.disabled = historial.length === 0;
  }

  function avisarCambio() {
    document.dispatchEvent(new CustomEvent('pizarron-cambio'));
  }

  btnLapiz.onclick = () => { modoBorrador = false; actualizarBotones(); };
  btnBorrador.onclick = () => { modoBorrador = true; actualizarBotones(); };
  btnDeshacer.onclick = deshacer;
  document.getElementById('btnLimpiar').onclick = limpiar;

  // Atajo Ctrl+Z
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z' &&
        !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
      e.preventDefault();
      deshacer();
    }
  });

  pintarFondo();
  actualizarBotones();

  return { estaVacio, exportarPNG };
})();
