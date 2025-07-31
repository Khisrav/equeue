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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('medical_institution_id')->nullable()->constrained('medical_institutions');
            $table->string('specialization')->nullable();
            $table->integer('room_number')->nullable();
            $table->string('avatar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['medical_institution_id']);
            $table->dropColumn('medical_institution_id');
            $table->dropColumn('specialization');
            $table->dropColumn('room_number');
            $table->dropColumn('avatar');
        });
    }
};
