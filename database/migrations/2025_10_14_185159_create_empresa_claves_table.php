<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_claves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_local_id')->constrained('empresa_local')->onDelete('cascade');
            $table->foreignId('servicio_externo_id')->constrained('servicios_externos')->onDelete('cascade');
            $table->string('usuario')->nullable();
            $table->string('correo_registrado')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_claves');
    }
};
