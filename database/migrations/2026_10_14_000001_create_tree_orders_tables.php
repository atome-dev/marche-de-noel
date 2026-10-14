<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tree_orders', function (Blueprint $table) {
            $table->id();
            // Empty for an order entered by a manager for someone without an account.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_email')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->unsignedInteger('total_cents');
            $table->timestamp('paid_at')->nullable();
            // Manager who recorded the cheque.
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('helloasso_checkout_id')->nullable()->index();
            $table->unsignedBigInteger('helloasso_order_id')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tree_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tree_order_id')->constrained()->cascadeOnDelete();
            // Copied from TREE_SIZES at order time.
            $table->string('size_key', 50);
            $table->string('label');
            $table->string('height', 50);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tree_order_lines');
        Schema::dropIfExists('tree_orders');
    }
};
