<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spks', function (Blueprint $table): void {
            $table->boolean('activate_translation')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('spks', function (Blueprint $table): void {
            $table->dropColumn('activate_translation');
        });
    }
};
