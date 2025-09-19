<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incapacidades', function (Blueprint $table) {
            $table->id();

            // Relación opcional con usuario_externos
            $table->foreignId('usuario_externo_id')->nullable()
                ->constrained('usuario_externos')->onDelete('set null');

            // Datos del usuario
            $table->string('documento'); // número de documento del usuario externo
            $table->string('nombre');    // nombre completo del usuario externo

            // Empresas
            $table->foreignId('empresa_externa_id')->nullable()
                ->constrained('empresa_externas')->onDelete('set null');
            $table->foreignId('empresa_local_id')->nullable()
                ->constrained('empresa_local')->onDelete('set null');

            // Entidad (EPS/ARL)
            $table->enum('entidad_tipo', ['EPS', 'ARL']);
            $table->foreignId('eps_id')->nullable()->constrained('eps')->onDelete('set null');
            $table->foreignId('arl_id')->nullable()->constrained('arls')->onDelete('set null');
            $table->string('entidad_nombre');

            // Fechas y días (inclusive)
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedInteger('dias_solicitados');

            $table->date('fecha_radicacion')->nullable();

            // Estado y vigencia
            $table->enum('estado', ['transcrita','radicada','aprobada','liquidada','rechazada','pagada'])
                  ->default('transcrita');
            $table->boolean('cerrada')->default(false);   // <--- integrado
            $table->date('fecha_cierre')->nullable();     // <--- integrado

            // Otros
            $table->longText('observaciones_libres')->nullable();
            $table->date('fecha_pago')->nullable();

            $table->timestamps();

            // Índices
            $table->index('documento');
            $table->index('estado');
            $table->index(['empresa_local_id', 'empresa_externa_id']);
            $table->index(['entidad_tipo', 'eps_id', 'arl_id']);
            $table->index(['fecha_inicio', 'fecha_fin']);
            $table->index(['cerrada', 'fecha_cierre']); // <--- integrado
        });

        // Check opcional (si tu motor lo soporta)
        try {
            DB::statement("ALTER TABLE incapacidades
                ADD CONSTRAINT chk_incapacidades_fechas CHECK (fecha_fin >= fecha_inicio)");
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        // Quitar el CHECK si el motor lo requiere
        try { DB::statement("ALTER TABLE incapacidades DROP CONSTRAINT chk_incapacidades_fechas"); } catch (\Throwable $e) {}

        Schema::dropIfExists('incapacidades');
    }
};
