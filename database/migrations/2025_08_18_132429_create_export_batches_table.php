<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('export_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_local_id')
                  ->constrained('empresa_local')
                  ->onDelete('cascade');

            // Código libre para identificar el lote (opcional)
            $table->string('codigo', 120)->nullable();

            // 'YYYY-MM' si todos los recibos del lote son del mismo mes
            $table->string('periodo', 7)->nullable();

            // Conteo / total fotos del lote
            $table->unsignedInteger('recibos_count')->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // Índice útil para filtrar por empresa y período
            $table->index(['empresa_local_id', 'periodo'], 'exportes_emp_periodo_idx');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('export_batches');
    }
};
