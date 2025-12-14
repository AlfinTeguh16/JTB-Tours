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
        // Convert status enum to string to allow 'in_progress' and flexible statuses
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to enums (simplified, might lose custom values if strict db)
        Schema::table('orders', function (Blueprint $table) {
            // We cannot easily revert to enum with strict values if 'in_progress' exists in data
            // So we effectively leave it or try best effort. 
            // For now, let's just try to set it back to text but logically it was enum.
            // Converting back to Enum in SQLite is hard.
        });
    }
};
