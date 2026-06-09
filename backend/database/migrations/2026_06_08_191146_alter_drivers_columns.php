<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('name', 60)->change();
            $table->string('birth_place', 50)->nullable()->change();
            $table->string('ktp_photo', 100)->nullable()->change();
            $table->string('photo', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->string('birth_place', 255)->nullable()->change();
            $table->string('ktp_photo', 255)->nullable()->change();
            $table->string('photo', 255)->nullable()->change();
        });
    }
};
