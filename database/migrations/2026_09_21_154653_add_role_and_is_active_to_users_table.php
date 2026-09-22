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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_chk CHECK (role IN ('student', 'staff', 'admin'))");

        // The design stores absolute instants; the connection session zone is Asia/Manila (config/database.php).
        DB::statement('ALTER TABLE users
            ALTER COLUMN email_verified_at TYPE timestamptz,
            ALTER COLUMN created_at TYPE timestamptz,
            ALTER COLUMN updated_at TYPE timestamptz');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE users
            ALTER COLUMN email_verified_at TYPE timestamp(0),
            ALTER COLUMN created_at TYPE timestamp(0),
            ALTER COLUMN updated_at TYPE timestamp(0)');

        DB::statement('ALTER TABLE users DROP CONSTRAINT users_role_chk');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
