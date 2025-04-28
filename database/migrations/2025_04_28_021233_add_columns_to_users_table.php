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
            $table->enum('gender', ['male', 'female', 'other'])->nullable(); // Kolom gender
            $table->date('date_of_birth')->nullable(); // Kolom tanggal lahir
            $table->string('region')->nullable(); // Kolom wilayah
            $table->string('job')->nullable(); // Kolom pekerjaan
            $table->string('referral_code')->nullable(); // Kolom referral code
            $table->unsignedBigInteger('referral_by')->nullable(); // Kolom referral_by
            $table->string('otp_code')->nullable(); // Kolom OTP
            $table->timestamp('otp_expires_at')->nullable(); // Kolom waktu kadaluarsa OTP
            $table->string('pin_code')->nullable(); // Kolom PIN
        });
    }

    /**
     * Balikkan perubahan jika migrasi dibatalkan.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
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
