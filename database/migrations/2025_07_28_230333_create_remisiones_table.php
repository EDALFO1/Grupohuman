<?php
// database/migrations/2025_08_13_000001_create_remisiones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('remisiones', function (Blueprint $table) {
            $table->id();

            // Empresa (consecutivo por empresa)
            $table->foreignId('empresa_local_id')
                  ->constrained('empresa_local')
                  ->onDelete('cascade');

            // Consecutivo interno por empresa
            $table->unsignedInteger('numero');

            $table->date('fecha');

            $table->foreignId('usuario_externo_id')
                  ->constrained('usuario_externos')
                  ->onDelete('cascade');

            $table->unsignedTinyInteger('dias_liquidar');

            // Valores proporcionales
            $table->decimal('valor_eps', 12, 2);
            $table->decimal('valor_arl', 12, 2);
            $table->decimal('valor_pension', 12, 2);
            $table->decimal('valor_caja', 12, 2);

            // Valores fijos
            $table->decimal('valor_admon', 12, 2);
            $table->decimal('valor_exequial', 12, 2)->nullable();
            $table->decimal('valor_mora', 12, 2)->nullable();
            $table->decimal('otros_servicios', 12, 2)->nullable();

            // Total
            $table->decimal('total', 14, 2);

            // Novedad y fecha retiro
            $table->enum('novedad', ['Ingreso', 'Retiro']);
            $table->date('fecha_retiro')->nullable();

            $table->timestamps();

            // Único compuesto por empresa y numero
            $table->unique(['empresa_local_id', 'numero'], 'remisiones_empresa_numero_unique');

            // Índices de consulta frecuentes
            $table->index(['empresa_local_id', 'fecha']);
            $table->index(['usuario_externo_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remisiones');
    }
};
