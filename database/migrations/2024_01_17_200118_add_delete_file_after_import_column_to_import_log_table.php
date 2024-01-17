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
        Schema::table('import_log', function (Blueprint $table) {
            $table->boolean('delete_file_after_import')->after('config')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_log', function (Blueprint $table) {
            $table->dropColumn('delete_file_after_import');
        });
    }
};
