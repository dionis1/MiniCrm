<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function metrics(Request $request): JsonResponse
    {
        $owner = $request->user()->id;
        $leads = Lead::where('user_id', $owner);
        $open = (clone $leads)->whereNotIn('stage', ['closed_won', 'closed_lost']);
        $won = (clone $leads)->where('stage', 'closed_won');

        return response()->json(['data' => [
            'leads' => (clone $leads)->count(),
            'customers' => Customer::where('user_id', $owner)->count(),
            'open_pipeline_value' => round((float) $open->sum('deal_value'), 2),
            'won_value' => round((float) $won->sum('deal_value'), 2),
            'tasks_today' => CrmTask::where('user_id', $owner)->whereNull('completed_at')
                ->whereDate('due_at', today('UTC'))->count(),
            'tasks_overdue' => CrmTask::where('user_id', $owner)->whereNull('completed_at')
                ->where('due_at', '<', now())->count(),
            'by_stage' => (clone $leads)->selectRaw('stage, COUNT(*) as total')
                ->groupBy('stage')->pluck('total', 'stage'),
        ]]);
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:80']]);
        $owner = $request->user()->id;
        $term = '%'.$data['q'].'%';

        return response()->json(['data' => [
            'leads' => Lead::where('user_id', $owner)->where('name', 'like', $term)
                ->limit(5)->get(['id', 'name', 'stage']),
            'customers' => Customer::where('user_id', $owner)->where('name', 'like', $term)
                ->limit(5)->get(['id', 'name']),
            'tasks' => CrmTask::where('user_id', $owner)->where('title', 'like', $term)
                ->limit(5)->get(['id', 'title']),
        ]]);
    }
}
