<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true; // We filter the list in the controller based on role
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole('reviewer')) {
            return true;
        }

        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('applicant');
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        if (! $user->hasRole('applicant')) {
            return false;
        }

        // Applicant can only update if they own the project and it is draft/revision_required
        return $user->id === $project->user_id && in_array($project->status, ['draft', 'revision_required']);
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        if (! $user->hasRole('applicant')) {
            return false;
        }

        // Applicant can only delete if they own it and status is draft
        return $user->id === $project->user_id && $project->status === 'draft';
    }

    /**
     * Determine whether the user can submit the project.
     */
    public function submit(User $user, Project $project): bool
    {
        if (! $user->hasRole('applicant')) {
            return false;
        }

        return $user->id === $project->user_id && in_array($project->status, ['draft', 'revision_required']);
    }

    /**
     * Determine whether the user can download documents of the project.
     */
    public function downloadDocument(User $user, Project $project): bool
    {
        if ($user->hasRole('reviewer')) {
            return true;
        }

        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can start reviewing the project.
     */
    public function review(User $user, Project $project): bool
    {
        return $user->hasRole('reviewer') && $project->status === 'submitted';
    }

    /**
     * Determine whether the user can request revision.
     */
    public function revision(User $user, Project $project): bool
    {
        return $user->hasRole('reviewer') && $project->status === 'under_review';
    }

    /**
     * Determine whether the user can approve the project.
     */
    public function approve(User $user, Project $project): bool
    {
        return $user->hasRole('reviewer') && $project->status === 'under_review';
    }

    /**
     * Determine whether the user can reject the project.
     */
    public function reject(User $user, Project $project): bool
    {
        return $user->hasRole('reviewer') && $project->status === 'under_review';
    }

    /**
     * Determine whether the user can export projects.
     */
    public function export(User $user): bool
    {
        return $user->hasRole('reviewer');
    }
}
