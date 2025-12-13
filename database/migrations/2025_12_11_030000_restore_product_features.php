<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Restore product exclusive fields
        if (!Schema::hasColumn('products', 'is_exclusive')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_exclusive')->default(false);
                $table->boolean('snack')->default(false);
                $table->boolean('water')->default(false);
                $table->boolean('magazine')->default(false);
            });
        }

        // 2. Re-create product_branches table
        if (!Schema::hasTable('product_branches')) {
            Schema::create('product_branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('name')->comment('e.g. Kuta to Ubud');
                $table->string('origin_region')->nullable();
                $table->string('destination_region')->nullable();
                $table->integer('duration_minutes')->default(0);
                $table->decimal('price', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        // 3. Add product_branch_id to orders if not exists
        if (!Schema::hasColumn('orders', 'product_branch_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('product_branch_id')->nullable()->constrained('product_branches')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        // Reverse operations
        if (Schema::hasColumn('orders', 'product_branch_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['product_branch_id']);
                $table->dropColumn('product_branch_id');
            });
        }

        Schema::dropIfExists('product_branches');

        if (Schema::hasColumn('products', 'is_exclusive')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['is_exclusive', 'snack', 'water', 'magazine']);
            });
        }
    }
};
