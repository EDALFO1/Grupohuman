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
     Schema::create('empresa_local', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id'); // Relación con la tabla documentos
            $table->string('numero_documento')->unique();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('contacto')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Clave foránea
            $table->foreign('documento_id')->references('id')->on('documentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_local');
    }
};
