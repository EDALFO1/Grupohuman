<?php

return [
    // Ajusta estos dos valores cada año:
    'smmlv' => env('PLANES_SMMLV', 1423500),   // Salario mínimo
    'admin' => env('PLANES_ADMIN', 48000),     // Administración fija

    // Porcentajes fijos
    'porcentajes' => [
        'eps'     => 0.04,    // 4%
        'caja'    => 0.04,    // 4%
        'pension' => 0.16,    // 16%
        'arl'     => [
            1 => 0.00522,     // 0.522%
            2 => 0.01044,     // 1.044%
            3 => 0.0244,      // 2.44%
            4 => 0.0435,      // 4.35%
            5 => 0.0696,      // 6.96%
        ],
    ],
];
