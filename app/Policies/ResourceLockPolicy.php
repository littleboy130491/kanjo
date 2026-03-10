<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ResourceLock;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResourceLockPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ResourceLock');
    }

    public function view(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('View:ResourceLock');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ResourceLock');
    }

    public function update(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('Update:ResourceLock');
    }

    public function delete(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('Delete:ResourceLock');
    }

    public function restore(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('Restore:ResourceLock');
    }

    public function forceDelete(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('ForceDelete:ResourceLock');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ResourceLock');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ResourceLock');
    }

    public function replicate(AuthUser $authUser, ResourceLock $resourceLock): bool
    {
        return $authUser->can('Replicate:ResourceLock');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ResourceLock');
    }

}