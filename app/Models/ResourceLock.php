<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class ResourceLock extends Model
{
    protected $fillable = [
        'user_id',
        'lockable_type',
        'lockable_id',
        'lock_identifier',
        'locked_at',
        'expires_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function lockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    public static function acquire(Model $lockable, int $userId, ?int $timeoutSeconds = null): self
    {
        return self::storeLock($lockable, $userId, $timeoutSeconds, force: false);
    }

    public static function forceAcquire(Model $lockable, int $userId, ?int $timeoutSeconds = null): self
    {
        return self::storeLock($lockable, $userId, $timeoutSeconds, force: true);
    }

    protected static function storeLock(Model $lockable, int $userId, ?int $timeoutSeconds = null, bool $force = false): self
    {
        $identifier = self::getIdentifier($lockable);

        return DB::transaction(function () use ($identifier, $lockable, $userId, $timeoutSeconds, $force): self {
            $lock = self::query()
                ->where('lock_identifier', $identifier)
                ->lockForUpdate()
                ->first();

            if ($lock && $lock->isActive() && $lock->user_id !== $userId && ! $force) {
                return $lock;
            }

            if ($lock) {
                $lock->delete();
            }

            return self::create([
                'user_id' => $userId,
                'lockable_type' => get_class($lockable),
                'lockable_id' => $lockable->getKey(),
                'lock_identifier' => $identifier,
                'locked_at' => now(),
                'expires_at' => $timeoutSeconds ? now()->addSeconds($timeoutSeconds) : null,
            ]);
        });
    }

    public static function release(Model $lockable): void
    {
        self::where('lock_identifier', self::getIdentifier($lockable))->delete();
    }

    public static function getFor(Model $lockable): ?self
    {
        return self::where('lock_identifier', self::getIdentifier($lockable))->first();
    }

    public static function getIdentifier(Model $lockable): string
    {
        return get_class($lockable).':'.$lockable->getKey();
    }
}
