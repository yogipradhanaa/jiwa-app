<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Ubah kolom order_code menjadi NOT NULL
            $table->string('order_code', 50)->unique()->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Rollback: kembalikan ke nullable
            $table->string('order_code', 50)->nullable()->unique()->change();
        });
    }
};
