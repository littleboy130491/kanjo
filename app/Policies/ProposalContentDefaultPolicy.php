<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProposalContentDefault;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProposalContentDefaultPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProposalContentDefault');
    }

    public function view(AuthUser $authUser, ProposalContentDefault $proposalContentDefault): bool
    {
        return $authUser->can('View:ProposalContentDefault');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProposalContentDefault');
    }

    public function update(AuthUser $authUser, ProposalContentDefault $proposalContentDefault): bool
    {
        return $authUser->can('Update:ProposalContentDefault');
    }

    public function delete(AuthUser $authUser, ProposalContentDefault $proposalContentDefault): bool
    {
        return $authUser->can('Delete:ProposalContentDefault');
    }

    public function restore(AuthUser $authUser, ProposalContentDefault $proposalContentDefault): bool
    {
        return $authUser->can('Restore:ProposalContentDefault');
    }

    public function forceDelete(AuthUser $authUser, ProposalContentDefault $proposalContentDefault): bool
    {
        return $authUser->can('ForceDelete:ProposalContentDefault');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProposalContentDefault');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProposalContentDefault');
    }

    public function replicate(AuthUser $authUser, ProposalContentDefault $proposalContentDefault): bool
    {
        return $authUser->can('Replicate:ProposalContentDefault');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProposalContentDefault');
    }
}
