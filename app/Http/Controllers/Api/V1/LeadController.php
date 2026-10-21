<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ManageLead;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadRequest;
use App\Http\Resources\LeadResource;
use App\Models\CrmActivity;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'stage' => ['nullable', Rule::in(Lead::STAGES)],
            'sort' => ['nullable', Rule::in(['created_at', 'name', 'deal_value'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
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
            ->paginate($filters['per_page'] ?? 20)->withQueryString());
    }

    public function store(LeadRequest $request, ManageLead $action): JsonResponse
    {
        $lead = $action->create($request->user(), $request->validated());

        return (new LeadResource($lead))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $lead): LeadResource
    {
        return new LeadResource($this->owned($request, $lead));
    }

    public function update(LeadRequest $request, int $lead, ManageLead $action): LeadResource
    {
        return new LeadResource($action->update($request->user(), $this->owned($request, $lead), $request->validated()));
    }

    public function stage(Request $request, int $lead, ManageLead $action): LeadResource
    {
        $data = $request->validate([
            'stage' => ['required', Rule::in(Lead::STAGES)],
            'version' => ['required', 'integer', 'min:0'],
        ]);

        return new LeadResource($action->update($request->user(), $this->owned($request, $lead), $data));
    }

    public function convert(Request $request, int $lead, ManageLead $action): JsonResponse
    {
        $data = $request->validate(['version' => ['required', 'integer', 'min:0']]);
        $customer = $action->convert($request->user(), $this->owned($request, $lead), $data['version']);

        return response()->json(['data' => ['id' => $customer->id, 'name' => $customer->name]], 201);
    }

    public function destroy(Request $request, int $lead): JsonResponse
    {
        $record = $this->owned($request, $lead);
        $record->delete();

        return response()->json(status: 204);
    }

    public function activities(Request $request, int $lead): JsonResponse
    {
        $record = $this->owned($request, $lead);

        return response()->json(['data' => CrmActivity::query()
            ->where('user_id', $request->user()->id)
            ->where('subject_type', 'lead')
            ->where('subject_id', $record->id)
            ->latest()->limit(100)->get(['id', 'type', 'description', 'created_at'])]);
    }

    private function owned(Request $request, int $id): Lead
    {
        return Lead::where('user_id', $request->user()->id)->findOrFail($id);
    }
}
