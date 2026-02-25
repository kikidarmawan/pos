<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending, active, expired, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('midtrans_order_id', 100)->nullable()->unique();
            $table->string('midtrans_payment_type', 50)->nullable();
            $table->string('midtrans_transaction_status', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
