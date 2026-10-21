<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ManageTask;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\CrmTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'filter' => ['nullable', Rule::in(['all', 'today', 'overdue', 'open', 'completed'])],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
        ]);
        $query = CrmTask::where('user_id', $request->user()->id);
        if (! empty($data['priority'])) {
            $query->where('priority', $data['priority']);
        }
        switch ($data['filter'] ?? 'all') {
            case 'today':
                $query->whereNull('completed_at')->whereDate('due_at', today('UTC'));
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
            ->orderBy('due_at')->paginate(20)->withQueryString());
    }

    public function store(TaskRequest $request, ManageTask $action): JsonResponse
    {
        $task = $action->create($request->user(), $request->validated());

        return (new TaskResource($task))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $task): TaskResource
    {
        return new TaskResource($this->owned($request, $task));
    }

    public function update(TaskRequest $request, int $task, ManageTask $action): TaskResource
    {
        return new TaskResource($action->update($request->user(), $this->owned($request, $task), $request->validated()));
    }

    public function complete(Request $request, int $task, ManageTask $action): TaskResource
    {
        $data = $request->validate(['completed' => ['required', 'boolean']]);

        return new TaskResource($action->complete($request->user(), $this->owned($request, $task), $data['completed']));
    }

    public function destroy(Request $request, int $task): JsonResponse
    {
        $this->owned($request, $task)->delete();

        return response()->json(status: 204);
    }

    private function owned(Request $request, int $task): CrmTask
    {
        return CrmTask::where('user_id', $request->user()->id)->findOrFail($task);
    }
}
