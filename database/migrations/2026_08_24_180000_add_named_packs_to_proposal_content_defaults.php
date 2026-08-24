<?php

use App\Models\ProposalContentDefault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposal_content_defaults', function (Blueprint $table): void {
            $table->dropUnique(['field_key']);
            $table->string('name')->nullable()->after('id');
            $table->string('slug')->nullable()->after('name');
            $table->boolean('is_default')->default(false)->after('slug');
        });

        $hasDefault = false;

        ProposalContentDefault::query()->orderBy('id')->each(function (ProposalContentDefault $record) use (&$hasDefault): void {
            $name = filled($record->name)
                ? (string) $record->name
                : ($record->field_key === ProposalContentDefault::GLOBAL_FIELD_KEY ? 'Default' : Str::headline((string) $record->field_key));
            $slug = filled($record->slug)
                ? (string) $record->slug
                : ($record->field_key === ProposalContentDefault::GLOBAL_FIELD_KEY ? 'default' : Str::slug((string) $record->field_key));

            $record->forceFill([
                'name' => $name,
                'slug' => $slug !== '' ? $slug : 'pack-'.$record->id,
                'is_default' => $hasDefault ? false : true,
            ])->save();

            $hasDefault = true;
        });

        Schema::table('proposal_content_defaults', function (Blueprint $table): void {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('proposal_content_defaults', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn(['name', 'slug', 'is_default']);
            $table->unique('field_key');
        });
    }
};
