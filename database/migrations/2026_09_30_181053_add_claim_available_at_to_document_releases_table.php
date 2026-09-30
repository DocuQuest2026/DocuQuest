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
        Schema::table('document_releases', function (Blueprint $table) {
            $table->timestampTz('claim_available_at')->nullable()->after('released_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_releases', function (Blueprint $table) {
            $table->dropColumn('claim_available_at');
        });
    }
};
