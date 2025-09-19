<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incapacidad_observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incapacidad_id')->constrained('incapacidades')->onDelete('cascade');
            $table->text('nota');
            $table->timestamps();

            $table->index(['incapacidad_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incapacidad_observaciones');
    }
};
