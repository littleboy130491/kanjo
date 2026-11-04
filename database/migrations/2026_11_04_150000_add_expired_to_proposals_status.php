<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table): void {
            $table->enum('status', ['draft', 'published', 'expired'])->default('draft')->change();
        });
    }

    public function down(): void
    {
        DB::table('proposals')->where('status', 'expired')->update(['status' => 'draft']);

        Schema::table('proposals', function (Blueprint $table): void {
            $table->enum('status', ['draft', 'published'])->default('draft')->change();
        });
    }
};
