<?php
// Ejemplos ya resueltos para el Plan B de la presentación (modo demo, sin llamar a Gemini).
// Las soluciones están escritas a mano; igual pasan por la verificación de PHP al mostrarse.
require_once __DIR__ . '/evaluador.php';

function demo_ejemplos()
{
    return [
        [
            'expresion' => '2*x+3=11',
            'ia' => [
                'resultado_texto' => 'x = 4', 'soluciones' => [4], 'valor' => null,
                'pasos' => [
                    ['titulo' => 'Restar 3 en ambos lados', 'latex' => '2x + 3 - 3 = 11 - 3 \implies 2x = 8',
                     'explicacion' => 'Para dejar sola la x, primero quitamos el 3 que está sumando: lo restamos en los dos lados para que la igualdad siga siendo cierta.'],
                    ['titulo' => 'Dividir entre 2', 'latex' => 'x = \frac{8}{2} = 4',
                     'explicacion' => 'Ahora la x está multiplicada por 2. Dividimos los dos lados entre 2 y queda x sola.'],
                ],
                'resumen' => 'El valor que cumple la ecuación es x = 4.',
            ],
        ],
        [
            'expresion' => '3*x-5=10',
            'ia' => [
                'resultado_texto' => 'x = 5', 'soluciones' => [5], 'valor' => null,
                'pasos' => [
                    ['titulo' => 'Sumar 5 en ambos lados', 'latex' => '3x - 5 + 5 = 10 + 5 \implies 3x = 15',
                     'explicacion' => 'El 5 está restando, así que lo cancelamos sumando 5 en los dos lados.'],
                    ['titulo' => 'Dividir entre 3', 'latex' => 'x = \frac{15}{3} = 5',
                     'explicacion' => 'Dividimos entre 3 para dejar la x sola.'],
                ],
                'resumen' => 'El valor que cumple la ecuación es x = 5.',
            ],
        ],
        [
            'expresion' => 'x/2=7',
            'ia' => [
                'resultado_texto' => 'x = 14', 'soluciones' => [14], 'valor' => null,
                'pasos' => [
                    ['titulo' => 'Multiplicar por 2 en ambos lados', 'latex' => '\frac{x}{2} \cdot 2 = 7 \cdot 2 \implies x = 14',
                     'explicacion' => 'La x está dividida entre 2. Lo contrario de dividir es multiplicar, así que multiplicamos los dos lados por 2.'],
                ],
                'resumen' => 'El valor que cumple la ecuación es x = 14.',
            ],
        ],
        [
            'expresion' => '(2+3)*4',
            'ia' => [
                'resultado_texto' => '20', 'soluciones' => [], 'valor' => 20,
                'pasos' => [
                    ['titulo' => 'Resolver el paréntesis', 'latex' => '(2 + 3) \cdot 4 = 5 \cdot 4',
                     'explicacion' => 'Primero se calcula lo que está dentro del paréntesis: 2 + 3 = 5.'],
                    ['titulo' => 'Multiplicar', 'latex' => '5 \cdot 4 = 20',
                     'explicacion' => 'Después multiplicamos el resultado por 4.'],
                ],
                'resumen' => 'El resultado de la operación es 20.',
            ],
        ],
        [
            'expresion' => 'x^2-5*x+6=0',
            'ia' => [
                'resultado_texto' => 'x = 2 y x = 3', 'soluciones' => [2, 3], 'valor' => null,
                'pasos' => [
                    ['titulo' => 'Factorizar', 'latex' => 'x^2 - 5x + 6 = (x - 2)(x - 3) = 0',
                     'explicacion' => 'Buscamos dos números que sumen -5 y multipliquen 6: son -2 y -3.'],
                    ['titulo' => 'Igualar cada factor a cero', 'latex' => 'x - 2 = 0 \quad \text{o} \quad x - 3 = 0',
                     'explicacion' => 'Si un producto es cero, al menos uno de sus factores es cero.'],
                    ['titulo' => 'Despejar', 'latex' => 'x = 2 \quad \text{o} \quad x = 3',
                     'explicacion' => 'Cada ecuación sencilla da una solución.'],
                ],
                'resumen' => 'La ecuación tiene dos soluciones: x = 2 y x = 3.',
            ],
        ],
        [
            'expresion' => 'sqrt(16)+2^3',
            'ia' => [
                'resultado_texto' => '12', 'soluciones' => [], 'valor' => 12,
                'pasos' => [
                    ['titulo' => 'Calcular la raíz y la potencia', 'latex' => '\sqrt{16} + 2^3 = 4 + 8',
                     'explicacion' => 'La raíz cuadrada de 16 es 4 y 2 elevado a 3 es 8.'],
                    ['titulo' => 'Sumar', 'latex' => '4 + 8 = 12',
                     'explicacion' => 'Sumamos los dos resultados.'],
                ],
                'resumen' => 'El resultado de la operación es 12.',
            ],
        ],
    ];
}

// Busca un ejemplo por su expresión (devuelve la parte "ia" o null).
function demo_buscar($expresion)
{
    $expresion = Evaluador::limpiar($expresion);
    foreach (demo_ejemplos() as $e) {
        if ($e['expresion'] === $expresion) {
            return $e['ia'];
        }
    }
    return null;
}
