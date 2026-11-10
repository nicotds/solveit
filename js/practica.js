// Modo práctica: "Dame un ejercicio parecido" + corrección automática hecha por PHP.
// Necesita js/solucion.js (aLatex, renderKatex, solucionActual).

(function () {
  const btnSimilar = document.getElementById('btnSimilar');
  if (!btnSimilar) return;   // en el modo demo este botón no existe

  const panel = document.getElementById('panelPractica');
  const ejercicioEl = document.getElementById('ejercicioPractica');
  const respuesta = document.getElementById('respuestaPractica');
  const resultado = document.getElementById('resultadoPractica');
  const aviso = document.getElementById('avisoPractica');
  const enlace = document.getElementById('enlaceResolverEste');

  function mostrarResultado(texto, clase) {
    resultado.textContent = texto;
    resultado.className = 'resultado-practica ' + clase;
    resultado.hidden = false;
  }

  async function post(url, cuerpo) {
    const resp = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(cuerpo)
    });
    if (resp.status === 401) { window.location = 'login.php'; return null; }
    return resp.json();
  }

  // Pide un ejercicio nuevo parecido al que se está viendo
  async function pedirEjercicio() {
    if (!solucionActual) return;
    aviso.hidden = true;
    btnSimilar.disabled = true;
    document.getElementById('btnOtro').disabled = true;
    const textoOriginal = btnSimilar.textContent;
    btnSimilar.textContent = 'Creando ejercicio…';
    try {
      const d = await post('php/similar.php', { expresion: solucionActual });
      if (!d) return;
      if (!d.ok) {
        aviso.textContent = d.mensaje || 'No se pudo crear el ejercicio.';
        aviso.hidden = false;
        return;
      }
      renderKatex(ejercicioEl, aLatex(d.expresion), true);
      respuesta.value = '';
      resultado.hidden = true;
      enlace.href = 'index.php?e=' + encodeURIComponent(d.expresion);
      panel.hidden = false;
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      respuesta.focus();
    } catch (err) {
      aviso.textContent = 'No se pudo contactar con el servidor.';
      aviso.hidden = false;
    } finally {
      btnSimilar.disabled = false;
      document.getElementById('btnOtro').disabled = false;
      btnSimilar.textContent = textoOriginal;
    }
  }

  async function comprobar() {
    if (!respuesta.value.trim()) { mostrarResultado('Escribe tu respuesta primero.', 'mal'); return; }
    const d = await post('php/comprobar.php', { respuesta: respuesta.value });
    if (!d) return;
    mostrarResultado(d.mensaje || 'No se pudo comprobar.', d.ok && d.correcto ? 'bien' : 'mal');
  }

  async function revelar() {
    const d = await post('php/comprobar.php', { revelar: true });
    if (!d) return;
    mostrarResultado(d.ok ? 'La respuesta es: ' + d.respuesta_correcta : (d.mensaje || 'Error.'), 'info');
  }

  btnSimilar.onclick = pedirEjercicio;
  document.getElementById('btnOtro').onclick = pedirEjercicio;
  document.getElementById('btnComprobar').onclick = comprobar;
  document.getElementById('btnRevelar').onclick = revelar;
  respuesta.addEventListener('keydown', e => { if (e.key === 'Enter') comprobar(); });
})();
