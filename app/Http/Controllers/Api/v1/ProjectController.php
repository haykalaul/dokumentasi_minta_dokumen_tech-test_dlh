<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectCreateRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Document;
use App\Models\Project;
use App\Services\WorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected WorkflowService $workflowService) {}

    /**
     * Display a listing of projects.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::query()->with('user');

        if ($request->user()->hasRole('reviewer')) {
            // Reviewers see all projects in workflow (not drafts)
            $query->where('status', '!=', 'draft');
        } else {
            // Applicants only see their own projects
            $query->where('user_id', $request->user()->id);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->has('search')) {
            $query->where('title', 'like', '%'.$request->query('search').'%');
        }

        // Order and paginate
        $projects = $query->orderBy('created_at', 'desc')
            ->cursorPaginate(15);

        return $this->jsonSuccess('Projects retrieved successfully.', $projects);
    }

    /**
     * Store a newly created project application.
     */
    public function store(ProjectCreateRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->workflowService->createProject(
            $request->user(),
            $request->validated(),
            $request->file('document')
        );

        return $this->jsonSuccess(
            'Project created successfully.',
            new ProjectResource($project->load(['user', 'documents'])),
            201
        );
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load(['user', 'documents', 'approvalLogs.user']);

        return $this->jsonSuccess('Project retrieved successfully.', new ProjectResource($project));
    }

    /**
     * Display the approval logs/history for the specified project.
     */
    public function history(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $logs = $project->approvalLogs()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->jsonSuccess('Project history retrieved successfully.', ApprovalLogResource::collection($logs));
    }

    /**
     * Update the specified project in storage.
     */
    public function update(ProjectUpdateRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $updatedProject = $this->workflowService->updateProject(
            $project,
            $request->validated(),
            $request->file('document')
        );

        return $this->jsonSuccess(
            'Project updated successfully.',
            new ProjectResource($updatedProject->load(['user', 'documents']))
        );
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->workflowService->deleteProject($project);

        return $this->jsonSuccess('Project deleted successfully.');
    }

    /**
     * Download the specified document file securely.
     */
    public function downloadDocument(Project $project, Document $document): StreamedResponse
    {
        $this->authorize('downloadDocument', $project);

        if ($document->project_id !== $project->id) {
            abort(404, 'Document not found in this project.');
        }

        if (! Storage::exists($document->file_path)) {
            abort(404, 'File not found in storage.');
        }

        return Storage::download($document->file_path, $document->original_name);
    }
}
