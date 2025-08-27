<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('periodo_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_local_id')->constrained('empresa_local')->cascadeOnDelete();
            $table->foreignId('usuario_externo_id')->constrained('usuario_externos')->cascadeOnDelete();

            // Período en formato YYYY-MM (indexable y legible)
            $table->char('periodo', 7); // p.ej. '2025-08'

            // Estado del usuario en ese período
            $table->enum('estado', ['Activo', 'Retirado']);

            // Trazabilidad
            $table->foreignId('recibo_id')->nullable()->constrained('recibos')->nullOnDelete();

            $table->timestamps();

            $table->unique(['empresa_local_id','usuario_externo_id','periodo'], 'periodo_usuarios_unique');
            $table->index(['empresa_local_id','periodo']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('periodo_usuarios');
    }
};
