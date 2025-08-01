<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\MedicalInstitution;
use App\Models\User;

class MedicalInstitutionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('view-any MedicalInstitution');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MedicalInstitution $medicalinstitution): bool
    {
        return $user->checkPermissionTo('view MedicalInstitution');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create MedicalInstitution');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MedicalInstitution $medicalinstitution): bool
    {
        return $user->checkPermissionTo('update MedicalInstitution');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MedicalInstitution $medicalinstitution): bool
    {
        return $user->checkPermissionTo('delete MedicalInstitution');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete-any MedicalInstitution');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MedicalInstitution $medicalinstitution): bool
    {
        return $user->checkPermissionTo('restore MedicalInstitution');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->checkPermissionTo('restore-any MedicalInstitution');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, MedicalInstitution $medicalinstitution): bool
    {
        return $user->checkPermissionTo('replicate MedicalInstitution');
    }

    /**
     * Determine whether the user can reorder the models.
     */
    public function reorder(User $user): bool
    {
        return $user->checkPermissionTo('reorder MedicalInstitution');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MedicalInstitution $medicalinstitution): bool
    {
        return $user->checkPermissionTo('force-delete MedicalInstitution');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->checkPermissionTo('force-delete-any MedicalInstitution');
    }
}
