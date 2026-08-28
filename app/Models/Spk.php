<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Models\Concerns\HasDocumentModelBehavior;
use App\Models\Concerns\HasLocks;
use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Spk extends Model
{
    use HasDocumentModelBehavior;
    use HasFactory;
    use HasLocks;
    use HasTranslations;
    use LogsModelActivity;
    use SoftDeletes;

    public string $documentNumberSuffix = 'NEW';

    protected $fillable = [
        'document_number',
        'slug',
        'document_number_raw',
        'document_number_suffix',
        'document_number_override',
        'issue_month',
        'issue_year',
        'spk_date',
        'client_company',
        'client_pic_name',
        'client_pic_role',
        'client_address',
        'company_name',
        'company_pic_name',
        'company_pic_role',
        'company_address',
        'title',
        'party_identification',
        'subject',
        'content',
        'status',
        'access_username',
        'access_password',
        'access_credentials_updated_at',
        'notes',
        'proposal_id',
        'client_id',
        'company_id',
        'user_id',
        'updated_by',
    ];

    protected $translatable = [
        'title',
        'party_identification',
        'subject',
        'content',
    ];

    protected $casts = [
        'document_number_override' => 'boolean',
        'spk_date' => 'date',
        'access_credentials_updated_at' => 'datetime',
        'status' => DocumentStatus::class,
        'notes' => 'array',
    ];

    protected static function documentNumberType(): string
    {
        return 'SPK';
    }

    protected static function documentDateColumn(): string
    {
        return 'spk_date';
    }

    protected static function resolveDocumentSuffixForCreate(Model $model): ?string
    {
        return self::extractSuffixFromDocumentNumber($model->document_number)
            ?? $model->document_number_suffix
            ?? $model->documentNumberSuffix
            ?? 'NEW';
    }

    protected static function resolveDocumentSuffixForUpdate(Model $model): ?string
    {
        return self::extractSuffixFromDocumentNumber($model->document_number)
            ?? $model->document_number_suffix
            ?? 'NEW';
    }

    protected static function beforeDocumentSaving(Model $model): void
    {
        $model->document_number_suffix = self::extractSuffixFromDocumentNumber($model->document_number)
            ?? $model->document_number_suffix
            ?? 'NEW';
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<int, string>
     */
    protected function activityLogExceptAttributes(): array
    {
        return array_merge([
            'created_at',
            'updated_at',
            'deleted_at',
        ], [
            'access_password',
        ]);
    }
}
