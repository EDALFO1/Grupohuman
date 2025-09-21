<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arl_usuarios', function (Blueprint $table) {
            $table->id();

            // Empresa local desde sesión (mismo patrón que usas)
            $table->foreignId('empresa_local_id')->constrained('empresa_local')->onDelete('cascade');

            // Campos solicitados
            $table->foreignId('documento_id')->constrained('documentos')->onDelete('restrict'); // tipo documento
            $table->string('numero')->unique(); // número doc
            $table->string('nombre'); // nombre completo en una sola columna (como pediste)

            $table->date('fecha_ingreso');

            // Nivel de ARL → relación con tabla arls (ya la tienes) que incluye nivel y %.
            $table->foreignId('arl_id')->constrained('arls')->onDelete('restrict');

            // Empresa externa
            $table->foreignId('empresa_externa_id')->constrained('empresa_externas')->onDelete('restrict');

            // Para calcular el valor
            $table->decimal('base_cotizacion', 12, 2)->default(0); // por defecto se autollenará con ValoresService->salario
            $table->decimal('administracion', 12, 2)->default(0);  // por defecto se autollenará con ValoresService->administracion

            // Estado / Retiro
            $table->boolean('estado')->default(true);
            $table->date('fecha_retiro')->nullable();

            // Para permitir sobreescribir valores por usuario (igual a tu patrón)
            $table->boolean('override_parametros')->default(false);

            $table->timestamps();

            $table->index(['empresa_local_id', 'estado']);
            $table->index(['documento_id', 'arl_id', 'empresa_externa_id']);
            $table->index('numero');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arl_usuarios');
    }
};
