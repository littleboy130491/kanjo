<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Portfolio;
use Illuminate\Auth\Access\HandlesAuthorization;

class PortfolioPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Portfolio');
    }

    public function view(AuthUser $authUser, Portfolio $portfolio): bool
    {
        return $authUser->can('View:Portfolio');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Portfolio');
    }

    public function update(AuthUser $authUser, Portfolio $portfolio): bool
    {
        return $authUser->can('Update:Portfolio');
    }

    public function delete(AuthUser $authUser, Portfolio $portfolio): bool
    {
        return $authUser->can('Delete:Portfolio');
    }

    public function restore(AuthUser $authUser, Portfolio $portfolio): bool
    {
        return $authUser->can('Restore:Portfolio');
    }

    public function forceDelete(AuthUser $authUser, Portfolio $portfolio): bool
    {
        return $authUser->can('ForceDelete:Portfolio');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Portfolio');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Portfolio');
    }

    public function replicate(AuthUser $authUser, Portfolio $portfolio): bool
    {
        return $authUser->can('Replicate:Portfolio');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Portfolio');
    }

}