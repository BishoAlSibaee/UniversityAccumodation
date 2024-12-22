<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('room_id');
            $table->string('student_name');
            $table->string('room_number');
            $table->date('start_date');
            $table->date('expire_date');
            $table->json('facility_ids')->nullable();
            $table->string('start_time')->nullable();
            $table->string('expire_time')->nullable();
            $table->tinyInteger('is_available')->default(0)->comment("0=>is available , 1=>is Not available");
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
