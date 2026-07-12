<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 40);
            $table->string('path', 500)->nullable();
            $table->unsignedBigInteger('page_id')->nullable()->index();
            $table->unsignedBigInteger('resume_variant_id')->nullable()->index();
            $table->string('visitor_hash', 64)->nullable()->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->string('referrer', 500)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
