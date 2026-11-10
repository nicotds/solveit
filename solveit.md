# SolveIt — Documento de construcción

> Pega este documento completo al inicio de un chat nuevo y escribe: **"Construye este proyecto siguiendo este documento, fase por fase. Empieza por la Fase 0."**

---

## 1. Resumen del proyecto

**SolveIt** es una web con un pizarrón digital donde el usuario **escribe a mano una operación o ecuación básica**. La IA lee lo escrito, **el usuario confirma o corrige lo que se leyó**, y entonces el sistema lo **resuelve paso a paso con una explicación**. El resultado se **verifica de forma independiente con PHP** (sin confiar solo en la IA) y se muestra con una marca de verificación.

Si lo escrito no es legible (garabatos, lienzo casi vacío, dibujos que no son matemática, símbolos imposibles de interpretar), se muestra un mensaje de error amable y no se resuelve nada.

Es un proyecto para una **materia de programación**. Es para presentación, no tiene que ser un producto perfecto, pero debe funcionar de forma confiable en vivo.

## 2. Restricciones del proyecto

- **Stack obligatorio:** HTML, CSS, PHP y MySQL. **JavaScript sí está permitido.**
- **Entorno:** XAMPP en Windows (Apache + PHP + MySQL + phpMyAdmin). Ruta del proyecto: `C:\xampp\htdocs\solveit\`.
- **Sin frameworks ni Composer.** PHP puro (cURL y `mysqli` o PDO), JavaScript puro, CSS propio.
- **IA:** API de **Gemini** (Google). La clave solo vive en el servidor PHP, nunca en JS ni en HTML.
- **Mostrar fórmulas:** KaTeX **guardado localmente dentro del proyecto** (no desde un CDN), para no depender de internet en ese punto.
- **Idioma:** español.
- **Presupuesto:** lo más cercano a cero posible.
- **Código claro y comentado en español**, fácil de explicar en una exposición. Evita sobreingeniería.

## 3. Alcance (qué entra y qué no)

**Entra:**
- Operaciones aritméticas: `+ − × ÷`, paréntesis, potencias, raíz cuadrada, fracciones y decimales.
- Ecuaciones **lineales de una variable** (por ejemplo `2x + 3 = 11`).
- (Fase extra) Ecuaciones **cuadráticas simples** de una variable.

**NO entra** (decirlo claramente al usuario si lo intenta):
- Circuitos eléctricos.
- Sistemas de ecuaciones, varias variables, cálculo (derivadas, integrales), geometría, problemas de texto.

## 4. Flujo del sistema

1. El usuario inicia sesión y entra al pizarrón.
2. Escribe la operación o ecuación con el mouse, el dedo o un lápiz táctil.
3. Presiona **"Leer"**. JS exporta el canvas a PNG base64 y lo envía a `php/leer.php`.
4. PHP llama a Gemini con la imagen y un prompt de **transcripción** (no resuelve nada).
5. Si no es legible → mensaje de error. Si es legible → se muestra la expresión leída en un **campo editable** (con la fórmula renderizada con KaTeX al lado) y los símbolos dudosos, si los hay.
6. El usuario **confirma o corrige** (también puede escribirla a mano en ese campo con el teclado, como alternativa).
7. Presiona **"Resolver"**. JS envía **solo el texto** a `php/resolver.php`.
8. PHP pide a Gemini la solución paso a paso (JSON), y **verifica el resultado con su propio evaluador** (ver sección 7).
9. PHP guarda el problema en MySQL y devuelve el JSON. El front muestra los pasos con KaTeX y el estado de verificación.
10. El usuario puede ver su **historial** y abrir o borrar problemas anteriores.

**Por qué dos llamadas separadas:** reconocer y resolver son problemas distintos. Separarlos permite que el usuario corrija una mala lectura antes de resolver, y la segunda llamada es solo texto (más rápida y barata).

## 5. Requisitos funcionales

### 5.1 Pizarrón (JavaScript + canvas)
- Escritura a mano alzada con mouse y táctil usando Pointer Events. Con `touch-action: none` en el canvas.
- Color negro por defecto, **grosor predeterminado fino** (unos 4 px), borrador, **deshacer** (historial de hasta 30 estados) y limpiar.
- Canvas de aproximadamente 800×350 con fondo **blanco sólido** (el PNG exportado no puede ser transparente).
- Si se usan líneas guía, deben ir en el **CSS del contenedor**, **no dentro del canvas**, para que no aparezcan en la imagen enviada a la IA.
- Coordenadas correctas aunque el canvas se escale con CSS.
- Si el canvas está vacío, no enviar y mostrar: "Primero escribe algo en el pizarrón".
- (Opcional) Recortar la imagen al área escrita con un pequeño margen antes de enviarla; mejora la lectura y reduce tokens.

### 5.2 Reconocimiento (`php/leer.php`)
- Recibe la imagen, valida que sea PNG base64 y de tamaño razonable (< 5 MB).
- Llama a Gemini con el prompt de la sección 8.1.
- Devuelve `{ ok, expresion, latex, tipo, dudas }` o `{ ok:false, mensaje }`.

### 5.3 Confirmación (front)
- Campo de texto editable con la expresión leída + vista previa en KaTeX que se actualiza al escribir.
- Mostrar las **dudas** de la IA ("el segundo símbolo podría ser + o ×") para que el usuario sepa qué revisar.
- No se puede resolver si el campo está vacío o contiene caracteres no permitidos.

### 5.4 Resolución y verificación (`php/resolver.php` + `php/evaluador.php`)
- Valida que la expresión use **solo caracteres permitidos** (dígitos, `x`, `+ - * / ^ ( ) . = ,` y `sqrt`). Cualquier otra cosa se rechaza.
- Llama a Gemini con el prompt de la sección 8.2 (temperatura baja).
- **Verifica con PHP** (sección 7) y marca el resultado: `verificado`, `no_verificado` o `inconsistente`.
- Si es `inconsistente` (la IA y la verificación no coinciden), **no presentar la respuesta como correcta**: mostrar un aviso claro y, si es posible, mostrar el valor calculado por PHP.

### 5.5 Presentación de la solución
- Pasos numerados, cada uno con título, fórmula (KaTeX) y explicación breve.
- Selector de nivel de explicación: **básico** o **avanzado**.
- Resultado final destacado y sello de verificación ("✓ Verificado: 2(4) + 3 = 11").

### 5.6 Usuarios e historial (refuerza la parte de PHP)
- Registro e inicio de sesión con sesiones PHP. Contraseñas con `password_hash()` / `password_verify()`.
- Páginas protegidas: sin sesión, redirigir al login.
- Guardar cada problema resuelto; **listar, abrir y borrar** los propios ("Historial").
- (Opcional) Rol `admin` con un panel de usuarios y estadísticas simples (cuántos problemas, porcentaje verificado).

## 6. Estructura de archivos

> **Estructura real construida** (incluye los archivos añadidos durante el desarrollo; ver sección 16).

```
solveit/
├── index.php                  # Pizarrón + confirmación + solución (requiere sesión). ?demo=1 = modo sin IA
├── login.php
├── registro.php
├── logout.php
├── historial.php              # Lista de problemas del usuario + estadísticas
├── problema.php               # Ver un problema guardado (?id=)
├── admin.php                  # Panel de administrador (solo rol admin)
├── prueba.html / prueba.php   # Prueba de la Fase 0 (prueba.php exige sesión)
├── css/
│   └── estilos.css
├── js/
│   ├── pizarron.js            # Canvas, deshacer, exportar PNG recortado
│   ├── solucion.js            # Compartido: expresión → LaTeX, KaTeX, mostrar solución verificada
│   ├── solver.js              # Flujo leer → confirmar → resolver (+ modo demo)
│   └── practica.js            # "Dame un ejercicio parecido" y corrección
├── lib/
│   └── katex/                 # katex.min.js, katex.min.css y carpeta fonts (local, v0.16.11)
├── php/
│   ├── config.php             # API key, modelos, datos de BD (NO subir a GitHub)
│   ├── db.php                 # Conexión a MySQL (mysqli)
│   ├── auth.php               # Sesiones, requerir_login, e(), token CSRF
│   ├── barra.php              # Barra de navegación común
│   ├── gemini.php             # Llamadas a Gemini: reintentos, modelos de respaldo, límite de uso
│   ├── imagen.php             # Valida y guarda/borra los PNG del pizarrón
│   ├── leer.php               # Imagen → expresión (Gemini)
│   ├── resolver.php           # Expresión → pasos (Gemini) + verificación + guardado
│   ├── verificar.php          # Compara a la IA contra el cálculo propio de PHP
│   ├── evaluador.php          # Evaluador matemático seguro (sin eval)
│   ├── problemas.php          # Guardar / obtener / borrar problemas (consultas preparadas)
│   ├── similar.php            # Genera un ejercicio parecido (PHP calcula la respuesta)
│   ├── comprobar.php          # Corrige la respuesta del usuario en el modo práctica
│   ├── borrar.php             # Borra un problema propio (POST + CSRF)
│   ├── demo_datos.php         # 6 ejemplos resueltos para el modo demo (Plan B)
│   └── parcial_solucion.php   # Fragmento HTML: panel de solución y de práctica
├── tests/
│   ├── probar_evaluador.php   # 68 casos del evaluador (navegador o terminal)
│   └── probar_verificacion.php# 23 casos de verificación con respuestas de IA inventadas
├── sql/
│   ├── solveit.sql            # Script para importar en phpMyAdmin
│   └── cargar_demo.php        # Crea la cuenta demo con los ejemplos (solo desde localhost)
├── uploads/
│   ├── .htaccess              # Impide ejecutar PHP y listar la carpeta
│   └── pizarrones/            # PNG de lo escrito, con nombre aleatorio
└── .gitignore                 # Incluye php/config.php y uploads/
```

## 7. Verificación independiente con PHP (la parte clave del proyecto)

> **Nunca usar `eval()` ni nada que ejecute texto del usuario.** Se debe escribir un evaluador propio.

### 7.1 Evaluador (`evaluador.php`)
- Validar con una lista blanca de caracteres (regex) antes de procesar.
- **Tokenizar** y evaluar con un analizador (shunting-yard o descenso recursivo).
- Operadores: `+ - * / ^`, paréntesis, menos unario, decimales, `sqrt(...)`.
- Soportar **multiplicación implícita**: `2x` → `2*x`, `3(x+1)` → `3*(x+1)`, `(a)(b)` → `(a)*(b)`.
- Una sola variable permitida: `x`. Manejar división entre cero y raíz de negativos sin romper la página.
- Comparar números con tolerancia (por ejemplo `1e-9`), no con `==`.

### 7.2 Qué se verifica
- **Operación (sin `=`):** PHP evalúa la expresión y compara con el resultado numérico que dio la IA.
- **Ecuación (con `=`):**
  - Sea `f(x) = lado_izquierdo − lado_derecho`.
  - **Sustitución:** para cada solución que dio la IA, comprobar que `f(solución) ≈ 0`.
  - **Solución independiente (recomendado):**
    - Lineal: calcular `f(0)` y `f(1)`; entonces `a = f(1) − f(0)`, `b = f(0)` y `x = −b / a`.
    - Cuadrática: calcular `f(0)`, `f(1)` y `f(−1)` para obtener `a`, `b`, `c`; luego el discriminante y las raíces. Confirmar con `f(2)` que realmente es de grado ≤ 2.
  - Comparar las soluciones de PHP con las de la IA.
- **Estados:**
  - `verificado`: coincide.
  - `no_verificado`: el tipo de problema no se puede comprobar (por ejemplo, algo fuera de alcance que pasó el filtro).
  - `inconsistente`: la IA y PHP no coinciden → avisar y no presentar como correcto.

### 7.3 Pruebas del evaluador (`tests/probar_evaluador.php`)
Una página que corre una tabla de casos y muestra ✓/✗, incluyendo como mínimo:

| Entrada | Resultado esperado |
|---|---|
| `2+3*4` | 14 |
| `(2+3)*4` | 20 |
| `2^3^2` | 512 (la potencia se asocia por la derecha) |
| `-3+5` | 2 |
| `10/4` | 2.5 |
| `sqrt(16)` | 4 |
| `3(2+1)` | 9 |
| `2x+3=11` | x = 4 |
| `x^2-5x+6=0` | x = 2 y x = 3 |
| `1/0` | error controlado, sin romper la página |
| `abc` / `system("ls")` | rechazado por la lista blanca |

## 8. Integración con Gemini

### 8.1 Configuración (`php/config.php`)
```php
<?php
define('GEMINI_API_KEY', 'PON_AQUI_TU_API_KEY');
define('GEMINI_MODELO', 'gemini-2.5-flash'); // VERIFICAR en Google AI Studio (ver 8.4)
define('DB_HOST', 'localhost');
define('DB_NAME', 'solveit');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 8.2 Petición REST (con cURL)
- URL: `https://generativelanguage.googleapis.com/v1beta/models/{MODELO}:generateContent`
- Encabezados: `Content-Type: application/json` y `x-goog-api-key: {API_KEY}`
- Llamada 1 (imagen), cuerpo:
```json
{
  "contents": [{
    "parts": [
      { "inline_data": { "mime_type": "image/png", "data": "<BASE64 SIN el prefijo data:image/png;base64,>" } },
      { "text": "<PROMPT_LECTURA>" }
    ]
  }],
  "generationConfig": { "responseMimeType": "application/json", "temperature": 0.1 }
}
```
- Llamada 2 (solo texto): igual pero con una sola parte `text` y `temperature` de 0.2.
- La respuesta está en `candidates[0].content.parts[0].text`; decodificar con `json_decode`. Si falta, revisar `promptFeedback` o `finishReason` y devolver un mensaje amable.
- `CURLOPT_TIMEOUT` de unos 60 segundos.

### 8.3 Prompts

**Prompt de lectura (llamada 1):**
```
Eres un lector de matemática escrita a mano. Mira la imagen de un pizarrón digital.

Tu único trabajo es TRANSCRIBIR lo escrito. NO lo resuelvas ni lo corrijas.

1. Decide si hay una expresión matemática legible: una operación aritmética o una ecuación
   de una variable. Si el lienzo está vacío, son garabatos, hay dibujos que no son matemática,
   o es tan ambiguo que no se puede leer, responde con "legible": false.
2. Si es legible, transcríbela con esta sintaxis exacta:
   - números y decimales con punto
   - la variable es x
   - operadores: + - * / ^
   - multiplicación SIEMPRE con * (escribe 2*x, no 2x)
   - paréntesis ( )
   - raíz cuadrada: sqrt(...)
   - fracciones con / y paréntesis
3. Si algún símbolo es dudoso (por ejemplo un "+" que podría ser "×"), elige la lectura más
   probable y descríbelo en "dudas".

Responde SOLO con JSON válido, sin texto adicional:
{
  "legible": true,
  "expresion": "2*x+3=11",
  "latex": "2x+3=11",
  "tipo": "ecuacion",          // "ecuacion" o "operacion"
  "dudas": ["el tercer símbolo podría ser + o ×"]   // lista vacía si no hay dudas
}
Si no es legible: {"legible": false, "motivo": "explicación breve y amable en español"}
```

**Prompt de resolución (llamada 2):** (sustituir `{EXPRESION}` y `{NIVEL}`)
```
Eres un profesor de matemática paciente. Resuelve esto: {EXPRESION}
Nivel de explicación: {NIVEL}  (basico = para quien empieza; avanzado = conciso y técnico)

Reglas:
- Solo operaciones aritméticas y ecuaciones de una variable (lineales o cuadráticas simples).
- Si está fuera de ese alcance, responde {"soportado": false, "motivo": "..."}.
- Da los números finales también como valores numéricos en "soluciones" / "valor".

Responde SOLO con JSON válido, sin texto adicional:
{
  "soportado": true,
  "tipo": "ecuacion",                  // "ecuacion" u "operacion"
  "resultado_texto": "x = 4",
  "soluciones": [4],                   // ecuación: lista de números; operación: lista vacía
  "valor": null,                       // operación: el número resultante; ecuación: null
  "pasos": [
    {"titulo": "Restar 3 en ambos lados", "latex": "2x = 8", "explicacion": "..."},
    {"titulo": "Dividir entre 2", "latex": "x = 4", "explicacion": "..."}
  ],
  "resumen": "una frase final"
}
```

### 8.4 Puntos que hay que VERIFICAR antes de construir (no están confirmados)
1. **Nombre exacto del modelo.** Una fuente de terceros mencionó modelos Gemini con uso gratuito, pero **no está confirmado** el nombre vigente ni que acepten imágenes. Entra a **Google AI Studio**, revisa la lista y elige uno con nivel gratuito que acepte imagen de entrada. Ponlo en `GEMINI_MODELO`. `gemini-2.5-flash` es solo un marcador.
2. **Límites del nivel gratuito** (peticiones por minuto y por día), visibles en AI Studio. Manejar el error 429 con un mensaje claro ("Se alcanzó el límite de uso, espera un minuto").
3. **Privacidad:** en el nivel gratuito Google puede usar lo que envías para mejorar sus productos. No escribas nada personal durante las pruebas.
4. **Obtener la clave:** entrar a Google AI Studio con una cuenta de Google y crear una API key; pegarla en `config.php`.
5. **Prueba mínima:** un script PHP aparte que envíe una imagen cualquiera y muestre la respuesta cruda. Hasta que eso funcione, no construir el resto.

## 9. Base de datos (MySQL)

```sql
CREATE DATABASE IF NOT EXISTS solveit CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE solveit;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE problemas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  expresion VARCHAR(255) NOT NULL,          -- la expresión confirmada por el usuario
  latex VARCHAR(255) NULL,
  tipo ENUM('operacion','ecuacion') NOT NULL,
  resultado_texto VARCHAR(255) NOT NULL,
  pasos_json LONGTEXT NOT NULL,
  verificacion ENUM('verificado','no_verificado','inconsistente') NOT NULL,
  nivel ENUM('basico','avanzado') NOT NULL DEFAULT 'basico',
  imagen_ruta VARCHAR(255) NULL,            -- PNG de lo escrito (opcional)
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
```

Todas las consultas con **sentencias preparadas**. Al borrar un problema, validar que pertenece al usuario en sesión y borrar también el PNG del disco.

## 10. Seguridad mínima

- La API key y los datos de BD solo en `php/config.php` (en `.gitignore`).
- `password_hash()` / `password_verify()`; `session_regenerate_id(true)` al iniciar sesión.
- Consultas preparadas siempre; `htmlspecialchars()` en todo lo que se imprima en HTML.
- **Nunca `eval()`** ni ejecutar texto del usuario. Lista blanca de caracteres en `leer.php` y `resolver.php`.
- Validar que la imagen sea PNG base64 de tamaño razonable; nombres de archivo aleatorios (`bin2hex(random_bytes(8))`).
- Cada problema solo se ve o borra si pertenece al usuario en sesión.
- Limitar peticiones por usuario (por ejemplo 1 cada 5 segundos) para cuidar la cuota de la API.

## 11. Fases de construcción

> Instrucción para quien construya: **trabaja una fase a la vez**, entrega los archivos completos de esa fase, explica cómo probarla y espera confirmación antes de pasar a la siguiente.

| Fase | Entregable | Cómo se prueba |
|---|---|---|
| **0. Prueba de escritura (puerta de decisión)** | Un `prueba.html` mínimo con canvas y un `prueba.php` que envía el dibujo a Gemini con el prompt de lectura y muestra la respuesta cruda | Escribir **10 ecuaciones con el mouse** (tabla abajo). Si Gemini lee bien al menos **8 de 10**, seguir. Si no, parar y replantear (ver nota) |
| **1. Preparación** | Estructura de carpetas, `config.php`, `db.php`, `solveit.sql` | `http://localhost/solveit/` abre y la BD se importa |
| **2. Usuarios** | `registro.php`, `login.php`, `logout.php`, `auth.php` | Registrar, iniciar y cerrar sesión; las páginas protegidas redirigen |
| **3. Pizarrón** | `index.php`, `estilos.css`, `pizarron.js` (solo escritura) | Escribir con mouse y táctil; deshacer, borrar y limpiar funcionan |
| **4. Lectura y confirmación** | `leer.php` + campo editable con KaTeX | Ecuación clara → se lee; garabato → error; se puede corregir a mano |
| **5. Evaluador seguro** | `evaluador.php` + `tests/probar_evaluador.php` | Todos los casos de la sección 7.3 pasan |
| **6. Resolver y verificar** | `resolver.php`, render de pasos con KaTeX, sello de verificación | Ecuación lineal → pasos + "✓ Verificado"; resultado forzado mal → "inconsistente" |
| **7. Historial** | Guardado en MySQL, `historial.php`, `problema.php`, `borrar.php` | Resolver → aparece en el historial → abrir → borrar |
| **8. Extras y pulido** | Cuadráticas, nivel básico/avanzado, "Dame un ejercicio parecido", diseño final | Ver criterios de aceptación |

**Tabla sugerida para la Fase 0** (escríbelas con el mouse, a tamaño grande y con tu letra normal):

`2+3` · `15-7` · `4×6` · `20÷5` · `2x+3=11` · `3x-5=10` · `x/2=7` · `(2+3)×4` · `2^3` · `x²-5x+6=0`

Anota cuáles lee bien y cuáles no. **Nota:** si la tasa de acierto es baja, las opciones son (a) engrosar o agrandar lo escrito y recortar el área, (b) añadir entrada por teclado como método principal con el pizarrón como complemento, o (c) cambiar de proyecto. Esa decisión debe tomarse aquí, antes de invertir más tiempo.

## 12. Plan B para la presentación

- Dejar guardados 4 o 5 problemas de ejemplo ya resueltos y verificados en el historial de una cuenta de demostración.
- Un modo `?demo=1` que muestre una solución guardada sin llamar a Gemini, por si falla internet o se agota la cuota.
- Probar en la misma laptop y red donde se expondrá. Llevar el proyecto en USB.
- Mostrar a propósito: (1) un caso que funciona, (2) una lectura corregida a mano en el paso de confirmación, (3) un garabato que da error, (4) la verificación con PHP.

## 13. Criterios de aceptación

- [x] Una operación o ecuación lineal escrita con claridad se lee, se confirma, se resuelve y se verifica.
- [x] Un garabato, un lienzo vacío o un dibujo que no es matemática muestran un error y **no** se resuelven ni se guardan.
- [x] El usuario puede **corregir la lectura** antes de resolver.
- [x] El resultado muestra los pasos con fórmulas bien renderizadas y un estado de verificación.
- [x] Si la IA y PHP no coinciden, la respuesta **no** se presenta como correcta.
- [x] El evaluador pasa todos los casos de prueba (68 de 68) y rechaza entradas peligrosas (`system("ls")`, etc.).
- [x] Registro, login y logout funcionan; las páginas protegidas exigen sesión.
- [x] El historial guarda, lista, abre y borra, y cada usuario solo ve lo suyo (probado con dos usuarios).
- [x] La clave de Gemini no aparece en ningún archivo que llegue al navegador.
- [x] Los errores de red, de cuota (429) y de clave inválida muestran mensajes comprensibles.
- [ ] Funciona en Chrome o Edge, con mouse y con pantalla táctil. *(pendiente de probar a mano; ver guía en la sección 17)*

## 14. Problemas comunes (XAMPP / Windows)

- **Error de certificado SSL en cURL** (frecuente): descargar `cacert.pem` de curl.se, guardarlo y apuntar `curl.cainfo` a él en `php.ini`; reiniciar Apache.
- **cURL no habilitado:** en `php.ini` debe estar activa `extension=curl`.
- **Imágenes rechazadas por tamaño:** revisar `post_max_size` en `php.ini`.
- **KaTeX sin estilos o con símbolos rotos:** la carpeta `fonts` debe estar junto a `katex.min.css`.
- **Errores silenciosos:** activar `display_errors` en desarrollo y revisar la consola (F12) y `xampp/apache/logs/error.log`.

## 15. Ideas opcionales si sobra tiempo

- Botón **"Dame un ejercicio parecido"** (`similar.php`) para practicar, con corrección automática de la respuesta que escriba el usuario.
- Estadísticas personales: cuántos problemas, porcentaje verificado, tipos más frecuentes.
- Exportar la solución como imagen o PDF.
- Panel de administrador con usuarios y métricas.

---

## 16. Estado final y diferencias respecto al plan

Todas las fases (0 a 8) y el Plan B están construidos. Resultado de la Fase 0: **10 de 10** ecuaciones leídas bien. Lo que cambió o se añadió respecto al plan original:

| Tema | Plan original | Lo que se hizo y por qué |
|---|---|---|
| **Puerto de MySQL** | `localhost`, puerto por defecto | El equipo tiene un servicio MySQL 9.3 de Windows en el 3306; el MySQL de XAMPP usa el **3307**. `config.php` usa `127.0.0.1` y `DB_PORT = 3307`. En otro equipo, revisar el puerto. |
| **Modelo de Gemini** | `gemini-2.5-flash` (marcador) | Principal: `gemini-3.5-flash`. El modelo `gemini-2.5-flash` ya devuelve **404** (retirado para cuentas nuevas). |
| **Cuota gratuita** | Solo "manejar el 429" | El nivel gratuito permite ~**20 peticiones por día por modelo**; se agotó durante las pruebas. Por eso `gemini.php` encadena modelos de respaldo (`gemini-3-flash-preview`, `gemini-3.5-flash-lite`, `gemini-3.1-flash-lite`), cada uno con su propia cuota. Ante 429/404 pasa al siguiente de inmediato; ante 503 reintenta una vez. |
| **Error 503 "high demand"** | No previsto | Manejado con el reintento y los respaldos anteriores, más un mensaje amable si todo falla. |
| **Imagen en el historial** | `resolver.php` recibe solo texto | El navegador reenvía el PNG a `resolver.php` solo para **guardarlo**; Gemini sigue recibiendo solo texto en esa llamada. Si se escribió con el teclado, no hay imagen. |
| **Verificación** | PHP muestra un sello | El **resultado destacado sale del cálculo de PHP**, no del texto de la IA. Si no coinciden, los pasos de la IA quedan ocultos y marcados "no confiables". Tolerancia IA↔PHP: 1e-6 (relativa); dentro del evaluador: 1e-9. |
| **Seguridad extra** | Lista de seguridad mínima (sección 10) | Se añadió: token **CSRF** al borrar, `.htaccess` en `uploads/`, validación de PNG real con `getimagesizefromstring`, y `prueba.php` ahora exige sesión (antes era un acceso abierto a Gemini). |
| **Modo demo (Plan B)** | `?demo=1` | `index.php?demo=1` ofrece 6 ejemplos guardados que se resuelven **sin llamar a Gemini**; PHP los verifica igual. `sql/cargar_demo.php` crea la cuenta `demo@solveit.local` / `demo1234` con esos problemas en su historial. |
| **Ejercicio parecido** | Opcional | Hecho. Gemini propone el ejercicio, pero **PHP calcula la respuesta correcta** y la guarda en la sesión; el navegador no la recibe hasta pedir "Ver la respuesta". Acepta fracciones (`1/3`) y varias soluciones (`2, 3`). |
| **Extras opcionales** | Estadísticas y panel admin | Hechos: estadísticas en el historial y `admin.php` (solo rol `admin`). |

**Pendiente / limitaciones conocidas**
- Probar en Chrome/Edge con pantalla táctil real (único criterio sin marcar).
- Los modelos "lite" de respaldo son menos potentes; no se midió su acierto leyendo letra a mano.
- Los nombres de modelos cambian con frecuencia: si alguno da 404, quitarlo de `GEMINI_MODELOS_RESPALDO`.
- Antes de publicar fuera de localhost: borrar `tests/` y `sql/cargar_demo.php`, y cambiar la contraseña de la cuenta demo.

## 17. Guía de pruebas (para revisar antes de exponer)

**Preparación:** XAMPP con Apache y MySQL encendidos, base `solveit` importada, y `http://localhost/solveit/`. Usa Ctrl+F5 para recargar sin caché.

### A. Pruebas automáticas (no gastan cuota de IA)
1. `http://localhost/solveit/tests/probar_evaluador.php` → debe decir **68 de 68** ✓.
2. `http://localhost/solveit/tests/probar_verificacion.php` → debe decir **23 de 23** ✓. Aquí se ve el caso "IA equivocada → inconsistente".

### B. Cuentas y sesión
3. Abre `http://localhost/solveit/` sin sesión → te manda a `login.php`.
4. Regístrate. Prueba errores: correo repetido, contraseñas distintas, contraseña de menos de 6 caracteres.
5. Cierra sesión; escribe `index.php` en la barra → vuelve al login.
6. En phpMyAdmin, tabla `usuarios`: la contraseña debe verse como un hash (`$2y$…`).

### C. Pizarrón (usa mouse y, si puedes, dedo o lápiz)
7. Escribe con el trazo; prueba **Lápiz, Borrador, Deshacer** (varias veces, también Ctrl+Z) y **Limpiar** (y deshacer después de limpiar).
8. En pantalla táctil la página **no** debe moverse mientras dibujas.
9. Achica la ventana: el trazo debe seguir exactamente al puntero.
10. Con el lienzo vacío pulsa **Leer** → "Primero escribe algo en el pizarrón".

### D. Flujo completo con IA (gasta 2 peticiones por intento)
11. Escribe `2x+3=11` → **Leer** → aparece `2*x+3=11` con la fórmula dibujada → **Resolver**. Debe salir el sello verde "✓ Verificado" con `2(4)+3=11`, el resultado `x = 4` y los pasos.
12. Cambia a **Avanzada** y resuelve otra vez; la explicación debe ser más técnica.
13. **Corregir a mano:** tras leer, cambia el 11 por 12 en el campo; la vista previa cambia y el resultado también.
14. **Garabato:** dibuja una casa o líneas sueltas → **Leer** → mensaje de error, sin panel de resolución.
15. Prueba las 10 de la Fase 0: `2+3` · `15-7` · `4×6` · `20÷5` · `2x+3=11` · `3x-5=10` · `x/2=7` · `(2+3)×4` · `2^3` · `x²-5x+6=0`.
16. Pulsa **Leer** dos veces muy rápido → la segunda pide esperar unos segundos (límite de 1 cada 4 s).
17. **Teclado:** botón "Escribirla con el teclado"; prueba `abc` o `system("ls")` → error y **Resolver** deshabilitado. Prueba `1/0` → "No se puede resolver: División entre cero" (sin gastar IA).

### E. Historial
18. Tras resolver aparece "✓ Guardado en tu historial". Entra a **Historial**: debe verse el problema con su insignia y las estadísticas.
19. **Abrir** → muestra la solución y, si lo escribiste a mano, "Ver lo que escribiste en el pizarrón".
20. **Borrar** → pide confirmación y desaparece (y su imagen sale de `uploads/pizarrones/`).
21. **Aislamiento:** crea un segundo usuario; su historial debe estar vacío y `problema.php?id=` con un id del primero debe dar "No encontramos ese problema".

### F. Ejercicio parecido
22. En una solución pulsa **🎲 Dame un ejercicio parecido** → aparece un ejercicio nuevo.
23. Responde mal → "Todavía no"; responde bien → "¡Correcto! 🎉". En ecuaciones con dos soluciones prueba `2, 3`, `x1=2; x2=3` y `3 y 2`.
24. **Ver la respuesta** muestra la correcta; **Otro ejercicio** pide uno nuevo.

### G. Plan B (modo demo)
25. Visita `http://localhost/solveit/index.php?demo=1` → elige un ejemplo → **Resolver** → solución verificada **sin internet** (puedes probarlo apagando el wifi).
26. Cuenta demo: ejecuta una vez `http://localhost/solveit/sql/cargar_demo.php` e inicia sesión con `demo@solveit.local` / `demo1234`; su historial trae 6 problemas listos para mostrar.

### H. Errores y administración
27. Para ver el error de cuota o de clave: pon una API key falsa en `config.php` y pulsa **Leer** → mensaje "Gemini rechazó la petición… Revisa la API key". Restaura la clave después.
28. `admin.php` como usuario normal → "Solo para administradores". Para probarlo: en phpMyAdmin cambia tu `rol` a `admin`.

### I. Seguridad rápida
29. Abre `http://localhost/solveit/uploads/pizarrones/` → debe dar 403 (sin listar archivos).
30. Mira el código fuente de la página (Ctrl+U): **no** debe aparecer la API key en ningún lado.

### Guion sugerido para la presentación (según la sección 12)
1) Un caso que funciona (`2x+3=11`, mostrando el sello). 2) Una lectura corregida a mano en la confirmación. 3) Un garabato que da error. 4) La verificación de PHP: abre `tests/probar_verificacion.php` y muestra una IA equivocada marcada como inconsistente. Si falla internet o se acaba la cuota: modo demo.

---

**Nota final para quien construya:** la credibilidad de este proyecto está en dos cosas: el **paso de confirmación** y la **verificación independiente con PHP**. Sin ellas es solo una caja de texto con IA. No avances de la Fase 0 sin el resultado de la prueba de escritura, y si algo de la sección 8.4 no se puede verificar, dilo y propón una alternativa en lugar de asumirlo.
