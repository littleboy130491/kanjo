<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spks', function (Blueprint $table): void {
            $table->json('signature')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table): void {
            $table->dropColumn('signature');
        });
    }
};
