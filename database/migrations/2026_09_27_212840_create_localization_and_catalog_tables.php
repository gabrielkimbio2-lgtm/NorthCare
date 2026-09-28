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
        if (! Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('code', 16)->unique();
                $table->string('name');
                $table->string('native_name');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('regions')) {
            Schema::create('regions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['city_id', 'slug']);
            });
        }

        if (! Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
                $table->string('kind', 30);
                $table->string('name');
                $table->string('slug');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['parent_id', 'slug']);
                $table->index(['kind', 'is_active']);
            });
        }

        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ui_translations')) {
            Schema::create('ui_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('language_id')->constrained()->cascadeOnDelete();
                $table->string('key');
                $table->text('value');
                $table->timestamps();

                $table->unique(['language_id', 'key']);
            });
        }

        if (! Schema::hasTable('content_translations')) {
            Schema::create('content_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('language_id')->constrained()->cascadeOnDelete();
                $table->morphs('translatable');
                $table->string('field');
                $table->text('value');
                $table->timestamps();

            });
        }

        $contentTranslationUnique = ['language_id', 'translatable_type', 'translatable_id', 'field'];

        if (! Schema::hasIndex('content_translations', $contentTranslationUnique, 'unique')) {
            Schema::table('content_translations', function (Blueprint $table) use ($contentTranslationUnique): void {
                $table->unique($contentTranslationUnique, 'ct_language_entity_field_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_translations');
        Schema::dropIfExists('ui_translations');
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('languages');
    }
};
