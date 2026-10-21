<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CompleteTaskAction;
use App\Actions\CreateTaskAction;
use App\Actions\DeleteTaskAction;
use App\Actions\UpdateTaskAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskCompletionRequest;
use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\CrmTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(TaskFilterRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $query = CrmTask::where('user_id', $request->user()->id);
        if (! empty($data['priority'])) {
            $query->where('priority', $data['priority']);
        }
        switch ($data['filter'] ?? 'all') {
            case 'today':
                $query->whereNull('completed_at')->where('due_at', '>=', today('UTC'))->where('due_at', '<', today('UTC')->addDay());
                break;
            case 'overdue':
                $query->whereNull('completed_at')->where('due_at', '<', now());
                break;
            case 'open':
                $query->whereNull('completed_at');
                break;
            case 'completed':
                $query->whereNotNull('completed_at');
                break;
        }

        return TaskResource::collection($query->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')->orderBy('id')->paginate(20)->withQueryString());
    }

    public function store(TaskRequest $request, CreateTaskAction $action): JsonResponse
    {
        $task = $action($request->user(), $request->validated());

        return (new TaskResource($task))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $task): TaskResource
    {
        return new TaskResource($this->owned($request, $task));
    }

    public function update(TaskRequest $request, int $task, UpdateTaskAction $action): TaskResource
    {
        return new TaskResource($action($request->user(), $this->owned($request, $task), $request->validated()));
    }

    public function complete(TaskCompletionRequest $request, int $task, CompleteTaskAction $action): TaskResource
    {
        $data = $request->validated();

        return new TaskResource($action($request->user(), $this->owned($request, $task), $data['completed']));
    }

    public function destroy(Request $request, int $task, DeleteTaskAction $action): JsonResponse
    {
        $action($request->user(), $this->owned($request, $task));

        return response()->json(status: 204);
    }

    private function owned(Request $request, int $task): CrmTask
    {
        $record = CrmTask::where('user_id', $request->user()->id)->findOrFail($task);
        Gate::authorize('view', $record);

        return $record;
    }
}
