<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recibos', function (Blueprint $table) {
            $table->id();

            // Empresa dueña del recibo
            $table->foreignId('empresa_local_id')
                  ->constrained('empresa_local')
                  ->onDelete('cascade');

            // Consecutivo por empresa
            $table->unsignedInteger('numero');

            // Fecha del recibo
            $table->date('fecha');

            // Sueldo y parámetros base (opcionalmente editables)
            $table->decimal('sueldo_base', 12, 2)->nullable();
            $table->decimal('admon_base', 12, 2)->nullable();
            $table->boolean('override_parametros')->default(false);

            // Usuario externo
            $table->foreignId('usuario_externo_id')
                  ->constrained('usuario_externos')
                  ->onDelete('cascade');

            // ===== Snapshots (para congelar nombres al momento de liquidar) =====
            $table->string('eps_nombre', 120)->nullable();
            $table->string('arl_nombre', 120)->nullable();
            $table->string('pension_nombre', 120)->nullable();
            $table->string('caja_nombre', 120)->nullable();

            // Días a liquidar (0..255)
            $table->unsignedTinyInteger('dias_liquidar');

            // ARL al momento del cálculo (nivel/clase, actividad y tarifa)
            $table->unsignedTinyInteger('arl_nivel')->nullable();           // 1..5
            $table->string('arl_nivel_riesgo', 20)->nullable();             // etiqueta si aplica
            $table->string('arl_actividad', 10)->nullable();
            $table->decimal('arl_tarifa', 7, 6)->nullable();                // porcentaje humano

            // Valores proporcionales
            $table->decimal('valor_eps', 12, 2);
            $table->decimal('valor_arl', 12, 2);
            $table->decimal('valor_pension', 12, 2);
            $table->decimal('valor_caja', 12, 2);

            // Valores fijos
            $table->decimal('valor_admon', 12, 2);
            $table->decimal('valor_exequial', 12, 2)->default(0);
            $table->decimal('valor_mora', 12, 2)->default(0);
            $table->decimal('otros_servicios', 12, 2)->default(0);

            // Total
            $table->decimal('total', 14, 2);

            // Novedad
            $table->enum('novedad', ['Ingreso', 'Retiro'])->nullable();
            $table->date('fecha_retiro')->nullable();

            // Lote de exportación (opcional)
            $table->foreignId('export_batch_id')
                  ->nullable()
                  ->constrained('export_batches')
                  ->nullOnDelete();

            $table->timestamps();

            // Único por empresa + número (consecutivo por empresa)
            $table->unique(['empresa_local_id', 'numero'], 'recibos_empresa_numero_unique');

            // Índices útiles
            $table->index(['empresa_local_id', 'fecha'], 'recibos_emp_fecha_idx');
            $table->index(['empresa_local_id', 'export_batch_id'], 'recibos_emp_lote_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recibos');
    }
};
