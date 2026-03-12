<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('response_number');
            $table->string('assigned_to')->nullable();
            $table->string('department')->nullable();
            $table->date('target_date')->nullable();
            $table->string('status');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['report_id', 'response_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_responses');
    }
};
