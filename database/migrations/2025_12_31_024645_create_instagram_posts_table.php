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
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('bulan');
            $table->string('tahun');
            $table->text('judul_pemberitaan');
            $table->string('link_pemberitaan')->unique();
            $table->string('platform')->default('Instagram');
            $table->string('tipe_konten'); // Feeds, Reels
            $table->string('pic_unit')->nullable();
            $table->string('akun');
            $table->string('kategori')->default('Korporat');
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('views')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};