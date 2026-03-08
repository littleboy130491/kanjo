<?php

namespace App\Models\Concerns;

use App\Models\ResourceLock;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasLocks
{
    public function resourceLock(): MorphOne
    {
        return $this->morphOne(ResourceLock::class, 'lockable');
    }

    public function isLocked(): bool
    {
        $lock = $this->resourceLock;

        return $lock && $lock->isActive();
    }

    public function isLockedBy(int $userId): bool
    {
        $lock = $this->resourceLock;

        return $lock && $lock->isActive() && $lock->user_id === $userId;
    }

    public function getLock(): ?ResourceLock
    {
        return ResourceLock::getFor($this);
    }

    public function acquireLock(int $userId, ?int $timeoutSeconds = null): ResourceLock
    {
        return ResourceLock::acquire($this, $userId, $timeoutSeconds);
    }

    public function forceAcquireLock(int $userId, ?int $timeoutSeconds = null): ResourceLock
    {
        return ResourceLock::forceAcquire($this, $userId, $timeoutSeconds);
    }

    public function releaseLock(): void
    {
        ResourceLock::release($this);
    }
}
