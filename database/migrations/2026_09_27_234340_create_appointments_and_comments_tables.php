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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->date('appointment_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('status', 30)->default('pending');
            $table->text('patient_notes')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->timestamps();

            $table->index(['practitioner_profile_id', 'appointment_date', 'status'], 'appt_profile_date_status_index');
            $table->index(['user_id', 'appointment_date', 'status'], 'appt_user_date_status_index');
        });

        Schema::create('appointment_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_reported')->default(false);
            $table->text('report_reason')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();

            $table->index(['practitioner_profile_id', 'status'], 'comment_profile_status_index');
            $table->index(['is_reported', 'status'], 'comment_report_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_comments');
        Schema::dropIfExists('appointments');
    }
};
