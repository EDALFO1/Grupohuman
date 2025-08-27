<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arls', function (Blueprint $table) {
            $table->id();

            // Datos base
            $table->string('nombre');                 // Ej. "ARL SURA"
            $table->string('codigo');                 // Ej. "SURA" (puede repetirse en distintos niveles)
            $table->unsignedTinyInteger('nivel');     // 1..5
            $table->string('actividad_economica', 7)->nullable()->index(); // 7 dígitos (CIIU)
            $table->decimal('porcentaje', 6, 4);      // 0..100 con 4 decimales

            $table->timestamps();

            // Unicidad compuesta: (codigo, nivel)
            $table->unique(['codigo', 'nivel'], 'arls_codigo_nivel_unique');
        });

        // Checks opcionales (MySQL 8+ / Postgres). Ignora si el motor no los soporta.
        try {
            DB::statement("ALTER TABLE arls
                ADD CONSTRAINT chk_arls_nivel CHECK (nivel BETWEEN 1 AND 5)");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE arls
                ADD CONSTRAINT chk_arls_porcentaje CHECK (porcentaje >= 0 AND porcentaje <= 100)");
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // Quitar CHECKs si existen (algunos motores lo requieren antes de drop)
        try { DB::statement("ALTER TABLE arls DROP CONSTRAINT chk_arls_nivel"); } catch (\Throwable $e) {}
        try { DB::statement("ALTER TABLE arls DROP CONSTRAINT chk_arls_porcentaje"); } catch (\Throwable $e) {}

        Schema::dropIfExists('arls');
    }
};
