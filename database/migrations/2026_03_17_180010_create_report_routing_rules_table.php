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
        Schema::create('report_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('main_category_keywords')->nullable();
            $table->json('category_keywords')->nullable();
            $table->json('classifications')->nullable();
            $table->string('target_position_code');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('route_on_submission')->default(true);
            $table->boolean('route_on_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('target_position_code');
            $table->index('priority');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_routing_rules');
    }
};
