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
        return LogOptions::defaults()
            ->useLogName('activity')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logExcept($this->activityLogExceptAttributes())
            ->setDescriptionForEvent(fn (string $eventName): string => $eventName);
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
}
