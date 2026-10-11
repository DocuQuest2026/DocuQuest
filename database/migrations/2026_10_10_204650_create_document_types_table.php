<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The documents the registrar offered before they became editable, with their fees and
     * processing days, so existing requests keep pointing at the same documents.
     *
     * @var array<string, array{name: string, fee: int, processing_days: int}>
     */
    private const INITIAL_DOCUMENTS = [
        'transcript_of_records' => ['name' => 'Transcript of Records', 'fee' => 150, 'processing_days' => 5],
        'certificate_of_enrolment' => ['name' => 'Certificate of Enrolment', 'fee' => 50, 'processing_days' => 1],
        'certificate_of_graduation' => ['name' => 'Certificate of Graduation', 'fee' => 100, 'processing_days' => 3],
        'certificate_of_good_moral' => ['name' => 'Certificate of Good Moral Character', 'fee' => 50, 'processing_days' => 2],
        'certified_true_copy_of_grades' => ['name' => 'Certified True Copy of Grades', 'fee' => 100, 'processing_days' => 3],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->unsignedInteger('fee');
            $table->unsignedSmallInteger('processing_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach (self::INITIAL_DOCUMENTS as $key => $document) {
            DB::table('document_types')->insert([
                'key' => $key,
                ...$document,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('record_requests', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable()->after('enrolment_status')->constrained('document_types');
            $table->unsignedInteger('fee_per_copy')->nullable()->after('document_type_id');
        });

        DB::statement('update record_requests set document_type_id = (select id from document_types where document_types.key = record_requests.document_type), fee_per_copy = (select fee from document_types where document_types.key = record_requests.document_type)');

        Schema::table('record_requests', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });

        Schema::table('record_requests', function (Blueprint $table) {
            $table->foreignId('document_type_id')->nullable(false)->change();
            $table->unsignedInteger('fee_per_copy')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('record_requests', function (Blueprint $table) {
            $table->string('document_type')->nullable()->after('enrolment_status');
        });

        DB::statement('update record_requests set document_type = (select key from document_types where document_types.id = record_requests.document_type_id)');

        Schema::table('record_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_type_id');
            $table->dropColumn('fee_per_copy');
        });

        Schema::dropIfExists('document_types');
    }
};
