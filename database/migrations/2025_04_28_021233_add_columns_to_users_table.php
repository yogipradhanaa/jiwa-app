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
            $table->enum('gender', ['male', 'female'])->nullable(); // Kolom gender
            $table->date('date_of_birth')->nullable(); // Kolom tanggal lahir
            $table->string('region')->nullable(); // Kolom wilayah
            $table->string('job')->nullable(); // Kolom pekerjaan
            $table->string('referral_code')->nullable(); // Kolom referral code
            $table->unsignedBigInteger('referral_by')->nullable(); // Kolom referral_by

            // Relasi referral_by ke tabel users (ID pengguna yang merujuk)
            $table->foreign('referral_by')->references('id')->on('users')->onDelete('set null');

            $table->string('otp_code')->nullable(); // Kolom OTP
            $table->timestamp('otp_expires_at')->nullable(); // Kolom waktu kadaluarsa OTP
            $table->string('pin_code', 60)->nullable(); // Kolom PIN (gunakan panjang 60 untuk hash)
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
            // Hapus foreign key dulu sebelum menghapus kolom
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
