# SolveIt — Landing page (briefing para Claude Code)

> **Prompt corto para pegar en la sesión de Claude Code** (el documento hace el resto):
>
> ```
> Lee `landing-solveit.md` completo. Es el briefing para crear la landing page de SolveIt,
> el proyecto que está en esta carpeta. La referencia visual es el video
> `referencia/site-zenith-orbit.webm`: extrae fotogramas con ffmpeg como indica la sección 2
> y míralos ANTES de escribir código.
> Antes de tocar nada, lee `index.php`, `php/auth.php`, `css/estilos.css` y la barra
> `php/barra.php`, y dime qué rutas, colores y tipografías ya existen.
> Trabaja por fases (sección 12): al terminar cada una, enséñame capturas y espera mi
> confirmación. No modifiques archivos existentes sin avisarme primero.
> ```

---

## 1. Objetivo

Crear la **página de inicio pública (landing page)** de **SolveIt**, con el mismo estilo y la misma sensación que el video de referencia (un sitio llamado "Zenith"): oscura, morada, elegante, con un héroe a pantalla completa, una barra de navegación flotante tipo píldora de vidrio, un titular grande en tipografía serif y una transición al hacer scroll hacia una sección con una maqueta de la aplicación.

**SolveIt** (para contexto): web con un pizarrón donde el usuario escribe a mano una operación o ecuación básica; la IA la lee, el usuario confirma o corrige, se resuelve paso a paso y el resultado se **verifica de forma independiente con PHP**. Proyecto de una materia de programación, hecho con HTML, CSS, PHP, JavaScript y MySQL sobre XAMPP.

La landing sustituye la "puerta de entrada" del proyecto: hoy, entrar a `index.php` sin sesión manda directo a `login.php`.

## 2. Archivos de referencia y cómo mirarlos

El video está en `referencia/site-zenith-orbit.webm` (1280×720, se repite en bucle, unos 20 s). **No puedes "ver" un video directamente**, así que extrae fotogramas y míralos como imágenes:

```bash
mkdir -p referencia/frames
ffmpeg -i referencia/site-zenith-orbit.webm -vf "fps=1/2,scale=960:-1" referencia/frames/f_%02d.png
```

Si no hay `ffmpeg`, pídele al usuario que lo instale o que te pase capturas del video. Mira todos los fotogramas (son unos 10) antes de escribir código.

> **Limitación honesta:** el análisis de la sección 3 sale de fotogramas sueltos (uno cada 2 segundos). No se vio el código del sitio original, así que **tipografías, colores y tiempos exactos son aproximaciones**. Si algo del video contradice este documento, **manda el video**.

## 3. Qué se ve en el video (análisis)

### 3.1 Barra de navegación (fija durante todo el recorrido)
- **Izquierda:** logo con un icono de **estrella de 4 puntas** (destello) en blanco + el nombre en tipografía **serif** ("Zenith").
- **Centro:** una **píldora flotante de vidrio** (fondo oscuro translúcido con desenfoque, esquinas muy redondeadas) con enlaces en sans pequeña: *Platform ▾, Pricing, Resources, Blog*.
- **Derecha:** dos botones tipo píldora: **"Login"** (oscuro, translúcido) y **"Book a demo"** (claro, lavanda casi blanco, texto oscuro) como botón principal.
- La barra se mantiene en su sitio mientras se hace scroll; su fondo se oscurece un poco sobre el contenido.

### 3.2 Héroe (sección 1)
- **Fondo a pantalla completa:** imagen o video de un cohete subiendo entre nubes, teñido de **violeta**. Hay un **degradado violeta intenso** en la parte inferior que se funde con la siguiente sección. Las nubes se desplazan lentamente.
- **Insignia pequeña** centrada: un rectángulo redondeado oscuro y translúcido con el texto "Backed by: • Halley Ventures".
- **Titular grande**, serif, peso ligero, interlineado muy apretado, centrado, blanco, en **3 líneas**: "Launch your mission data past the horizon".
- **Subtítulo** centrado, en sans, color lavanda claro, 2 líneas.
- No hay botones dentro del héroe; las llamadas a la acción están en la barra.

### 3.3 Transición (al hacer scroll)
- El contenido del héroe se **desenfoca y se desvanece** (efecto blur + fade) mientras sube.
- Las nubes pasan a un lado y el fondo se va a **gris carbón oscuro** (con textura de grano fino).
- Aparece una **esfera enorme de color violeta con brillo** (como un planeta que sube desde abajo), que queda detrás del contenido de la siguiente sección. Sus bordes tienen un resplandor.

### 3.4 Sección 2 (la esfera + la maqueta)
- Insignia pequeña: "• Live Telemetry".
- Titular serif en 3 líneas, más pequeño que el del héroe: "Clear the atmosphere with predictive infrastructure that thinks ahead".
- Debajo, una **maqueta de navegador** (ventana con los tres puntos de macOS rojo/amarillo/verde, barra de URL con candado y "zenith.ai"), que **sube y se acomoda** con el scroll. Dentro hay un **panel oscuro** con:
  - Pestañas grandes en serif/sans: **Orbit** (activa, blanca) · *Fleet* · *Signals* (apagadas).
  - Tarjetas de vidrio oscuro: *Revenue* (una cifra grande y una gráfica de área), *Launch Leads* (matriz de puntos), *Sales Velocity* (barras), *Mission Log* (lista con etiquetas), *Trajectory* (tres métricas).
- Todo con bordes sutiles de 1 px translúcidos y esquinas redondeadas.

### 3.5 Ritmo general
- El video **vuelve al inicio** al terminar (bucle). La landing real debe verse bien al detenerse en cualquier punto del scroll.

## 4. Adaptación a SolveIt

**No copies el contenido ni el nombre ni las imágenes de Zenith.** Se copia la **estructura, el estilo y las sensaciones**; todo el contenido es de SolveIt.

| Elemento del video | Equivalente en SolveIt |
|---|---|
| Cohete subiendo entre nubes | **Fondo matemático**: ver sección 6 |
| Estrella de 4 puntas como logo | Mismo icono de destello, o un símbolo propio (por ejemplo una "√" o "∑" estilizada). Nombre: **SolveIt** |
| "Backed by: Halley Ventures" | Insignia **"Proyecto académico"** o **"Verificado con PHP"**. **No inventes patrocinadores, cifras, reseñas ni logos de empresas** |
| Maqueta del dashboard | **Maqueta de SolveIt** (sección 8), construida en HTML/CSS, no como imagen |
| Pestañas Orbit / Fleet / Signals | **Escribir / Confirmar / Resolver** |
| "Book a demo" | **"Crear cuenta"** (botón claro principal) |
| "Login" | **"Iniciar sesión"** |

### 4.1 Paleta (aproximada, sacada a ojo del video)
Definir como variables CSS en `:root`:

| Variable | Valor sugerido | Uso |
|---|---|---|
| `--lp-bg` | `#0e0c15` | fondo base carbón-violeta |
| `--lp-surface` | `rgba(255,255,255,0.06)` | tarjetas y píldoras de vidrio |
| `--lp-border` | `rgba(255,255,255,0.12)` | bordes de 1 px |
| `--lp-violet-500` | `#7c5cff` | violeta principal |
| `--lp-violet-400` | `#9b82ff` | resplandor y degradados |
| `--lp-violet-300` | `#b9a8ff` | esfera, acentos |
| `--lp-lavender` | `#ece8ff` | botón claro, texto principal |
| `--lp-text-dim` | `rgba(236,232,255,0.7)` | subtítulos |
| `--lp-ok` | `#4ade80` | sello "verificado" |

Antes de fijarlas, **mira `css/estilos.css`**: si la app ya tiene colores, propón cómo unificarlos y pregunta al usuario.

### 4.2 Tipografía
- **Titulares:** una serif de alto contraste y peso ligero (en el video parece del estilo de *Instrument Serif* o *Newsreader*; es una aproximación mía).
- **Texto e interfaz:** una sans neutra (por ejemplo *Inter*).
- **Importante:** el proyecto se presenta en vivo y puede no haber internet. Si hay red, **descarga las fuentes en `.woff2` a `fonts/`** y cárgalas con `@font-face`. Si no, usa Google Fonts **con una pila de respaldo** (`Georgia, 'Times New Roman', serif` y `system-ui, sans-serif`) y que la página se vea bien aun sin las fuentes.
- Titular del héroe: tamaño fluido (`clamp(2.6rem, 7vw, 5.5rem)`), `line-height` ≈ 0.95–1.0, `letter-spacing` ligeramente negativo.

### 4.3 Textos (en español; ajusta si el usuario lo pide)

**Navegación:** Inicio · Cómo funciona · Características · Demo — botones: **Iniciar sesión** / **Crear cuenta**.

**Héroe**
- Insignia: `• Verificado con PHP`
- Titular: **Escribe tu ecuación. Entiende cada paso.**
- Subtítulo: *Dibuja una operación en el pizarrón, confirma lo que leyó la IA y obtén la solución explicada y comprobada de forma independiente.*

**Sección 2**
- Insignia: `• Así funciona`
- Titular: **De tu trazo a una solución verificada**

**Cómo funciona (3 pasos)**
1. **Escribe** — Dibuja la operación o ecuación con el mouse, el dedo o un lápiz.
2. **Confirma** — La IA transcribe lo que leyó y tú lo corriges antes de resolver.
3. **Resuelve y verifica** — Recibes los pasos explicados y PHP comprueba el resultado por su cuenta.

**Características (4 tarjetas)**
- **Tú tienes la última palabra** — Corriges la lectura antes de resolver, o escribes con el teclado.
- **Verificación independiente** — Un evaluador propio en PHP recalcula el resultado; si no coincide con la IA, no se presenta como correcto.
- **Explicación a tu nivel** — Modo básico o avanzado, con los pasos y su fórmula.
- **Practica** — Historial de tus problemas y ejercicios parecidos con corrección automática.

**Cierre (CTA)**
- Titular: **Pruébalo ahora**
- Botones: **Crear cuenta** (principal) · **Ver demo** (secundario)
- Línea pequeña: *Alcance: operaciones básicas y ecuaciones de una variable.* (decir el alcance con honestidad)

**Pie:** `SolveIt · Proyecto de [materia] · [nombres de los autores]` — dejar los corchetes para que el usuario los complete. Tecnologías: HTML, CSS, PHP, JavaScript y MySQL.

**Regla de contenido:** nada de estadísticas, porcentajes de acierto, reseñas ni "confían en nosotros" inventados. Todo lo que se afirme debe ser cierto en el proyecto.

## 5. Estructura de secciones

1. **Barra fija** (píldora de vidrio).
2. **Héroe** a pantalla completa (≥ 100vh): fondo matemático + insignia + titular + subtítulo.
3. **Sección "Así funciona"**: esfera violeta detrás + titular + **maqueta de navegador** con pestañas Escribir / Confirmar / Resolver.
4. **Cómo funciona**: 3 pasos en fila (en columna en móvil).
5. **Características**: 4 tarjetas de vidrio en cuadrícula.
6. **CTA final**.
7. **Pie**.

## 6. El fondo (la parte creativa)

Se pide un **fondo de matemáticas o, si es demasiado, un fondo estético de color**. Implementa ambos como modos y deja el matemático por defecto.

### 6.1 Modo A — fondo matemático (por defecto)
Capas, de atrás hacia adelante, **todo con CSS/SVG/canvas, sin librerías ni imágenes pesadas**:

1. **Degradado base:** carbón-violeta arriba, con un **resplandor violeta intenso en la parte inferior** (como en el video).
2. **Cuadrícula tipo papel milimetrado:** líneas muy tenues (opacidad ~0.05–0.08), con una **máscara radial** para que se desvanezca hacia los bordes. Debe ir en el CSS del fondo.
3. **Símbolos y ecuaciones flotantes** (canvas o elementos posicionados): `∑ ∫ π √ ∞ x² Δ θ ± ÷ ×`, y ecuaciones cortas (`2x + 3 = 11`, `x² − 5x + 6 = 0`, `a² + b² = c²`). Deben derivar muy lentamente, con **distinto tamaño, opacidad y desenfoque según la "profundidad"** (los más lejanos, más pequeños, tenues y borrosos) y un **parallax suave** al mover el mouse o al hacer scroll. Pocos elementos (15–30), no una lluvia.
4. **Foco central (equivalente al cohete):** una **curva brillante** (una parábola o una onda senoidal) que se **dibuja sola** con animación de trazo (`stroke-dasharray` / `stroke-dashoffset`) y un **punto luminoso** que recorre la curva en bucle. Con un resplandor violeta (`filter: drop-shadow` o `blur`). Debe quedar **detrás del texto** y sin restarle legibilidad.
5. **Grano fino** sobre todo (SVG `feTurbulence` como máscara o patrón con opacidad ~0.05) como en el video.
6. Un **degradado oscuro** detrás del texto del héroe para asegurar el contraste.

### 6.2 Modo B — fondo estético de color
Si el usuario prefiere algo más sobrio: solo las capas 1, 5 y 6 + una esfera de resplandor lenta. Se activa con una clase en `<body>` o una constante en `js/landing.js`.

### 6.3 Rendimiento
- Canvas con `requestAnimationFrame`, **pausado cuando la pestaña no está visible** (`document.hidden`) y cuando el héroe sale de pantalla (`IntersectionObserver`).
- Ajustar la cantidad de elementos según el ancho de pantalla.
- Debe ir fluido en una laptop normal, sin que el ventilador se note.

## 7. Animaciones y scroll

Sin librerías: JavaScript propio con `requestAnimationFrame`, `IntersectionObserver` y variables CSS.

- **Progreso de scroll del héroe** `--p` (0 a 1): se calcula en JS y se asigna como variable CSS. Con `--p`:
  - El contenido del héroe pasa de `opacity: 1; filter: blur(0); transform: translateY(0)` a `opacity: 0; filter: blur(14px); transform: translateY(-40px) scale(.98)`.
  - El fondo oscurece y la curva/los símbolos se desplazan en parallax.
  - La **esfera violeta sube** desde abajo (`translateY` de ~60% a 0) y escala ligeramente.
- **Maqueta de navegador:** entra desde abajo con `translateY` y `scale` de 0.92 → 1 y fundido, ligada al scroll o con `IntersectionObserver`.
- **Revelado** de las secciones siguientes: fundido + subida suave de 16–24 px al entrar en pantalla, con retraso escalonado entre tarjetas.
- **Barra:** al pasar de ~40 px de scroll, el fondo de la píldora se vuelve más opaco.
- **Pestañas de la maqueta** (Escribir / Confirmar / Resolver): se pueden pulsar y, si el usuario no interactúa, **rotan solas** cada ~4 s con una transición suave.
- Respetar **`prefers-reduced-motion: reduce`**: sin parallax, sin blur de scroll, sin rotación automática, sin canvas animado (fondo estático).
- Duraciones y curvas: 300–700 ms, `cubic-bezier(.22,1,.36,1)`; nada brusco.

## 8. La maqueta de SolveIt (HTML/CSS, no una captura)

Una ventana de navegador falsa, construida en HTML y CSS:
- Marco con los tres puntos (rojo, amarillo, verde), barra de URL con candado y el texto `localhost/solveit`.
- Cuerpo oscuro con pestañas **Escribir · Confirmar · Resolver** (la activa grande y blanca; las otras apagadas, como en el video).

**Contenido por pestaña** (todo estático e inventado a propósito como ejemplo, sin datos reales):

- **Escribir:** tarjeta "Pizarrón" con una ecuación **escrita a mano en SVG** (`2x + 3 = 11`) que se dibuja con animación de trazo, con un lápiz/cursor luminoso. Botones simulados: Lápiz · Borrador · Deshacer · **Leer**.
- **Confirmar:** tarjeta "Lectura de la IA" con un campo `2*x+3=11` y la fórmula `2x + 3 = 11` debajo, y un aviso "Revisa: el tercer símbolo podría ser + o ×". Botón **Resolver**.
- **Resolver:** tarjeta con los pasos:
  1. Restar 3 en ambos lados → `2x = 8`
  2. Dividir entre 2 → `x = 4`
  y un **sello verde**: `✓ Verificado · 2(4) + 3 = 11`.

Puedes usar KaTeX (ya está en `lib/katex/`) para las fórmulas, o escribirlas con HTML/CSS sencillo. Si usas KaTeX, que la landing **siga viéndose bien** si no carga.

Las tarjetas son de vidrio oscuro (`--lp-surface`, borde `--lp-border`, `backdrop-filter: blur`), esquinas de 16–20 px.

## 9. Integración con el proyecto (no romper nada)

### 9.1 Archivos nuevos (todos con prefijo `lp-` / `landing`)
```
solveit/
├── inicio.php              # Landing pública (PHP para poder detectar la sesión)
├── css/landing.css         # Estilos solo de la landing. Clases con prefijo .lp-
├── js/landing.js           # Fondo, scroll, pestañas, revelado
├── fonts/                  # (opcional) .woff2 locales
└── referencia/             # video + fotogramas (no se publica)
```

- **No modificar** `css/estilos.css` ni los `.js` existentes. La landing tiene su propio CSS para no chocar con el de la app.
- `inicio.php` es **pública**: **no** llamar a `requerir_login()` (esa función redirige). Lee `php/auth.php` para ver cómo comprobar si hay sesión sin redirigir. Si hay sesión activa, el botón "Crear cuenta" se convierte en **"Ir al pizarrón"** (`index.php`) y "Iniciar sesión" en **"Cerrar sesión"** o desaparece; decide y dímelo.
- Escapar con `e()` / `htmlspecialchars()` cualquier dato de sesión que se imprima.

### 9.2 Enlaces (verifica que existan antes de usarlos)
- Iniciar sesión → `login.php`
- Crear cuenta → `registro.php`
- Ir al pizarrón → `index.php`
- Ver demo → `index.php?demo=1` — **comprueba si el modo demo exige sesión**. Si la exige, no lo enlaces como si fuera público: ofrece "Ver demo" solo a usuarios con sesión, o envía a `registro.php`, y **pregunta al usuario qué prefiere**.
- Todas las rutas **relativas** (el proyecto vive en `C:\xampp\htdocs\solveit\`).

### 9.3 Decisión que debe tomar el usuario (preguntar, no asumir)
Hoy `index.php` sin sesión redirige a `login.php`. Para que la landing sea la puerta de entrada, habría que cambiar esa redirección a `inicio.php`. **Es una modificación a un archivo existente: propónla, explica el cambio exacto y espera el "sí" del usuario.** Mientras tanto, la landing es accesible en `http://localhost/solveit/inicio.php`.

## 10. Restricciones técnicas

- **HTML, CSS, PHP y JavaScript puros.** Sin frameworks, sin Composer, sin Node, sin Tailwind, sin librerías de animación. Todo debe funcionar copiando la carpeta a `htdocs` de XAMPP.
- **Sin dependencias externas en tiempo de ejecución** (CDN, fuentes remotas sin respaldo, imágenes de internet): la presentación puede ser sin internet. Todo lo necesario va dentro del proyecto.
- **No usar imágenes ni videos con derechos ajenos.** No incrustar el video de referencia ni sus fotogramas en la landing. Todo el arte es propio (CSS, SVG, canvas).
- Código **comentado en español** y claro: el autor tiene que poder explicarlo en una exposición.
- HTML semántico (`header`, `nav`, `main`, `section`, `footer`), un solo `h1`.

## 11. Responsive y accesibilidad

- Probar a **1440, 1024, 768 y 390 px**. Sin scroll horizontal.
- Móvil: la píldora de navegación se reemplaza por un **menú de hamburguesa** (botón accesible, se cierra con Esc y al elegir un enlace); los botones de sesión quedan dentro del menú o en versión compacta. Titular más pequeño; la maqueta ocupa el ancho disponible y puede simplificarse (menos tarjetas).
- Contraste suficiente del texto sobre el fondo (revisar el subtítulo y la insignia).
- Foco visible en enlaces y botones; navegable con teclado.
- Pestañas de la maqueta con roles ARIA (`tablist`, `tab`, `tabpanel`).
- `alt` o `aria-hidden` apropiados en elementos decorativos (el fondo es decorativo: `aria-hidden="true"`).
- `prefers-reduced-motion` respetado (sección 7).
- `<title>` y `<meta name="description">` en español; `lang="es"`.

## 12. Fases (una a la vez; capturas al final de cada una)

| Fase | Entregable | Cómo se comprueba |
|---|---|---|
| **0. Reconocimiento** | Lee los archivos de la sección 9, extrae y mira los fotogramas, y resume en 10 líneas qué entendió y qué preguntas tiene | El usuario confirma que lo entendió bien |
| **1. Esqueleto** | `inicio.php`, `landing.css`, `landing.js` vacío; barra fija en píldora + héroe con textos (sin fondo elaborado) | Se ve la estructura y los enlaces funcionan |
| **2. Fondo matemático** | Capas de la sección 6.1 y el modo B | Se ve vivo pero legible; el CPU no se dispara |
| **3. Scroll y esfera** | Desenfoque del héroe, esfera que sube, transición | Al hacer scroll se parece al video |
| **4. Maqueta** | Ventana de navegador con las 3 pestañas y sus animaciones | Las pestañas cambian solas y al pulsar |
| **5. Resto de secciones** | Cómo funciona, características, CTA y pie | Contenido completo y revelado suave |
| **6. Responsive y accesibilidad** | Menú móvil, `prefers-reduced-motion`, contraste, teclado | Pruebas a 390/768/1024/1440 px |
| **7. Pulido y revisión** | Limpieza de código, comentarios, revisión final | Criterios de la sección 13 |

**Cómo mostrar el progreso:** si tienes Playwright o un navegador controlable, toma capturas a 1440×900 y 390×844 (y a mitad del scroll). Si no, dile al usuario exactamente qué abrir y qué probar.

## 13. Criterios de aceptación

- [ ] `http://localhost/solveit/inicio.php` abre sin iniciar sesión y se ve como el video de referencia en estructura y estilo, pero con identidad y contenido de SolveIt.
- [ ] La barra es una píldora de vidrio fija, con "Iniciar sesión" y "Crear cuenta".
- [ ] El héroe tiene el fondo matemático, la insignia, el titular serif de 3 líneas y el subtítulo.
- [ ] Al hacer scroll, el héroe se desenfoca y se desvanece, la esfera violeta sube y aparece la maqueta.
- [ ] La maqueta tiene las pestañas Escribir / Confirmar / Resolver con contenido coherente con la app real.
- [ ] Hay una opción de fondo solo de color (modo B).
- [ ] Todos los enlaces llevan a archivos que existen; el de la demo respeta si exige sesión.
- [ ] **No** hay cifras, reseñas, logos ni patrocinadores inventados; el alcance se dice con honestidad.
- [ ] Funciona **sin internet** (fuentes, scripts y estilos locales o con respaldo).
- [ ] No hay scroll horizontal de 390 px a 1440 px; el menú móvil funciona.
- [ ] Con `prefers-reduced-motion` todo queda estático y legible.
- [ ] No se modificó ningún archivo existente sin el permiso del usuario.
- [ ] El canvas se pausa cuando no se ve y el uso de CPU es razonable.
- [ ] El código está comentado en español.

## 14. Qué NO hacer

- No copiar el texto, el nombre "Zenith", el logo ni las imágenes del sitio de referencia.
- No inventar datos, métricas, testimonios ni patrocinadores.
- No usar frameworks, CDN obligatorios ni librerías de animación.
- No tocar `css/estilos.css`, `index.php` ni otros archivos del proyecto sin avisar.
- No llamar a `requerir_login()` en la landing.
- No mostrar la clave de Gemini ni rutas internas en el código de la página.
- No dejar el video de referencia dentro de la carpeta pública si el proyecto se sube a un servidor (la carpeta `referencia/` es solo para trabajar).

---

**Nota final para quien construya:** lo que hace especial al video es la **atmósfera** (oscuro, violeta, vidrio, serif grande, un solo foco visual por pantalla) y el **ritmo del scroll**. Si tienes que recortar algo por tiempo, recorta secciones y extras, **no** la barra de vidrio, el titular serif, el fondo y la transición del héroe a la maqueta.
