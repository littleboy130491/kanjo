<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Schemas;

use App\Filament\Admin\Resources\Clients\ClientResource;
use App\Filament\Admin\Resources\Companies\CompanyResource;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Filament\Admin\Resources\Portfolios\PortfolioResource;
use App\Filament\Admin\Resources\ProposalContentDefaults\ProposalContentDefaultResource;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Portfolio;
use App\Models\Proposal;
use App\Models\ProposalContentDefault;
use App\Models\Service;
use App\Models\User;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Activitylog\Models\Activity;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity Details')
                    ->schema([
                        TextEntry::make('causer_id')
                            ->label('User')
                            ->formatStateUsing(fn (mixed $state, Activity $record): string => $record->causer?->name ?? '-'),
                        TextEntry::make('event')
                            ->label('Activity')
                            ->badge()
                            ->default('-'),
                        TextEntry::make('associated_model')
                            ->label('Model Associated')
                            ->state(fn (Activity $record): string => self::resolveAssociatedModel($record))
                            ->copyable(),
                        TextEntry::make('associated_record')
                            ->label('Record Associated')
                            ->state(fn (Activity $record): string => self::resolveAssociatedRecord($record))
                            ->url(fn (Activity $record): ?string => self::resolveSubjectEditUrl($record))
                            ->openUrlInNewTab()
                            ->copyable(),
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('ip_address')
                            ->label('IP')
                            ->default('-')
                            ->copyable(),
                        TextEntry::make('device')
                            ->label('Device')
                            ->default('-')
                            ->columnSpanFull()
                            ->copyable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Change Payload')
                    ->schema([
                        KeyValueEntry::make('attributes')
                            ->label('Current Values')
                            ->state(fn (Activity $record): array => self::normalizeChangeSet(
                                $record->changes()->get('attributes', [])
                            ))
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                        KeyValueEntry::make('old')
                            ->label('Previous Values')
                            ->state(fn (Activity $record): array => self::normalizeChangeSet(
                                $record->changes()->get('old', [])
                            ))
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function resolveAssociatedModel(Activity $record): string
    {
        if (filled($record->subject_type)) {
            return class_basename($record->subject_type);
        }

        $documentType = $record->properties->get('document_type');

        if (filled($documentType)) {
            return ucfirst((string) $documentType);
        }

        return '-';
    }

    public static function resolveAssociatedRecord(Activity $record): string
    {
        if (filled($record->subject_id)) {
            $label = self::resolveSubjectLabel($record);

            return filled($label)
                ? $record->subject_id . ' - ' . $label
                : (string) $record->subject_id;
        }

        $slug = $record->properties->get('slug');

        if (filled($slug)) {
            return (string) $slug;
        }

        return '-';
    }

    public static function resolveSubjectEditUrl(Activity $record): ?string
    {
        $resource = self::resolveSubjectResourceClass($record->subject_type);

        if ($resource === null || $record->subject === null) {
            return null;
        }

        return $resource::getUrl('edit', ['record' => $record->subject]);
    }

    protected static function resolveSubjectLabel(Activity $record): ?string
    {
        $subject = $record->subject;

        if ($subject === null) {
            return null;
        }

        foreach (['document_number', 'company_name', 'brand_name', 'name', 'title', 'email'] as $attribute) {
            $value = data_get($subject, $attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }
    protected static function resolveSubjectResourceClass(?string $subjectType): ?string
    {
        return match ($subjectType) {
            Client::class => ClientResource::class,
            Company::class => CompanyResource::class,
            Invoice::class => InvoiceResource::class,
            Portfolio::class => PortfolioResource::class,
            Proposal::class => ProposalResource::class,
            ProposalContentDefault::class => ProposalContentDefaultResource::class,
            Service::class => ServiceResource::class,
            User::class => UserResource::class,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, string>
     */
    protected static function normalizeChangeSet(array $changes): array
    {
        return collect($changes)
            ->mapWithKeys(fn (mixed $value, string $key): array => [
                $key => self::formatChangeValue($value),
            ])
            ->all();
    }

    protected static function formatChangeValue(mixed $value): string
    {
        if (is_string($value) && self::looksLikeJson($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode(
                    $decoded,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ) ?: $value;
            }
        }

        if (is_array($value)) {
            return json_encode(
                $value,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ) ?: '[]';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        return (string) $value;
    }

    protected static function looksLikeJson(string $value): bool
    {
        $trimmed = trim($value);

        return str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[');
    }
}
