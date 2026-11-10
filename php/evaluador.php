<?php
// Evaluador matemático seguro. NO usa eval(): lee el texto carácter por carácter,
// lo convierte en un árbol de operaciones y lo calcula. Sirve para verificar a la IA.
//
// Pasos:  texto -> [tokenizar] -> tokens -> [parsear] -> árbol -> [calcular] -> número
//
// Gramática (de menor a mayor prioridad):
//   expresion := termino  (('+' | '-') termino)*
//   termino   := unario   (('*' | '/') unario)*
//   unario    := ('-' | '+') unario | potencia
//   potencia  := primario ('^' unario)?         <- se asocia por la derecha: 2^3^2 = 2^(3^2)
//   primario  := número | x | sqrt '(' expresion ')' | '(' expresion ')'

class EvaluadorError extends Exception {}

class Evaluador
{
    const MAX_LARGO = 100;        // largo máximo de la expresión
    const MAX_PROFUNDIDAD = 40;   // paréntesis anidados permitidos
    const TOLERANCIA = 1e-9;      // para comparar decimales (nunca con ==)

    // ---------- 1. Validación con lista blanca ----------

    // Quita espacios. Devuelve la expresión limpia.
    public static function limpiar($expr)
    {
        return preg_replace('/\s+/', '', (string)$expr);
    }

    // ¿Solo contiene caracteres permitidos? (dígitos, x, + - * / ^ ( ) . = y la palabra sqrt)
    public static function validar($expr)
    {
        $expr = self::limpiar($expr);
        if ($expr === '' || strlen($expr) > self::MAX_LARGO) return false;
        if (substr_count($expr, '=') > 1) return false;
        $sinSqrt = str_replace('sqrt', '', $expr);
        return preg_match('/^[0-9x+\-*\/^().=]+$/', $sinSqrt) === 1;
    }

    // ¿Dos números son iguales dentro de la tolerancia?
    public static function iguales($a, $b)
    {
        return abs($a - $b) <= self::TOLERANCIA * max(1.0, abs($a), abs($b));
    }

    // ---------- 2. Interfaz pública ----------

    // Calcula una expresión (sin '='). Si tiene x, hay que dar su valor.
    public static function evaluar($expr, $x = null)
    {
        $expr = self::limpiar($expr);
        if (!self::validar($expr)) throw new EvaluadorError('La expresión contiene caracteres no permitidos.');
        if (strpos($expr, '=') !== false) throw new EvaluadorError('Esto es una ecuación; usa resolverEcuacion().');
        return self::calcular(self::parsear($expr), $x);
    }

    // Para una ecuación "izq = der" devuelve izq - der con x = $x. Si da ~0, ese x es solución.
    public static function diferenciaEn($expr, $x)
    {
        $expr = self::limpiar($expr);
        if (!self::validar($expr)) throw new EvaluadorError('La expresión contiene caracteres no permitidos.');
        $partes = explode('=', $expr);
        if (count($partes) !== 2) throw new EvaluadorError('Una ecuación necesita exactamente un "=".');
        return self::calcular(self::parsear($partes[0]), $x) - self::calcular(self::parsear($partes[1]), $x);
    }

    // Resuelve una ecuación de una variable de grado 0, 1 o 2, SIN usar a la IA.
    // Idea: f(x) = lado_izquierdo - lado_derecho = a·x² + b·x + c.
    // Evaluando f en unos pocos puntos se despejan a, b y c; luego se resuelve normal.
    public static function resolverEcuacion($expr)
    {
        $expr = self::limpiar($expr);
        if (!self::validar($expr)) throw new EvaluadorError('La expresión contiene caracteres no permitidos.');
        $partes = explode('=', $expr);
        if (count($partes) !== 2 || $partes[0] === '' || $partes[1] === '') {
            throw new EvaluadorError('Una ecuación necesita una expresión a cada lado del "=".');
        }
        $izq = self::parsear($partes[0]);   // si hay error de sintaxis, se lanza aquí
        $der = self::parsear($partes[1]);
        $f = function ($x) use ($izq, $der) {
            return self::calcular($izq, $x) - self::calcular($der, $x);
        };

        $noSoportada = ['tipo' => 'no_soportada', 'grado' => null, 'soluciones' => []];

        try {
            // Coeficientes del polinomio a partir de f(0), f(1) y f(-1)
            $c = $f(0.0);
            $p1 = $f(1.0);
            $m1 = $f(-1.0);
            $a = ($p1 + $m1) / 2 - $c;
            $b = ($p1 - $m1) / 2;

            // Comprobar que de verdad es un polinomio de grado <= 2 con otros puntos
            foreach ([2.0, -2.0, 3.0, 0.5, 7.0] as $t) {
                if (!self::iguales($f($t), $a * $t * $t + $b * $t + $c)) return $noSoportada;
            }
        } catch (EvaluadorError $e) {
            return $noSoportada;   // por ejemplo 1/x = 2, o sqrt(x) = 2 (fuera de alcance)
        }

        $escala = max(1.0, abs($b), abs($c));
        $coef = ['a' => $a, 'b' => $b, 'c' => $c];

        // Grado 2 (cuadrática)
        if (abs($a) > self::TOLERANCIA * max($escala, 1.0)) {
            $disc = $b * $b - 4 * $a * $c;
            $escalaDisc = max(1.0, $b * $b, abs(4 * $a * $c));
            if (abs($disc) <= self::TOLERANCIA * $escalaDisc) {
                return ['tipo' => 'unica', 'grado' => 2, 'coef' => $coef,
                        'soluciones' => [self::limpiarNumero(-$b / (2 * $a))]];
            }
            if ($disc < 0) {
                return ['tipo' => 'ninguna', 'grado' => 2, 'coef' => $coef, 'soluciones' => []];
            }
            $raiz = sqrt($disc);
            $s = [self::limpiarNumero((-$b - $raiz) / (2 * $a)), self::limpiarNumero((-$b + $raiz) / (2 * $a))];
            sort($s);
            return ['tipo' => 'dos', 'grado' => 2, 'coef' => $coef, 'soluciones' => $s];
        }

        // Grado 1 (lineal)
        if (abs($b) > self::TOLERANCIA * max($escala, 1.0)) {
            return ['tipo' => 'unica', 'grado' => 1, 'coef' => $coef,
                    'soluciones' => [self::limpiarNumero(-$c / $b)]];
        }

        // Grado 0: f(x) es constante
        if (abs($c) <= self::TOLERANCIA) {
            return ['tipo' => 'infinitas', 'grado' => 0, 'coef' => $coef, 'soluciones' => []]; // 0 = 0
        }
        return ['tipo' => 'ninguna', 'grado' => 0, 'coef' => $coef, 'soluciones' => []];         // 3 = 5
    }

    // Quita ruido de decimales (3.0000000000001 -> 3) y el "-0".
    private static function limpiarNumero($v)
    {
        return round($v, 10) + 0.0;
    }

    // ---------- 3. Tokenizador: texto -> lista de piezas ----------

    private static function tokenizar($texto)
    {
        $crudos = [];
        $n = strlen($texto);
        for ($i = 0; $i < $n;) {
            $ch = $texto[$i];
            if (substr($texto, $i, 4) === 'sqrt') {
                $crudos[] = ['t' => 'sqrt'];
                $i += 4;
            } elseif (preg_match('/\G(\d+\.?\d*|\.\d+)/', $texto, $m, 0, $i)) {
                $crudos[] = ['t' => 'num', 'v' => (float)$m[1]];
                $i += strlen($m[1]);
            } elseif ($ch === 'x') {
                $crudos[] = ['t' => 'x'];
                $i++;
            } elseif (strpos('+-*/^', $ch) !== false) {
                $crudos[] = ['t' => 'op', 'v' => $ch];
                $i++;
            } elseif ($ch === '(' || $ch === ')') {
                $crudos[] = ['t' => $ch];
                $i++;
            } else {
                throw new EvaluadorError("Carácter no permitido: $ch");
            }
        }

        // Multiplicación implícita: 2x -> 2*x, 3(x+1) -> 3*(x+1), (a)(b) -> (a)*(b)
        $tokens = [];
        foreach ($crudos as $tok) {
            if ($tokens) {
                $prev = end($tokens)['t'];
                $cur = $tok['t'];
                $terminaValor = in_array($prev, ['num', 'x', ')'], true);
                $empiezaValor = in_array($cur, ['num', 'x', '(', 'sqrt'], true);
                if ($terminaValor && $empiezaValor) {
                    if ($prev === 'num' && $cur === 'num') throw new EvaluadorError('Número mal escrito.');
                    $tokens[] = ['t' => 'op', 'v' => '*'];
                }
            }
            $tokens[] = $tok;
        }
        return $tokens;
    }

    // ---------- 4. Parser (descenso recursivo): tokens -> árbol ----------

    private static $tokens;
    private static $pos;
    private static $profundidad;

    private static function parsear($texto)
    {
        if ($texto === '') throw new EvaluadorError('Expresión vacía.');
        self::$tokens = self::tokenizar($texto);
        self::$pos = 0;
        self::$profundidad = 0;
        $arbol = self::pExpresion();
        if (self::$pos < count(self::$tokens)) throw new EvaluadorError('Expresión mal formada.');
        return $arbol;
    }

    private static function mirar()
    {
        return self::$tokens[self::$pos] ?? null;
    }

    private static function esOp($simbolos)
    {
        $t = self::mirar();
        return $t && $t['t'] === 'op' && strpos($simbolos, $t['v']) !== false;
    }

    private static function pExpresion()
    {
        $izq = self::pTermino();
        while (self::esOp('+-')) {
            $op = self::$tokens[self::$pos++]['v'];
            $izq = ['bin', $op, $izq, self::pTermino()];
        }
        return $izq;
    }

    private static function pTermino()
    {
        $izq = self::pUnario();
        while (self::esOp('*/')) {
            $op = self::$tokens[self::$pos++]['v'];
            $izq = ['bin', $op, $izq, self::pUnario()];
        }
        return $izq;
    }

    private static function pUnario()
    {
        if (self::esOp('-')) {
            self::$pos++;
            return ['neg', self::pUnario()];
        }
        if (self::esOp('+')) {
            self::$pos++;
            return self::pUnario();
        }
        return self::pPotencia();
    }

    private static function pPotencia()
    {
        $base = self::pPrimario();
        if (self::esOp('^')) {
            self::$pos++;
            return ['bin', '^', $base, self::pUnario()];   // el exponente vuelve a entrar: derecha-asociativo
        }
        return $base;
    }

    private static function pPrimario()
    {
        $t = self::mirar();
        if ($t === null) throw new EvaluadorError('La expresión está incompleta.');

        if ($t['t'] === 'num') { self::$pos++; return ['num', $t['v']]; }
        if ($t['t'] === 'x')   { self::$pos++; return ['x']; }

        if ($t['t'] === 'sqrt') {
            self::$pos++;
            if ((self::mirar()['t'] ?? '') !== '(') throw new EvaluadorError('Después de sqrt va un paréntesis.');
            return ['sqrt', self::pAgrupado()];
        }
        if ($t['t'] === '(') return self::pAgrupado();

        throw new EvaluadorError('Expresión mal formada.');
    }

    // Lee "( expresion )" controlando la profundidad.
    private static function pAgrupado()
    {
        self::$pos++;   // consume "("
        if (++self::$profundidad > self::MAX_PROFUNDIDAD) throw new EvaluadorError('Demasiados paréntesis anidados.');
        $dentro = self::pExpresion();
        if ((self::mirar()['t'] ?? '') !== ')') throw new EvaluadorError('Falta cerrar un paréntesis.');
        self::$pos++;   // consume ")"
        self::$profundidad--;
        return $dentro;
    }

    // ---------- 5. Cálculo: árbol -> número ----------

    private static function calcular($nodo, $x)
    {
        switch ($nodo[0]) {
            case 'num':
                return $nodo[1];
            case 'x':
                if ($x === null) throw new EvaluadorError('Falta el valor de x.');
                return (float)$x;
            case 'neg':
                return -self::calcular($nodo[1], $x);
            case 'sqrt':
                $v = self::calcular($nodo[1], $x);
                if ($v < 0) throw new EvaluadorError('No existe la raíz cuadrada de un número negativo.');
                return sqrt($v);
            case 'bin':
                $a = self::calcular($nodo[2], $x);
                $b = self::calcular($nodo[3], $x);
                switch ($nodo[1]) {
                    case '+': return self::finito($a + $b);
                    case '-': return self::finito($a - $b);
                    case '*': return self::finito($a * $b);
                    case '/':
                        if (abs($b) < 1e-12) throw new EvaluadorError('División entre cero.');
                        return self::finito($a / $b);
                    case '^':
                        if ($a == 0 && $b < 0) throw new EvaluadorError('División entre cero.');
                        return self::finito(pow($a, $b));
                }
        }
        throw new EvaluadorError('Expresión mal formada.');
    }

    // Rechaza INF y NAN (resultados enormes o no reales, como (-8)^0.5)
    private static function finito($v)
    {
        if (is_nan($v) || is_infinite($v)) {
            throw new EvaluadorError('El resultado es demasiado grande o no es un número real.');
        }
        return (float)$v;
    }
}
