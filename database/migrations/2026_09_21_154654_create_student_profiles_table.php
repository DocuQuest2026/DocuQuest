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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('student_no', 30)->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('course');
            $table->unsignedSmallInteger('year_level')->nullable();
            $table->string('contact_no', 30);
            $table->string('enrolment_status')->default('enrolled');
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE student_profiles ADD CONSTRAINT student_profiles_enrolment_status_chk CHECK (enrolment_status IN ('enrolled', 'graduated', 'alumni'))");
        DB::statement('ALTER TABLE student_profiles ADD CONSTRAINT student_profiles_year_level_chk CHECK (year_level IS NULL OR year_level BETWEEN 1 AND 6)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
