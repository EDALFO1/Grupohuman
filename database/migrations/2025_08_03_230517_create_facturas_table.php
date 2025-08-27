<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();

            // Datos clave
            $table->string('numero')->unique();
            $table->date('fecha_emision');
            $table->string('moneda')->default('COP');
            $table->enum('tipo', ['Factura', 'Nota Crédito', 'Nota Débito'])->default('Factura');

            // Relaciones
            $table->foreignId('empresa_local_id')->constrained('empresa_local')->onDelete('cascade'); // Emisor
            $table->foreignId('cliente_id')->constrained('empresa_externas')->onDelete('cascade'); // Receptor

            // Totales
            $table->decimal('subtotal', 14, 2);
            $table->decimal('iva', 14, 2)->default(0);
            $table->decimal('retencion', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('total', 14, 2);

            // Estado electrónico
            $table->text('xml_ubl')->nullable(); // XML UBL firmado
            $table->string('cufe')->nullable();  // CUFE generado
            $table->enum('estado_envio', ['Pendiente', 'Enviado', 'Aceptado', 'Rechazado'])->default('Pendiente');
            $table->text('respuesta_dian')->nullable(); // XML respuesta de la DIAN

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
