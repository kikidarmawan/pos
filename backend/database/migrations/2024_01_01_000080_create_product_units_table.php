<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->decimal('conversion_factor', 15, 3)->comment('Faktor konversi ke base unit. Contoh: 1 Roll = 100 Meter, maka conversion_factor = 100');
            $table->decimal('selling_price', 15, 2)->comment('Harga jual untuk unit ini');
            $table->string('barcode')->nullable()->comment('Barcode khusus untuk unit ini');
            $table->boolean('is_default')->default(false)->comment('Unit default untuk penjualan');
            $table->timestamps();

            $table->unique(['product_id', 'unit_id']);
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
