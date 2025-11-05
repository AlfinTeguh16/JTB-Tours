<?php
// database/migrations/2025_11_02_000000_create_jtb_tours_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('role', ['super_admin','admin','staff','driver','guide'])->default('staff');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('join_date')->nullable();
            $table->string('password');
            $table->integer('monthly_work_limit')->default(200); // jam limit per bulan
            $table->integer('used_hours')->default(0);
            $table->enum('status', ['online','offline'])->default('offline');
            $table->timestamps();
        });

        // products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('capacity')->default(4);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // vehicles
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('type'); // bus, minibus, suv, dll
            $table->string('plate_number')->unique();
            $table->string('color')->nullable();
            $table->enum('status',['available','in_use','maintenance'])->default('available');
            $table->smallInteger('year')->nullable();
            $table->integer('capacity')->default(4);
            $table->timestamps();
        });

        // orders (depends on products and users.created_by)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->dateTime('pickup_time');
            $table->dateTime('arrival_time')->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('passengers')->default(1);
            $table->string('pickup_location')->nullable();
            $table->string('destination')->nullable();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('adults')->default(0);
            $table->integer('children')->default(0);
            $table->integer('babies')->default(0);
            $table->integer('vehicle_count')->default(1);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status',['pending','assigned','completed','canceled'])->default('pending');
            $table->timestamps();
        });

        // work_schedules
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('month'); // 1..12
            $table->smallInteger('year');
            $table->integer('total_hours')->default(200);
            $table->float('used_hours')->default(0);
            $table->timestamps();

            $table->unique(['user_id','month','year']);
        });

        // assignments (depends on orders, users)
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users');
            $table->foreignId('guide_id')->nullable()->constrained('users');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status',['pending','accepted','completed','declined'])->default('pending');
            
            $table->time('workstart')->nullable();
            $table->time('workend')->nullable();
            
            $table->dateTime('assigned_at')->nullable();
            $table->timestamps();
        });

        // optional: indices to speed up common queries
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status']);
            $table->index(['pickup_time']);
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->index(['driver_id','status']);
            $table->index(['guide_id','status']);
    });
    }

    public function down()
    {
        // drop in reverse order to avoid FK errors
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['driver_id','status']);
            $table->dropIndex(['guide_id','status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['pickup_time']);
        });

        Schema::dropIfExists('assignments');
        Schema::dropIfExists('work_schedules');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('products');
        Schema::dropIfExists('users');
    }
};
