<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsersTable extends Migration
{
    /**
     * Tambahkan kolom baru ke tabel users.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->nullable(); 
            $table->date('date_of_birth')->nullable(); 
            $table->string('region')->nullable(); 
            $table->string('job')->nullable(); 
            $table->string('phone_number')->nullable()->unique();
            $table->string('referral_code')->unique()->nullable(); 
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('otp_code')->nullable(); 
            $table->timestamp('otp_expires_at')->nullable(); 
            $table->string('pin_code', 60)->nullable(); 
        });
    }

    /**
     * 
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);   
            $table->dropColumn([
                'gender',
                'date_of_birth',
                'region',
                'job',
                'phone_number',
                'referral_code',
                'referred_by',
                'otp_code',
                'otp_expires_at',
                'pin_code'
            ]);
        });
    }
}
