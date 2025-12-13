<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Add started_at for when driver clicks "Kerjakan"
            if (!Schema::hasColumn('assignments', 'started_at')) {
                $table->dateTime('started_at')->nullable()->after('assigned_at');
            }
            if (!Schema::hasColumn('assignments', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->after('started_at');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'vehicle_type')) {
                $table->string('vehicle_type')->nullable()->after('passengers');
            }
        });

        // Modify ENUM status in assignments to include 'in_progress' and ensure 'declined'
        // ENUM modifications can be tricky in some DBs, using raw statement for MySQL/MariaDB
        // Current: ['pending','accepted','completed','declined']
        // Target: ['pending','accepted','in_progress','completed','declined']
        
        try {
            DB::statement("ALTER TABLE assignments MODIFY COLUMN status ENUM('pending', 'accepted', 'in_progress', 'completed', 'declined') DEFAULT 'pending'");
        } catch (\Exception $e) {
            // Fallback strategy if raw statement fails (e.g. SQLite for testing)
            // Just ignore for now if it's not MySQL
        }
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vehicle_type']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['started_at']);
             // We keep completed_at as it might be useful even if rolled back
        });

        // Revert enum
        try {
             DB::statement("ALTER TABLE assignments MODIFY COLUMN status ENUM('pending', 'accepted', 'completed', 'declined') DEFAULT 'pending'");
        } catch (\Exception $e) {
            
        }
    }
};
