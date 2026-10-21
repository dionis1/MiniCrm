<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ConvertLeadAction;
use App\Actions\CreateLeadAction;
use App\Actions\DeleteLeadAction;
use App\Actions\UpdateLeadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadFilterRequest;
use App\Http\Requests\LeadRequest;
use App\Http\Requests\LeadStageRequest;
use App\Http\Requests\LeadVersionRequest;
use App\Http\Resources\LeadResource;
use App\Models\CrmActivity;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LeadController extends Controller
{
    public function index(LeadFilterRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $query = Lead::where('user_id', $request->user()->id);
        if (! empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }
        if (! empty($filters['q'])) {
            $query->where(function ($builder) use ($filters): void {
                $builder->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('company_name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('email', 'like', '%'.$filters['q'].'%');
            });
        }

        return LeadResource::collection($query
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->orderBy('id', $filters['direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 20)->withQueryString());
    }

    public function store(LeadRequest $request, CreateLeadAction $action): JsonResponse
    {
        $lead = $action($request->user(), $request->validated());

        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $lead): LeadResource
    {
        return new LeadResource($this->owned($request, $lead));
    }

    public function update(LeadRequest $request, int $lead, UpdateLeadAction $action): LeadResource
    {
        return new LeadResource($action($request->user(), $this->owned($request, $lead), $request->validated()));
    }

    public function stage(LeadStageRequest $request, int $lead, UpdateLeadAction $action): LeadResource
    {
        $data = $request->validated();

        return new LeadResource($action($request->user(), $this->owned($request, $lead), $data));
    }

    public function convert(LeadVersionRequest $request, int $lead, ConvertLeadAction $action): JsonResponse
    {
        $data = $request->validated();
        $customer = $action($request->user(), $this->owned($request, $lead), $data['version']);

        return response()->json(['data' => ['id' => $customer->id, 'name' => $customer->name]], 201);
    }

    public function destroy(Request $request, int $lead, DeleteLeadAction $action): JsonResponse
    {
        $record = $this->owned($request, $lead);
        $action($request->user(), $record);

        return response()->json(status: 204);
    }

    public function activities(Request $request, int $lead): JsonResponse
    {
        $record = $this->owned($request, $lead);

        return response()->json(['data' => CrmActivity::query()
            ->where('user_id', $request->user()->id)
            ->where('subject_type', 'lead')
            ->where('subject_id', $record->id)
            ->latest('id')->limit(100)->get(['id', 'type', 'description', 'created_at'])]);
    }

    private function owned(Request $request, int $id): Lead
    {
        $record = Lead::where('user_id', $request->user()->id)->findOrFail($id);
        Gate::authorize('view', $record);

        return $record;
    }
}
