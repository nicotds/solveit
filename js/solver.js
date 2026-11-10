// Flujo del pizarrón: Leer (IA) -> confirmar/corregir -> Resolver.
// Usa Pizarron (pizarron.js) y las funciones de solucion.js.

const MODO_DEMO = document.body.dataset.demo === '1';   // ?demo=1 : sin IA, ejemplos guardados

const btnLeer = document.getElementById('btnLeer');
const btnTeclado = document.getElementById('btnTeclado');
const btnResolver = document.getElementById('btnResolver');
const avisoLectura = document.getElementById('avisoLectura');
const panelConfirmar = document.getElementById('panelConfirmar');
const campoExpresion = document.getElementById('campoExpresion');
const vistaPrevia = document.getElementById('vistaPrevia');
const listaDudas = document.getElementById('listaDudas');
const errorCampo = document.getElementById('errorCampo');
const panelSolucion = document.getElementById('panelSolucion');
const avisoResolver = document.getElementById('avisoResolver');

// Imagen del pizarrón de la última lectura exitosa (se guarda con el problema en el historial).
// Si el usuario escribe con el teclado, no hay imagen.
let imagenLeida = null;

// ---------- Utilidades ----------

function mostrarAviso(texto) {
  avisoLectura.textContent = texto;
  avisoLectura.hidden = !texto;
}

// Misma lista blanca que usa PHP: dígitos, x, + - * / ^ ( ) . = y sqrt
function expresionValida(expr) {
  const sinSqrt = expr.replace(/sqrt/g, '');
  return expr.length > 0 && expr.length <= 100 &&
         /^[0-9x+\-*\/^().=]+$/.test(sinSqrt) &&
         (expr.match(/=/g) || []).length <= 1;
}

// ---------- Panel de confirmación ----------

function actualizarVistaPrevia() {
  const expr = campoExpresion.value.replace(/\s+/g, '');
  const valida = expresionValida(expr);

  errorCampo.hidden = expr === '' || valida;
  errorCampo.textContent = 'Solo se permiten números, x, + - * / ^ ( ) . = y sqrt.';
  btnResolver.disabled = !valida;

  if (expr === '') {
    vistaPrevia.textContent = '';
  } else {
    renderKatex(vistaPrevia, aLatex(expr), true);
  }
}

function abrirPanel(expresion, dudas) {
  campoExpresion.value = expresion;
  listaDudas.innerHTML = '';
  (dudas || []).forEach(d => listaDudas.appendChild(crear('li', '', d)));
  document.getElementById('cajaDudas').hidden = !(dudas && dudas.length);
  panelConfirmar.hidden = false;
  panelSolucion.hidden = true;
  actualizarVistaPrevia();
  campoExpresion.focus();
}

campoExpresion.addEventListener('input', () => {
  actualizarVistaPrevia();
  panelSolucion.hidden = true;   // la solución mostrada ya no corresponde a lo escrito
});

// ---------- Botón Leer ----------

if (btnLeer) {
  btnLeer.onclick = async () => {
    mostrarAviso('');
    if (Pizarron.estaVacio()) {
      mostrarAviso('Primero escribe algo en el pizarrón');
      return;
    }

    btnLeer.disabled = true;
    btnLeer.textContent = 'Leyendo…';
    try {
      const imagen = Pizarron.exportarPNG();
      const resp = await fetch('php/leer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ imagen })
      });
      if (resp.status === 401) { window.location = 'login.php'; return; }
      const datos = await resp.json();

      if (!datos.ok) {
        panelConfirmar.hidden = true;
        mostrarAviso(datos.mensaje || 'No se pudo leer lo escrito.');
      } else {
        imagenLeida = imagen;
        abrirPanel(datos.expresion, datos.dudas);
      }
    } catch (err) {
      mostrarAviso('No se pudo contactar con el servidor. Revisa que Apache esté encendido.');
    } finally {
      btnLeer.disabled = false;
      btnLeer.textContent = 'Leer';
    }
  };
}

// Alternativa: escribir la expresión con el teclado
btnTeclado.onclick = () => {
  mostrarAviso('');
  imagenLeida = null;
  abrirPanel('', []);
};

// Modo demo: botones con ejemplos guardados
document.querySelectorAll('.ejemplo-demo').forEach(boton => {
  boton.onclick = () => {
    mostrarAviso('');
    imagenLeida = null;
    abrirPanel(boton.dataset.expr, []);
  };
});

// Ejercicio precargado desde la URL (?e=2*x+3=11), por ejemplo desde el modo práctica
(function () {
  const e = new URLSearchParams(window.location.search).get('e');
  if (e && expresionValida(e.replace(/\s+/g, ''))) {
    imagenLeida = null;
    abrirPanel(e, []);
  }
})();

// ---------- Botón Resolver ----------

btnResolver.onclick = async () => {
  const expr = campoExpresion.value.replace(/\s+/g, '');
  if (!expresionValida(expr)) return;

  avisoResolver.hidden = true;
  btnResolver.disabled = true;
  btnResolver.textContent = 'Resolviendo…';
  try {
    const resp = await fetch('php/resolver.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        expresion: expr,
        nivel: document.querySelector('input[name=nivel]:checked').value,
        demo: MODO_DEMO,
        imagen: imagenLeida          // null si se escribió con el teclado
      })
    });
    if (resp.status === 401) { window.location = 'login.php'; return; }
    const datos = await resp.json();

    if (!datos.ok) {
      panelSolucion.hidden = true;
      avisoResolver.textContent = datos.mensaje || 'No se pudo resolver.';
      avisoResolver.hidden = false;
    } else {
      mostrarSolucionEn(datos);

      const estado = document.getElementById('estadoGuardado');
      estado.innerHTML = '';
      if (datos.id) {
        estado.appendChild(document.createTextNode('✓ Guardado en tu historial. '));
        const a = crear('a', '', 'Abrirlo');
        a.href = 'problema.php?id=' + encodeURIComponent(datos.id);
        estado.appendChild(a);
      } else {
        estado.textContent = 'No se pudo guardar en el historial.';
      }
      panelSolucion.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  } catch (err) {
    avisoResolver.textContent = 'No se pudo contactar con el servidor. Revisa que Apache esté encendido.';
    avisoResolver.hidden = false;
  } finally {
    btnResolver.textContent = 'Resolver';
    actualizarVistaPrevia();   // vuelve a habilitar el botón si la expresión sigue siendo válida
  }
};
