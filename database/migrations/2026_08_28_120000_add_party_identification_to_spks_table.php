<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spks', function (Blueprint $table): void {
            $table->json('party_identification')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table): void {
            $table->dropColumn('party_identification');
        });
    }
};
