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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('room_id')->unique();
            $table->string('student_name');
            $table->string('room_number');
            $table->date('start_date');
            $table->date('expire_date');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('expire_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
