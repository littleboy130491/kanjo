<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('document_number');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('document_number');
        });

        DB::table('proposals')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($proposals) {
                foreach ($proposals as $proposal) {
                    do {
                        $slug = sprintf(
                            'quo-%s-%s',
                            base_convert((string) $proposal->id, 10, 36),
                            Str::lower(Str::random(8)),
                        );
                    } while (DB::table('proposals')->where('slug', $slug)->exists());

                    DB::table('proposals')
                        ->where('id', $proposal->id)
                        ->update(['slug' => $slug]);
                }
            });

        DB::table('invoices')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($invoices) {
                foreach ($invoices as $invoice) {
                    do {
                        $slug = sprintf(
                            'inv-%s-%s',
                            base_convert((string) $invoice->id, 10, 36),
                            Str::lower(Str::random(8)),
                        );
                    } while (DB::table('invoices')->where('slug', $slug)->exists());

                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update(['slug' => $slug]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropUnique('proposals_slug_unique');
            $table->dropColumn('slug');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_slug_unique');
            $table->dropColumn('slug');
        });
    }
};
