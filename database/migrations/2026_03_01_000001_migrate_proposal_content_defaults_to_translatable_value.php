<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proposal_content_defaults')) {
            return;
        }

        if (! Schema::hasColumn('proposal_content_defaults', 'value')) {
            Schema::table('proposal_content_defaults', function (Blueprint $table): void {
                $table->json('value')->nullable();
            });
        }

        $hasValueEn = Schema::hasColumn('proposal_content_defaults', 'value_en');
        $hasValueId = Schema::hasColumn('proposal_content_defaults', 'value_id');

        if ($hasValueEn || $hasValueId) {
            DB::table('proposal_content_defaults')
                ->select(['id', 'value', 'value_en', 'value_id'])
                ->orderBy('id')
                ->each(function (object $row): void {
                    $translations = $this->decodeJsonArray($row->value);
                    $translations['en'] = $translations['en'] ?? $this->decodeJsonArray($row->value_en);
                    $translations['id'] = $translations['id'] ?? $this->decodeJsonArray($row->value_id);

                    DB::table('proposal_content_defaults')
                        ->where('id', $row->id)
                        ->update([
                            'value' => json_encode($translations, JSON_UNESCAPED_UNICODE),
                        ]);
                });

            Schema::table('proposal_content_defaults', function (Blueprint $table) use ($hasValueEn, $hasValueId): void {
                if ($hasValueEn) {
                    $table->dropColumn('value_en');
                }

                if ($hasValueId) {
                    $table->dropColumn('value_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('proposal_content_defaults')) {
            return;
        }

        if (! Schema::hasColumn('proposal_content_defaults', 'value_en')) {
            Schema::table('proposal_content_defaults', function (Blueprint $table): void {
                $table->json('value_en')->nullable();
            });
        }

        if (! Schema::hasColumn('proposal_content_defaults', 'value_id')) {
            Schema::table('proposal_content_defaults', function (Blueprint $table): void {
                $table->json('value_id')->nullable();
            });
        }

        if (Schema::hasColumn('proposal_content_defaults', 'value')) {
            DB::table('proposal_content_defaults')
                ->select(['id', 'value'])
                ->orderBy('id')
                ->each(function (object $row): void {
                    $translations = $this->decodeJsonArray($row->value);

                    DB::table('proposal_content_defaults')
                        ->where('id', $row->id)
                        ->update([
                            'value_en' => json_encode($this->normalizeLocaleValue($translations['en'] ?? []), JSON_UNESCAPED_UNICODE),
                            'value_id' => json_encode($this->normalizeLocaleValue($translations['id'] ?? []), JSON_UNESCAPED_UNICODE),
                        ]);
                });

            Schema::table('proposal_content_defaults', function (Blueprint $table): void {
                $table->dropColumn('value');
            });
        }
    }

    private function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeLocaleValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
};
