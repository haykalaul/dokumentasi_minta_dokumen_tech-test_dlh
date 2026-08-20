<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkflowActionRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\WorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class WorkflowController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected WorkflowService $workflowService) {}

    /**
     * Submit a project to the workflow (Applicants only).
     */
    public function submit(Project $project, WorkflowActionRequest $request): JsonResponse
    {
        $this->authorize('submit', $project);

        $updatedProject = $this->workflowService->transition(
            $project,
            'submitted',
            $request->user(),
            $request->validated()['notes'] ?? 'Project submitted for review.'
        );

        return $this->jsonSuccess(
            'Project submitted successfully.',
            new ProjectResource($updatedProject->load(['user', 'documents']))
        );
    }

    /**
     * Start reviewing a project (Reviewers only).
     */
    public function review(Project $project, WorkflowActionRequest $request): JsonResponse
    {
        $this->authorize('review', $project);

        $updatedProject = $this->workflowService->transition(
            $project,
            'under_review',
            $request->user(),
            $request->validated()['notes'] ?? 'Reviewer started reviewing the project.'
        );

        return $this->jsonSuccess(
            'Project is now under review.',
            new ProjectResource($updatedProject->load(['user', 'documents']))
        );
    }

    /**
     * Request a revision (Reviewers only).
     */
    public function revision(Project $project, WorkflowActionRequest $request): JsonResponse
    {
        $this->authorize('revision', $project);

        $updatedProject = $this->workflowService->transition(
            $project,
            'revision_required',
            $request->user(),
            $request->validated()['notes']
        );

        return $this->jsonSuccess(
            'Revision requested successfully.',
            new ProjectResource($updatedProject->load(['user', 'documents']))
        );
    }

    /**
     * Approve the project (Reviewers only).
     */
    public function approve(Project $project, WorkflowActionRequest $request): JsonResponse
    {
        $this->authorize('approve', $project);

        $updatedProject = $this->workflowService->transition(
            $project,
            'approved',
            $request->user(),
            $request->validated()['notes'] ?? 'Project approved.'
        );

        return $this->jsonSuccess(
            'Project approved successfully.',
            new ProjectResource($updatedProject->load(['user', 'documents']))
        );
    }

    /**
     * Reject the project (Reviewers only).
     */
    public function reject(Project $project, WorkflowActionRequest $request): JsonResponse
    {
        $this->authorize('reject', $project);

        $updatedProject = $this->workflowService->transition(
            $project,
            'rejected',
            $request->user(),
            $request->validated()['notes']
        );

        return $this->jsonSuccess(
            'Project rejected successfully.',
            new ProjectResource($updatedProject->load(['user', 'documents']))
        );
    }
}
