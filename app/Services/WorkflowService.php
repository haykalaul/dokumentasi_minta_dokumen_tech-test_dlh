<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApprovalLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkflowService
{
    /**
     * Map of valid state transitions: [initial_state => [allowed_target_states]]
     */
    protected array $transitions = [
        'draft' => ['submitted'],
        'submitted' => ['under_review'],
        'under_review' => ['revision_required', 'approved', 'rejected'],
        'revision_required' => ['submitted'],
    ];

    public function __construct(protected DocumentService $documentService) {}

    /**
     * Create a new project in draft state with an uploaded document.
     */
    public function createProject(User $user, array $data, UploadedFile $file): Project
    {
        return DB::transaction(function () use ($user, $data, $file) {
            // 1. Create Project in draft status
            $project = Project::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 'draft',
            ]);

            // 2. Upload and attach initial document
            $this->documentService->upload($file, $project);

            // 3. Log initial creation action
            ApprovalLog::create([
                'project_id' => $project->id,
                'user_id' => $user->id,
                'action' => 'CREATE',
                'old_status' => null,
                'new_status' => 'draft',
                'notes' => 'Project created as draft.',
            ]);

            // Clear user dashboard cache
            $this->clearDashboardCache($user->id);

            return $project;
        });
    }

    /**
     * Update project draft or revision details, optionally updating the document.
     */
    public function updateProject(Project $project, array $data, ?UploadedFile $file): Project
    {
        if (! in_array($project->status, ['draft', 'revision_required'])) {
            throw new InvalidArgumentException('Project hanya dapat diperbaiki dalam status draft atau butuh revisi.');
        }

        return DB::transaction(function () use ($project, $data, $file) {
            // 1. Update Project fields
            $project->update([
                'title' => $data['title'],
                'description' => $data['description'],
            ]);

            // 2. If new document is uploaded, replace the old one
            if ($file) {
                // Delete existing documents
                foreach ($project->documents as $doc) {
                    $this->documentService->delete($doc);
                }
                // Upload new document
                $this->documentService->upload($file, $project);
            }

            // 3. Log the update action
            ApprovalLog::create([
                'project_id' => $project->id,
                'user_id' => $project->user_id,
                'action' => 'UPDATE',
                'old_status' => $project->status,
                'new_status' => $project->status,
                'notes' => 'Project details updated.',
            ]);

            return $project;
        });
    }

    /**
     * Transition project to a new status in the workflow.
     */
    public function transition(Project $project, string $targetStatus, User $actor, ?string $notes = null): Project
    {
        $currentStatus = $project->status;

        // 1. Check if state transition is registered as valid
        if (! isset($this->transitions[$currentStatus]) || ! in_array($targetStatus, $this->transitions[$currentStatus])) {
            throw new InvalidArgumentException("Transisi status dari {$currentStatus} ke {$targetStatus} tidak valid.");
        }

        return DB::transaction(function () use ($project, $currentStatus, $targetStatus, $actor, $notes) {
            // 2. Update status
            $project->update(['status' => $targetStatus]);

            // 3. Determine action type name
            $action = match ($targetStatus) {
                'submitted' => 'SUBMIT',
                'under_review' => 'START_REVIEW',
                'revision_required' => 'REVISE',
                'approved' => 'APPROVE',
                'rejected' => 'REJECT',
                default => 'TRANSITION',
            };

            // 4. Log state change
            ApprovalLog::create([
                'project_id' => $project->id,
                'user_id' => $actor->id,
                'action' => $action,
                'old_status' => $currentStatus,
                'new_status' => $targetStatus,
                'notes' => $notes ?? "Project status transitioned to {$targetStatus}.",
            ]);

            // 5. Clear metrics caches
            $this->clearDashboardCache($project->user_id);
            $this->clearReviewerDashboardCache();

            return $project;
        });
    }

    /**
     * Delete a project (only allowed in draft state).
     */
    public function deleteProject(Project $project): void
    {
        if ($project->status !== 'draft') {
            throw new InvalidArgumentException('Hanya project berstatus draft yang dapat dihapus.');
        }

        DB::transaction(function () use ($project) {
            // Delete documents physically
            foreach ($project->documents as $doc) {
                $this->documentService->delete($doc);
            }

            // Database cascade deletes will remove documents and approval logs from db,
            // but we call delete explicitly just in case.
            $project->delete();

            $this->clearDashboardCache($project->user_id);
        });
    }

    /**
     * Clear dashboard statistics cache for an applicant.
     */
    protected function clearDashboardCache(string $userId): void
    {
        Cache::forget("dashboard_applicant_{$userId}");
    }

    /**
     * Clear reviewer dashboard statistics cache.
     */
    protected function clearReviewerDashboardCache(): void
    {
        Cache::forget('dashboard_reviewer_metrics');
    }
}
