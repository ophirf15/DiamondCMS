<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resume_variants', function (Blueprint $table): void {
            $table->string('download_pdf')->nullable()->after('builder_json');
            $table->string('download_docx')->nullable()->after('download_pdf');
        });
    }

    public function down(): void
    {
        Schema::table('resume_variants', function (Blueprint $table): void {
            $table->dropColumn(['download_pdf', 'download_docx']);
        });
    }
};
