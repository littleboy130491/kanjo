<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('address')->nullable();
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->text('client_address')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->text('client_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('client_address');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('client_address');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
