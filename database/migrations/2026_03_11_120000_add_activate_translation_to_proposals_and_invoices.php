<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->boolean('activate_translation')->default(false)->after('currency');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('activate_translation')->default(false)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('activate_translation');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('activate_translation');
        });
    }
};
