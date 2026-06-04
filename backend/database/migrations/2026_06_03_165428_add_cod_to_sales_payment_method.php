<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer', 'credit', 'other', 'cod') DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'card', 'transfer', 'credit', 'other') DEFAULT 'cash'");
    }
};
