<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Drop foreign key and column from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['product_branch_id']);
            $table->dropColumn('product_branch_id');
        });

        // 2. Drop product_branches table
        Schema::dropIfExists('product_branches');
    }

    public function down()
    {
        // 1. Recreate product_branches table
        Schema::create('product_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('duration_minutes');
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });

        // 2. Re-add product_branch_id to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('product_branch_id')->nullable()->constrained('product_branches')->nullOnDelete();
        });
    }
};
