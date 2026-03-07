<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsModelActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->useLogName('activity')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logExcept($this->activityLogExceptAttributes())
            ->setDescriptionForEvent(fn (string $eventName): string => $eventName);

        if ($this->shouldUseSimpleActivityLogging()) {
            return $options->logOnly($this->activityLogSimpleAttributes());
        }

        $options->logAll();

        if ($this->shouldUseRawActivityLogging()) {
            $options->useAttributeRawValues($this->activityLogRawAttributes());
        }

        return $options;
    }

    public function tapActivity(ActivityContract $activity, string $eventName): void
    {
        $activity->description = $eventName;
        $activity->ip_address = request()->ip();
        $activity->device = request()->userAgent();
    }

    /**
     * @return array<int, string>
     */
    protected function activityLogExceptAttributes(): array
    {
        return [
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function activityLogRawAttributes(): array
    {
        if (! method_exists($this, 'getTranslatableAttributes')) {
            return [];
        }

        /** @var array<int, string> $attributes */
        $attributes = $this->getTranslatableAttributes();

        return $attributes;
    }

    protected function activityLogLevel(): string
    {
        return 'detailed';
    }

    protected function shouldUseRawActivityLogging(): bool
    {
        return $this->activityLogLevel() === 'detailed';
    }

    protected function shouldUseSimpleActivityLogging(): bool
    {
        return $this->activityLogLevel() === 'simple';
    }

    /**
     * @return array<int, string>
     */
    protected function activityLogSimpleAttributes(): array
    {
        return [];
    }
}
