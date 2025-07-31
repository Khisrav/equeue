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
        Schema::create('queue', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('medical_institution_id')->constrained('medical_institutions');
            $table->foreignId('doctor_id')->constrained('users');
            $table->enum('status', ['waiting', 'called', 'skipped', 'done', 'canceled'])->default('waiting');
            $table->string('notes')->nullable();
            $table->string('doctor_notes')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue');
    }
};
