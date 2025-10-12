<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('usuario_externos', function (Blueprint $table) {
        $table->string('google_drive_folder_id')->nullable()->after('empresa_local_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuario_externos', function (Blueprint $table) {
            //
        });
    }
};
