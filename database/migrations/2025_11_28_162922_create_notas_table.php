<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();

            // Usuario interno que crea la nota
            $table->foreignId('creado_por_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('titulo');
            $table->text('descripcion')->nullable();

            // Tipo general de tarea (clasificación opcional)
            $table->enum('tipo', [
                'traslado',
                'certificado',
                'cambio_empresa',
                'recordatorio',
                'otro',
            ])->default('otro');

            // Estado de la nota
            $table->enum('estado', [
                'pendiente',
                'en_proceso',
                'resuelto',
                'cancelado',
            ])->default('pendiente');

            $table->date('fecha_vencimiento')->nullable();

            // Cierre / resolución
            $table->dateTime('fecha_resuelto')->nullable();
            $table->foreignId('resuelto_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};
