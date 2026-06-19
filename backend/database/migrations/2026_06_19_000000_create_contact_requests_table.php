<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('phone', 30);
            $table->string('email', 150);
            $table->string('comment', 2000);
            $table->string('ai_category', 50)->nullable();
            $table->string('ai_sentiment', 20)->nullable();
            $table->string('ai_priority', 20)->nullable();
            $table->text('ai_summary')->nullable();
            $table->boolean('ai_available')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_requests');
    }
};
