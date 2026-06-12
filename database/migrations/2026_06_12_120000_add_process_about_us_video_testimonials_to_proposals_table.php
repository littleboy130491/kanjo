<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->json('our_process')->nullable()->after('faq');
            $table->json('about_us')->nullable()->after('our_process');
            $table->json('video_testimonials')->nullable()->after('about_us');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['our_process', 'about_us', 'video_testimonials']);
        });
    }
};
