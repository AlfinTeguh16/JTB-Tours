<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Create product_branches table
        Schema::create('product_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g. "Kuta Selatan - Ubud"
            $table->integer('duration_minutes'); // e.g. 150 for 2.5 hours
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        // 2. Update products table
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_exclusive')->default(false);
            $table->text('exclusive_benefits')->nullable(); // e.g. "snack, air & majalah"
        });

        // 3. Update orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('product_branch_id')->nullable()->constrained('product_branches')->nullOnDelete();
            // Note: estimated_duration_minutes already exists in orders, we will just use it more effectively or rely on branch
        });

        // 4. Update assignments table
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->dateTime('rejected_at')->nullable();
        });
        
         // 5. Update vehicles table - user mentioned "hapus jumlah mobil dan ubah menjadi jenis mobil"
         // The 'vehicles' table already has 'type' (APV, HiAce, etc) and 'plate_number' (specific unit).
         // So we don't need 'count' on vehicles table. 
         // However, ensure capacity can handle 12 & 24.
         // We might want to add a 'model' column if 'type' is category (Bus) and model is specific (HiAce)? 
         // But current 'type' can be 'HiAce', 'Bus'. 
    }

    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn(['vehicle_id', 'rejection_reason', 'rejected_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_branch_id']);
            $table->dropColumn(['product_branch_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_exclusive', 'exclusive_benefits']);
        });

        Schema::dropIfExists('product_branches');
    }
};
