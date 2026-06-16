<?php

declare(strict_types=1);

namespace App\Policies\Accounting;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Accounting\TransferBetweenAccount;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransferBetweenAccountPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransferBetweenAccount');
    }

    public function view(AuthUser $authUser, TransferBetweenAccount $transferBetweenAccount): bool
    {
        return $authUser->can('View:TransferBetweenAccount');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransferBetweenAccount');
    }

    public function update(AuthUser $authUser, TransferBetweenAccount $transferBetweenAccount): bool
    {
        return $authUser->can('Update:TransferBetweenAccount');
    }

    public function delete(AuthUser $authUser, TransferBetweenAccount $transferBetweenAccount): bool
    {
        return $authUser->can('Delete:TransferBetweenAccount');
    }

    public function restore(AuthUser $authUser, TransferBetweenAccount $transferBetweenAccount): bool
    {
        return $authUser->can('Restore:TransferBetweenAccount');
    }

    public function forceDelete(AuthUser $authUser, TransferBetweenAccount $transferBetweenAccount): bool
    {
        return $authUser->can('ForceDelete:TransferBetweenAccount');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransferBetweenAccount');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransferBetweenAccount');
    }

    public function replicate(AuthUser $authUser, TransferBetweenAccount $transferBetweenAccount): bool
    {
        return $authUser->can('Replicate:TransferBetweenAccount');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransferBetweenAccount');
    }

    public function viewFinancials(AuthUser $authUser, TransferBetweenAccount $transferBetweenAccount): bool
    {
        return $authUser->can('ViewFinancials:TransferBetweenAccount');
    }

}