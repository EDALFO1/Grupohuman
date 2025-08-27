<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_externos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('documento_id')->constrained('documentos')->onDelete('restrict');
            $table->foreignId('asesor_id')->constrained('asesores')->onDelete('restrict');

            $table->string('numero')->unique();             // doc único global
            $table->date('fecha_expedicion');

            $table->string('primer_apellido');
            $table->string('segundo_apellido')->nullable();
            $table->string('primer_nombre');
            $table->string('segundo_nombre')->nullable();
            $table->date('fecha_nacimiento');

            $table->string('correo_electronico')->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();

            $table->date('fecha_afiliacion');

            $table->enum('sexo', ['M', 'F', 'Otro'])->default('M');

            $table->foreignId('eps_id')->constrained('eps')->onDelete('restrict');
            $table->foreignId('arl_id')->constrained('arls')->onDelete('restrict');
            $table->foreignId('pension_id')->constrained('pensions')->onDelete('restrict');
            $table->foreignId('caja_id')->constrained('cajas')->onDelete('restrict');
            $table->foreignId('subtipo_cotizantes_id')->constrained('subtipo_cotizantes')->onDelete('restrict');

            $table->foreignId('empresa_local_id')->constrained('empresa_local')->onDelete('cascade');
            $table->foreignId('empresa_externa_id')->constrained('empresa_externas')->onDelete('restrict');

            $table->decimal('sueldo', 12, 2)->default(0);
            $table->decimal('admon', 12, 2)->default(0);
            $table->decimal('seg_exequial', 12, 2)->nullable()->default(0);
            $table->decimal('mora', 12, 2)->nullable()->default(0);
            $table->decimal('otros_servicios', 12, 2)->nullable()->default(0);

            $table->boolean('override_parametros')->default(false);

            $table->string('cargo')->nullable();
            $table->boolean('estado')->default(true);

            $table->enum('novedad', ['Ingreso', 'Retiro'])->default('Ingreso');
            $table->date('fecha_retiro')->nullable();

            $table->timestamps();

            // Índices para joins y filtros frecuentes
            $table->index(['empresa_local_id', 'estado']);
            $table->index(['eps_id', 'arl_id', 'pension_id', 'caja_id']);
            $table->index(['asesor_id', 'subtipo_cotizantes_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_externos');
    }
};
