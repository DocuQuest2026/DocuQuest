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
        Schema::table('record_requests', function (Blueprint $table) {
            $table->string('designated_representative_id_type')->nullable()->after('designated_representative_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('record_requests', function (Blueprint $table) {
            $table->dropColumn('designated_representative_id_type');
        });
    }
};
