<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->text('description');
            $table->date('incident_date');
            $table->time('incident_time');
            $table->string('location');
            $table->text('evidence'); // 👈 now REQUIRED (not nullable)
            $table->dateTime('submitted_at')->default(now());
            $table->enum('status', ['Pending', 'Under Review', 'Resolved', 'Rejected', 'Approved'])->default('Pending');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
