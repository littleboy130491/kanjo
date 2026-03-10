<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Proposal;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProposalPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Proposal');
    }

    public function view(AuthUser $authUser, Proposal $proposal): bool
    {
        return $authUser->can('View:Proposal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Proposal');
    }

    public function update(AuthUser $authUser, Proposal $proposal): bool
    {
        return $authUser->can('Update:Proposal');
    }

    public function delete(AuthUser $authUser, Proposal $proposal): bool
    {
        return $authUser->can('Delete:Proposal');
    }

    public function restore(AuthUser $authUser, Proposal $proposal): bool
    {
        return $authUser->can('Restore:Proposal');
    }

    public function forceDelete(AuthUser $authUser, Proposal $proposal): bool
    {
        return $authUser->can('ForceDelete:Proposal');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Proposal');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Proposal');
    }

    public function replicate(AuthUser $authUser, Proposal $proposal): bool
    {
        return $authUser->can('Replicate:Proposal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Proposal');
    }

}