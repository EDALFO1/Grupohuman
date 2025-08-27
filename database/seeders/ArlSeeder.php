<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArlSeeder extends Seeder
{
    public function run()
    {
        DB::table('arls')->truncate(); // Limpia la tabla antes de insertar

        
        $arls = [
    ['codigo' => 'ARL01', 'nombre' => 'SURA',      'porcentaje' => 0.00522, 'nivel' => 1],
    ['codigo' => 'ARL02', 'nombre' => 'Colpatria', 'porcentaje' => 0.01044, 'nivel' => 2],
    ['codigo' => 'ARL03', 'nombre' => 'Bolívar',   'porcentaje' => 0.02436, 'nivel' => 3],
    ['codigo' => 'ARL04', 'nombre' => 'Equidad',   'porcentaje' => 0.04350, 'nivel' => 4],
    ['codigo' => 'ARL05', 'nombre' => 'Positiva',  'porcentaje' => 0.06960, 'nivel' => 5],
];


        DB::table('arls')->insert($arls);
    }
}
