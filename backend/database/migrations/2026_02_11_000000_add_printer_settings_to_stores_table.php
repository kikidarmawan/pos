<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedTinyInteger('paper_width')->default(80)->after('email');
            $table->string('font_size', 20)->default('normal')->after('paper_width');
            $table->boolean('show_store_header')->default(true)->after('font_size');
            $table->string('default_printer_name', 255)->nullable()->after('show_store_header');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['paper_width', 'font_size', 'show_store_header', 'default_printer_name']);
        });
    }
};
