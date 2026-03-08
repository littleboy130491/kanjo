<?php

namespace App\Filament\Admin\Resources\Concerns;

use App\Models\ResourceLock;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

trait UsesResourceLock
{
    use UsesPolling;

    protected int $lockTimeoutSeconds = 300;

    protected bool $lockKeepAlive = true;

    protected int $lockPollingInterval = 15;

    public bool $isResourceLocked = false;

    public ?string $resourceLockedBy = null;

    public function mountUsesResourceLock(): void
    {
        $this->acquireResourceLock();
    }

    protected function acquireResourceLock(): void
    {
        if (! $this->record) {
            return;
        }

        $lock = $this->record->getLock();

        if ($lock && $lock->isActive() && $lock->user_id !== auth()->id()) {
            $this->isResourceLocked = true;
            $this->resourceLockedBy = $lock->user?->name ?? 'Another user';
            $this->showLockedNotification($lock);

            return;
        }

        $this->isResourceLocked = false;
        $this->resourceLockedBy = null;
        $this->record->acquireLock(auth()->id(), $this->lockTimeoutSeconds);
    }

    protected function ensureResourceLockOwnership(): bool
    {
        if (! $this->record) {
            return true;
        }

        $lock = $this->record->getLock();

        if ($lock && $lock->isActive() && $lock->user_id !== auth()->id()) {
            $this->isResourceLocked = true;
            $this->resourceLockedBy = $lock->user?->name ?? 'Another user';
            $this->showLockedNotification($lock);

            return false;
        }

        $this->isResourceLocked = false;
        $this->resourceLockedBy = null;
        $this->record->acquireLock(auth()->id(), $this->lockTimeoutSeconds);

        return true;
    }

    protected function releaseResourceLock(): void
    {
        if (! $this->record) {
            return;
        }

        $lock = $this->record->getLock();

        if ($lock && $lock->user_id === auth()->id()) {
            $this->record->releaseLock();
        }
    }

    protected function showLockedNotification(ResourceLock $lock): void
    {
        $locker = $lock->user?->name ?? 'Another user';

        Notification::make()
            ->title('Resource Locked')
            ->body("This resource is currently being edited by {$locker}.")
            ->danger()
            ->persistent()
            ->send();
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendToChildComponent = true): void
    {
        if (! $this->ensureResourceLockOwnership()) {
            return;
        }

        parent::save($shouldRedirect, $shouldSendToChildComponent);

        $this->releaseResourceLock();
    }

    public function cancel(): void
    {
        $this->releaseResourceLock();

        parent::cancel();
    }

    public function getLockPollingInterval(): int
    {
        return $this->lockPollingInterval;
    }

    public function isLockKeepAliveEnabled(): bool
    {
        return $this->lockKeepAlive;
    }

    #[On('refresh-resource-lock')]
    public function refreshResourceLock(): void
    {
        if (! $this->record) {
            return;
        }

        $lock = $this->record->getLock();

        if (! $lock || ! $lock->isActive() || $lock->user_id === auth()->id()) {
            $this->isResourceLocked = false;
            $this->resourceLockedBy = null;
            $this->record->acquireLock(auth()->id(), $this->lockTimeoutSeconds);

            return;
        }

        $this->isResourceLocked = true;
        $this->resourceLockedBy = $lock->user?->name ?? 'Another user';

        Notification::make()
            ->title('Lock Taken Over')
            ->body("{$this->resourceLockedBy} took over this resource. You have been returned to the list.")
            ->danger()
            ->send();

        $this->redirect($this->resourceLockReturnUrl(), navigate: true);
    }

    public function resourceLockReturnUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function startResourceLockPolling(): void
    {
        if (! $this->record || $this->isResourceLocked || ! $this->isLockKeepAliveEnabled()) {
            return;
        }

        $this->poll(
            static::class . '.resource-lock.' . $this->record->getKey(),
            "\$wire.dispatch('refresh-resource-lock')",
            $this->getLockPollingInterval(),
        );
    }

    protected function getLockActions(): array
    {
        return [
            Action::make('unlock_and_edit')
                ->label('Take Over')
                ->icon('heroicon-o-lock-open')
                ->color('warning')
                ->visible(fn () => $this->isResourceLocked)
                ->requiresConfirmation()
                ->modalHeading('Take Over Lock')
                ->modalDescription('This will remove the current lock and allow you to edit. The other user may lose their changes.')
                ->action(function () {
                    $this->record->forceAcquireLock(auth()->id(), $this->lockTimeoutSeconds);
                    $this->isResourceLocked = false;
                    $this->resourceLockedBy = null;
                    $this->startResourceLockPolling();
                    Notification::make()
                        ->title('Lock Taken Over')
                        ->body('You now have exclusive access to edit this resource.')
                        ->success()
                        ->send();
                }),
            Action::make('return_to_list')
                ->label('Return')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->visible(fn () => $this->isResourceLocked)
                ->url($this->resourceLockReturnUrl()),
        ];
    }

    protected function mergeLockActions(array $actions): array
    {
        return array_merge($this->getLockActions(), $actions);
    }

    public function bootedUsesResourceLock(): void
    {
        $this->startResourceLockPolling();
    }

}
