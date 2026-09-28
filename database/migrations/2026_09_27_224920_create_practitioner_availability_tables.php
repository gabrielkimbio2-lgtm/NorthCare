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
        Schema::create('availability_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['practitioner_profile_id', 'day_of_week'], 'availability_profile_day_unique');
        });

        Schema::create('availability_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('reason', 160)->nullable();
            $table->timestamps();

            $table->index(['practitioner_profile_id', 'date'], 'availability_override_profile_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_overrides');
        Schema::dropIfExists('availability_windows');
    }
};
