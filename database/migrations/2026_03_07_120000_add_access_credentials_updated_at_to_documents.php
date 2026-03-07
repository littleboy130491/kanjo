<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->timestamp('access_credentials_updated_at')->nullable()->after('access_password');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('access_credentials_updated_at')->nullable()->after('access_password');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('access_credentials_updated_at');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('access_credentials_updated_at');
        });
    }
};
