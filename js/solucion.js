// Funciones compartidas por el pizarrón (index.php), el historial y la página de un problema:
// convertir expresiones a LaTeX, dibujar con KaTeX y mostrar una solución verificada.

// ---------- Utilidades ----------

// Crea un elemento con texto seguro (textContent nunca interpreta HTML)
function crear(etiqueta, clase, texto) {
  const el = document.createElement(etiqueta);
  if (clase) el.className = clase;
  if (texto !== undefined) el.textContent = texto;
  return el;
}

// Convierte nuestra sintaxis (2*x^2, sqrt(16)) a LaTeX para mostrarla con KaTeX.
function aLatex(expr) {
  let salida = '';
  const pila = [];              // qué cierra cada paréntesis: ')' o '}'
  for (let i = 0; i < expr.length; i++) {
    if (expr.startsWith('sqrt(', i)) {
      salida += '\\sqrt{'; pila.push('}'); i += 4;
    } else if (expr[i] === '^' && expr[i + 1] === '(') {
      salida += '^{'; pila.push('}'); i += 1;
    } else if (expr[i] === '^') {
      // potencia de un número o de x: 2^10 -> 2^{10}
      const m = expr.slice(i + 1).match(/^(-?\d+\.?\d*|x)/);
      if (m) { salida += '^{' + m[1] + '}'; i += m[1].length; } else { salida += '^'; }
    } else if (expr[i] === '(') {
      salida += '('; pila.push(')');
    } else if (expr[i] === ')') {
      salida += pila.length ? pila.pop() : ')';
    } else if (expr[i] === '*') {
      salida += '\\cdot ';
    } else {
      salida += expr[i];
    }
  }
  return salida;
}

function renderKatex(elemento, latex, modoBloque) {
  try {
    katex.render(latex, elemento, { throwOnError: false, displayMode: !!modoBloque });
  } catch (e) {
    elemento.textContent = latex;
  }
}

// La IA a veces envuelve el LaTeX en $...$; se lo quitamos
function limpiarLatex(l) {
  return String(l || '').replace(/^\s*(\$\$?|\\\()/, '').replace(/(\$\$?|\\\))\s*$/, '').trim();
}

// Dibuja en pantalla las expresiones de una lista (elementos con data-expr="2*x+3=11")
function renderizarExpresiones(selector) {
  document.querySelectorAll(selector).forEach(el => renderKatex(el, aLatex(el.dataset.expr || ''), false));
}

// ---------- Mostrar una solución ----------

// Expresión de la solución que se está mostrando (la usa el modo práctica)
let solucionActual = null;

// d tiene el formato que devuelve php/resolver.php (también lo reconstruye problema.php).
// Rellena los elementos de php/parcial_solucion.php.
function mostrarSolucionEn(d) {
  const selloEl = document.getElementById('sello');
  const resultadoEl = document.getElementById('resultadoFinal');
  const listaPasos = document.getElementById('listaPasos');
  selloEl.innerHTML = '';
  resultadoEl.innerHTML = '';
  listaPasos.innerHTML = '';
  document.getElementById('resumenFinal').textContent = '';
  solucionActual = d.expresion;

  // ----- Sello de verificación -----
  if (d.verificacion === 'verificado') {
    selloEl.className = 'sello bueno';
    selloEl.appendChild(crear('strong', '', '✓ Verificado con PHP: '));
    d.comprobaciones.forEach((c, i) => {
      const s = crear('span', 'comprobacion');
      renderKatex(s, aLatex(c), false);
      selloEl.appendChild(s);
      if (i < d.comprobaciones.length - 1) selloEl.appendChild(document.createTextNode('  '));
    });
  } else if (d.verificacion === 'inconsistente') {
    selloEl.className = 'sello malo';
    selloEl.appendChild(crear('strong', '', '⚠️ La IA y la verificación de PHP NO coinciden. '));
    selloEl.appendChild(document.createTextNode(
      'La respuesta de la IA no se da por correcta. El valor calculado por PHP aparece abajo.'));
    if (d.resultado_ia) selloEl.appendChild(crear('div', 'detalle', 'La IA dijo: ' + d.resultado_ia));
  } else {
    selloEl.className = 'sello medio';
    selloEl.appendChild(crear('strong', '', '⚠️ Sin verificar. '));
    selloEl.appendChild(document.createTextNode(
      'Este tipo de problema no se puede comprobar con PHP; revisa el resultado por tu cuenta.'));
  }

  // ----- Resultado final destacado -----
  if (d.resultado_texto !== null) {
    resultadoEl.appendChild(crear('div', 'etiqueta', d.verificacion === 'inconsistente'
      ? 'Resultado calculado por PHP' : 'Resultado'));
    const r = crear('div', 'valor');
    if (d.resultado_latex) renderKatex(r, d.resultado_latex, false); else r.textContent = d.resultado_texto;
    resultadoEl.appendChild(r);
  } else {
    resultadoEl.appendChild(crear('div', 'etiqueta', 'Resultado según la IA (sin verificar)'));
    resultadoEl.appendChild(crear('div', 'valor', d.resultado_ia || '—'));
  }

  // ----- Pasos -----
  d.pasos.forEach(p => {
    const li = crear('li');
    li.appendChild(crear('strong', '', p.titulo));
    const f = crear('div', 'formula');
    renderKatex(f, limpiarLatex(p.latex), true);
    li.appendChild(f);
    if (p.explicacion) li.appendChild(crear('p', 'explicacion', p.explicacion));
    listaPasos.appendChild(li);
  });
  // Si no coincidió con PHP, los pasos de la IA no son confiables: se ocultan por defecto
  document.getElementById('detallePasos').open = d.verificacion !== 'inconsistente';
  document.getElementById('resumenPasos').textContent = d.verificacion === 'inconsistente'
    ? 'Ver los pasos de la IA (no son confiables)' : 'Pasos';

  document.getElementById('resumenFinal').textContent = d.resumen || '';
  document.getElementById('panelSolucion').hidden = false;
}
