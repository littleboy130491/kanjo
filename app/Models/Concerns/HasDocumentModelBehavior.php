<?php

namespace App\Models\Concerns;

use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasDocumentModelBehavior
{
    protected static function bootHasDocumentModelBehavior(): void
    {
        static::saving(function (Model $model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }

            static::syncAccessCredentialVersion($model);
            static::beforeDocumentSaving($model);

            if (blank($model->slug)) {
                $model->slug = null;

                return;
            }

            $model->slug = Str::slug((string) $model->slug);
        });

        static::creating(function (Model $model): void {
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }

            if (empty($model->updated_by) && auth()->check()) {
                $model->updated_by = auth()->id();
            }

            static::syncIssuePeriodOnCreate($model);

            $dateColumn = static::documentDateColumn();
            $dateValue = $model->{$dateColumn};
            $date = $dateValue ? Carbon::parse($dateValue) : now();
            $suffix = static::resolveDocumentSuffixForCreate($model);

            if (filled($model->document_number_raw)) {
                $model->document_number_raw = (int) $model->document_number_raw;
                $generatedDocumentNumber = DocumentNumberGenerator::regenerate(
                    static::documentNumberType(),
                    (int) $model->document_number_raw,
                    $date,
                    $suffix,
                );
            } else {
                $data = DocumentNumberGenerator::generate(
                    static::documentNumberType(),
                    $date,
                    $suffix,
                );

                $generatedDocumentNumber = $data['document_number'];
                $model->document_number_raw = $data['document_number_raw'];
            }

            if (blank($model->document_number) || ! $model->document_number_override) {
                $model->document_number = $generatedDocumentNumber;
                $model->document_number_override = false;

                return;
            }

            $model->document_number_override = $model->document_number !== $generatedDocumentNumber;
        });

        static::created(function (Model $model): void {
            if (filled($model->slug)) {
                return;
            }

            $model->forceFill([
                'slug' => static::generateSlug(
                    (int) $model->id,
                    (int) $model->document_number_raw,
                    (int) $model->issue_month,
                    (int) $model->issue_year,
                ),
            ])->saveQuietly();
        });

        static::updating(function (Model $model): void {
            static::syncDocumentNumberForUpdate($model);
        });
    }

    protected static function beforeDocumentSaving(Model $model): void
    {
    }

    protected static function syncAccessCredentialVersion(Model $model): void
    {
        if (! $model->isDirty('access_username') && ! $model->isDirty('access_password')) {
            return;
        }

        $model->access_credentials_updated_at = now();
    }

    protected static function resolveDocumentSuffixForCreate(Model $model): ?string
    {
        return null;
    }

    protected static function resolveDocumentSuffixForUpdate(Model $model): ?string
    {
        return null;
    }

    protected static function documentDateColumn(): string
    {
        return 'issue_date';
    }

    protected static function extractSuffixFromDocumentNumber(?string $documentNumber): ?string
    {
        if (blank($documentNumber)) {
            return null;
        }

        $parts = explode('/', (string) $documentNumber);
        $suffix = trim((string) end($parts));

        return $suffix !== '' ? $suffix : null;
    }

    protected static function syncIssuePeriodOnCreate(Model $model): void
    {
        $dateColumn = static::documentDateColumn();
        $dateValue = $model->{$dateColumn};

        if ($dateValue) {
            $date = Carbon::parse($dateValue);
            $model->issue_month = $date->month;
            $model->issue_year = $date->year;

            return;
        }

        $now = now();
        $model->issue_month = $now->month;
        $model->issue_year = $now->year;
    }

    protected static function resolveDocumentNumberDateForUpdate(Model $model): Carbon
    {
        if (filled($model->issue_month) && filled($model->issue_year)) {
            return Carbon::create((int) $model->issue_year, (int) $model->issue_month, 1);
        }

        $dateColumn = static::documentDateColumn();
        $dateValue = $model->{$dateColumn};

        return $dateValue ? Carbon::parse($dateValue) : now();
    }

    protected static function syncDocumentNumberForUpdate(Model $model): void
    {
        $date = static::resolveDocumentNumberDateForUpdate($model);
        $generatedDocumentNumber = DocumentNumberGenerator::regenerate(
            static::documentNumberType(),
            (int) $model->document_number_raw,
            $date,
            static::resolveDocumentSuffixForUpdate($model),
        );

        if ($model->isDirty('document_number_raw')) {
            $model->document_number_raw = (int) $model->document_number_raw;
            $model->document_number = $generatedDocumentNumber;
            $model->document_number_override = false;

            return;
        }

        if ($model->isDirty('document_number')) {
            if (blank($model->document_number)) {
                $model->document_number = $generatedDocumentNumber;
                $model->document_number_override = false;

                return;
            }

            $model->document_number_override = $model->document_number !== $generatedDocumentNumber;

            return;
        }

        if ($model->isDirty(static::documentDateColumn()) && ! $model->document_number_override) {
            $model->document_number = $generatedDocumentNumber;
        }
    }

    private static function generateSlug(
        int $id,
        int $documentNumberRaw,
        int $issueMonth,
        int $issueYear,
    ): string {
        return sprintf('%d-%d%d%d', $id, $documentNumberRaw, $issueMonth, $issueYear);
    }

    abstract protected static function documentNumberType(): string;
}
