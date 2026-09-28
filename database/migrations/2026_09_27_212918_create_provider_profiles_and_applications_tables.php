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
        Schema::create('practitioner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->text('bio')->nullable();
            $table->json('visibility')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['city_id', 'category_id', 'is_published'], 'profiles_city_category_published_index');
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->json('visibility')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('institution_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->json('visibility')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['city_id', 'region_id', 'is_published']);
        });

        Schema::create('profile_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('application_type', 30);
            $table->string('status', 30)->default('pending');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('contact_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->json('visibility')->nullable();
            $table->foreignId('matched_practitioner_profile_id')->nullable()->unique()->constrained('practitioner_profiles')->nullOnDelete();
            $table->foreignId('matched_institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('matched_institution_location_id')->nullable()->constrained('institution_locations')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['application_type', 'status']);
            $table->index(['applicant_user_id', 'application_type', 'status'], 'profile_apps_applicant_type_status_index');
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_application_id')->constrained()->cascadeOnDelete();
            $table->string('storage_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('byte_size');
            $table->timestamps();
        });

        Schema::create('service_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('practitioner_profile_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->timestamps();

            $table->unique(['practitioner_profile_id', 'service_id'], 'profile_service_unique');
        });

        Schema::create('category_institution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['category_id', 'institution_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_institution');
        Schema::dropIfExists('practitioner_profile_service');
        Schema::dropIfExists('service_suggestions');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('profile_applications');
        Schema::dropIfExists('institution_locations');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('practitioner_profiles');
    }
};
