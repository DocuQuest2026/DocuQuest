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
        Schema::create('record_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no', 20)->unique();
            $table->string('student_no', 30)->index();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('course');
            $table->string('enrolment_status');
            $table->string('email');
            $table->string('contact_no', 30);
            $table->string('document_type');
            $table->unsignedSmallInteger('copies')->default(1);
            $table->text('purpose');
            $table->string('status', 20)->default('pending')->index();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_requests');
    }
};
