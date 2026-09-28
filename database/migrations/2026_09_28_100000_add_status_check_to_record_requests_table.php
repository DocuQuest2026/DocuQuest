<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE record_requests ADD CONSTRAINT record_requests_status_chk
            CHECK (status IN ('pending', 'approved', 'released', 'rejected', 'cancellation_requested', 'cancelled'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE record_requests DROP CONSTRAINT record_requests_status_chk');
    }
};
