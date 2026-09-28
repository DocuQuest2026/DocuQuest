<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('record_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('released_by')->constrained('users');
            $table->timestampTz('released_at');

            $table->string('representative_name');
            $table->string('representative_id_type');
            $table->string('representative_id_number');
            $table->string('representative_relationship')->nullable();

            $table->string('verification_token', 64)->unique();
            $table->string('pdf_path')->nullable();

            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE document_releases ADD CONSTRAINT document_releases_representative_id_type_chk
            CHECK (representative_id_type IN ('school_id', 'government_id', 'requester_self'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_releases');
    }
};
