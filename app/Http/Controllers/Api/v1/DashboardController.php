<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApprovalLogResource;
use App\Http\Resources\ProjectResource;
use App\Models\ApprovalLog;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard metrics and recent items based on user role.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('reviewer')) {
            return $this->getReviewerDashboard();
        }

        return $this->getApplicantDashboard($user->id);
    }

    /**
     * Compile and cache metrics for applicant role.
     */
    protected function getApplicantDashboard(string $userId): JsonResponse
    {
        // 1. Retrieve aggregated metrics from cache (or compile it)
        $metrics = Cache::remember("dashboard_applicant_{$userId}", 300, function () use ($userId) {
            $data = Project::where('user_id', $userId)
                ->selectRaw("
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
                    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted,
                    COUNT(CASE WHEN status = 'revision_required' THEN 1 END) as revision,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
                ")
                ->first();

            return [
                'total' => (int) ($data->total ?? 0),
                'draft' => (int) ($data->draft ?? 0),
                'submitted' => (int) ($data->submitted ?? 0),
                'revision' => (int) ($data->revision ?? 0),
                'approved' => (int) ($data->approved ?? 0),
                'rejected' => (int) ($data->rejected ?? 0),
            ];
        });

        // 2. Load recent projects in real-time (no cache for chronological lists)
        $recentProjects = Project::where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $this->jsonSuccess('Applicant dashboard loaded.', [
            'metrics' => $metrics,
            'recent_projects' => ProjectResource::collection($recentProjects),
        ]);
    }

    /**
     * Compile and cache metrics for reviewer role.
     */
    protected function getReviewerDashboard(): JsonResponse
    {
        // 1. Retrieve aggregated metrics and monthly chart from cache
        $cacheData = Cache::remember('dashboard_reviewer_metrics', 300, function () {
            // Aggregate totals in 1 query
            $data = Project::selectRaw("
                COUNT(CASE WHEN status != 'draft' THEN 1 END) as total,
                COUNT(CASE WHEN status IN ('submitted', 'under_review') THEN 1 END) as pending_review,
                COUNT(CASE WHEN status = 'revision_required' THEN 1 END) as revision,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
            ")
                ->first();

            // Aggregate monthly submissions for chart (last 6 months)
            $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

            $driver = DB::connection()->getDriverName();
            $dateGroup = $driver === 'sqlite'
                ? "strftime('%Y-%m', created_at)"
                : "TO_CHAR(created_at, 'YYYY-MM')";

            $rawChart = Project::where('status', '!=', 'draft')
                ->where('created_at', '>=', $sixMonthsAgo)
                ->selectRaw("{$dateGroup} as month, COUNT(*) as count")
                ->groupBy('month')
                ->orderBy('month', 'asc')
                ->get();

            // Populate monthly data structure
            $chart = [];
            for ($i = 5; $i >= 0; $i--) {
                $monthKey = now()->subMonths($i)->format('Y-m');
                $chartName = now()->subMonths($i)->format('M Y');
                $match = $rawChart->firstWhere('month', $monthKey);
                $chart[] = [
                    'label' => $chartName,
                    'count' => $match ? (int) $match->count : 0,
                ];
            }

            return [
                'metrics' => [
                    'total' => (int) ($data->total ?? 0),
                    'pending_review' => (int) ($data->pending_review ?? 0),
                    'revision' => (int) ($data->revision ?? 0),
                    'approved' => (int) ($data->approved ?? 0),
                    'rejected' => (int) ($data->rejected ?? 0),
                ],
                'chart' => $chart,
            ];
        });

        // 2. Load recent reviewer activities in real-time
        $recentActivities = ApprovalLog::with(['project', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $this->jsonSuccess('Reviewer dashboard loaded.', [
            'metrics' => $cacheData['metrics'],
            'chart' => $cacheData['chart'],
            'recent_activities' => ApprovalLogResource::collection($recentActivities),
        ]);
    }
}
