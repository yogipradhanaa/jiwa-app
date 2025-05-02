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
            $table->string('referral_code')->nullable(); 
            $table->unsignedBigInteger('referral_by')->nullable(); 

            $table->foreign('referral_by')->references('id')->on('users')->onDelete('set null');

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
            $table->dropForeign(['referral_by']);
            
            $table->dropColumn([
                'gender',
                'date_of_birth',
                'region',
                'job',
                'referral_code',
                'referral_by',
                'otp_code',
                'otp_expires_at',
                'pin_code'
            ]);
        });
    }
}
