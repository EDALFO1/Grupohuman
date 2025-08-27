<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('valores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_local_id')
                ->constrained('empresa_local')
                ->onDelete('cascade');

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable(); // null = abierta

            $table->decimal('salario', 12, 2);
            $table->decimal('administracion', 12, 2);

            $table->boolean('activa')->default(true);

            $table->timestamps();

            $table->index(['empresa_local_id', 'activa']);
            $table->index(['empresa_local_id', 'fecha_inicio', 'fecha_fin']);
            $table->unique(['empresa_local_id', 'fecha_inicio'], 'valores_unique_inicio');
        });

        try {
            DB::statement("
                ALTER TABLE valores
                ADD CONSTRAINT chk_valores_fechas
                CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
            ");
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        try { DB::statement("ALTER TABLE valores DROP CONSTRAINT chk_valores_fechas"); } catch (\Throwable $e) {}
        Schema::dropIfExists('valores');
    }
};
