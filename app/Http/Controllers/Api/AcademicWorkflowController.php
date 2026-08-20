<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\Academic\AcademicReadinessScorer;
use App\Services\Academic\AcademicWorkflowEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Admin\Services\PermissionRegistry;

class AcademicWorkflowController extends Controller
{
    protected function resolveSchoolId(Request $request): int
    {
        $user = $request->user();

        $schoolId = $user->school_id;

        if ($user->school_id === null && $request->has('school_id')) {
            $schoolId = (int) $request->query('school_id');
        }

        if (! $schoolId) {
            abort(422, 'No school context is bound to the authenticated user.');
        }

        return $schoolId;
    }

    protected function bindTenant(int $schoolId): void
    {
        if (! App::has('current_tenant')) {
            App::instance('current_tenant', School::find($schoolId));
        }
    }

    protected function engine(Request $request): AcademicWorkflowEngine
    {
        $schoolId = $this->resolveSchoolId($request);
        $this->bindTenant($schoolId);

        return new AcademicWorkflowEngine($schoolId);
    }

    protected function scorer(Request $request): AcademicReadinessScorer
    {
        $schoolId = $this->resolveSchoolId($request);
        $this->bindTenant($schoolId);

        return new AcademicReadinessScorer($schoolId);
    }

    public function workflow(Request $request): JsonResponse
    {
        $engine = $this->engine($request);

        return response()->json([
            'data' => [
                'school_id' => $this->resolveSchoolId($request),
                'progress' => $engine->getWorkflowProgress(),
                'steps' => $engine->getWorkflowSteps(),
                'blocked_steps' => $engine->getBlockedSteps(),
                'ready_next_steps' => $engine->getReadyNextSteps(),
            ],
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        $engine = $this->engine($request);

        return response()->json([
            'data' => array_merge(
                $engine->getWorkflowProgress(),
                [
                    'blocked_steps' => $engine->getBlockedSteps(),
                    'ready_next_steps' => $engine->getReadyNextSteps(),
                ]
            ),
        ]);
    }

    public function steps(Request $request): JsonResponse
    {
        $engine = $this->engine($request);
        $progress = $engine->getWorkflowProgress();

        $steps = $engine->getWorkflowSteps()->map(function ($step, $key) use ($engine, $progress) {
            return array_merge($step, [
                'key' => $key,
                'status' => $progress['status_by_step'][$key] ?? 'pending',
                'is_blocked' => in_array($key, $engine->getBlockedSteps()),
                'depends_on' => $step['depends_on'] ?? null,
                'route' => $engine->getStepRoute($key),
            ]);
        })->values();

        return response()->json(['data' => $steps]);
    }

    public function step(Request $request, string $step): JsonResponse
    {
        $engine = $this->engine($request);

        $config = $engine->getStep($step);
        if (! $config) {
            return response()->json(['message' => 'Unknown workflow step: '.$step], 404);
        }

        $progress = $engine->getWorkflowProgress();

        return response()->json([
            'data' => array_merge($config, [
                'key' => $step,
                'status' => $progress['status_by_step'][$step] ?? 'pending',
                'completion' => $engine->getStepCompletionStatus($step),
                'is_blocked' => in_array($step, $engine->getBlockedSteps()),
                'depends_on' => $config['depends_on'] ?? null,
                'route' => $engine->getStepRoute($step),
            ]),
        ]);
    }

    public function complete(Request $request, string $step): JsonResponse
    {
        return $this->setStepStatus($request, $step, 'completed');
    }

    public function skip(Request $request, string $step): JsonResponse
    {
        return $this->setStepStatus($request, $step, 'skipped');
    }

    public function reset(Request $request, string $step): JsonResponse
    {
        if (! PermissionRegistry::checkPermission('academic_ops.manage_workflow')) {
            abort(403, 'You do not have permission to override workflow steps.');
        }

        $engine = $this->engine($request);
        if (! $engine->getStep($step)) {
            return response()->json(['message' => 'Unknown workflow step: '.$step], 404);
        }

        $engine->resetStepStatus($step);

        return response()->json([
            'message' => 'Workflow step reset.',
            'data' => [
                'key' => $step,
                'status' => 'pending',
            ],
        ]);
    }

    protected function setStepStatus(Request $request, string $step, string $status): JsonResponse
    {
        if (! PermissionRegistry::checkPermission('academic_ops.manage_workflow')) {
            abort(403, 'You do not have permission to override workflow steps.');
        }

        $engine = $this->engine($request);
        if (! $engine->getStep($step)) {
            return response()->json(['message' => 'Unknown workflow step: '.$step], 404);
        }

        if (in_array($step, $engine->getBlockedSteps())) {
            return response()->json([
                'message' => 'Step cannot be updated while it is blocked by an unsatisfied dependency.',
                'data' => [
                    'key' => $step,
                    'depends_on' => $engine->getStep($step)['depends_on'] ?? null,
                ],
            ], 422);
        }

        $ok = $engine->setStepStatus($step, $status);
        if (! $ok) {
            return response()->json(['message' => 'Step cannot be marked '.$status.' until its dependency is satisfied.'], 422);
        }

        return response()->json([
            'message' => 'Workflow step updated.',
            'data' => [
                'key' => $step,
                'status' => $status,
            ],
        ]);
    }

    public function readiness(Request $request): JsonResponse
    {
        $scorer = $this->scorer($request);

        return response()->json([
            'data' => $scorer->calculateReadinessScore(),
        ]);
    }

    public function timeline(Request $request): JsonResponse
    {
        $scorer = $this->scorer($request);

        return response()->json([
            'data' => $scorer->getWorkflowTimeline(),
        ]);
    }

    public function kpis(Request $request): JsonResponse
    {
        $scorer = $this->scorer($request);

        return response()->json([
            'data' => $scorer->getAcademicKPIs(),
        ]);
    }
}
