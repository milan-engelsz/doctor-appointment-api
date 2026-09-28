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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('doctor_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('patient_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->string('status', 20);

            $table->string('cancellation_reason', 500)->nullable();

            $table->timestamps();

            $table->index(['doctor_id', 'start_time']);
            $table->index(['patient_id', 'start_time']);
            $table->index(['status', 'start_time']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
