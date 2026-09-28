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
        Schema::table('appointment_comments', function (Blueprint $table) {
            $table->renameColumn('rating', 'doctor_rating');
            $table->unsignedTinyInteger('service_rating')->nullable()->after('doctor_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_comments', function (Blueprint $table) {
            $table->dropColumn('service_rating');
            $table->renameColumn('doctor_rating', 'rating');
        });
    }
};
