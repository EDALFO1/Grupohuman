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
       Schema::create('empresa_externas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_id'); // clave foránea
            $table->string('numero');
            $table->string('nombre');
            $table->string('direccion');
            $table->string('telefono');
            $table->string('contacto');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('documento_id')->references('id')->on('documentos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_externas');
    }
};
