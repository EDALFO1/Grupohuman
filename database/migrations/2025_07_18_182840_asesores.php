<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::create('asesores', function (Blueprint $table) {
        $table->id();

        // Relación con la tabla documentos (agregamos clave foránea y restricción)
        $table->foreignId('documento_id')->constrained('documentos')->onDelete('cascade');

        // Campos básicos
        $table->string('numero_documento', 20)->unique(); // Limita longitud razonable
        $table->string('nombre', 100);
        $table->string('direccion', 150);
        $table->string('telefono', 20);
        $table->string('email', 100)->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
