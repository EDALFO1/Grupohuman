<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuario_externos', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('google_drive_folder_id');
            // Si "google_drive_folder_id" no existe en esta migración base,
            // puedes usar ->after('cargo') o el campo que prefieras.
        });
    }

    public function down(): void
    {
        Schema::table('usuario_externos', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};
