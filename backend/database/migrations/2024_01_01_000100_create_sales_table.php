<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('warehouse_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->date('sale_date');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid', 15, 2)->default(0);
            $table->decimal('change', 15, 2)->default(0);
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'credit', 'other'])->default('cash');
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'sale_date']);
            $table->index(['user_id', 'sale_date']);
            $table->index('status');
        });

        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('restrict');
            $table->foreignId('unit_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 3);
            $table->decimal('price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index('sale_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_details');
        Schema::dropIfExists('sales');
    }
};
