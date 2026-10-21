<?php

namespace App\Actions;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DeleteLeadAction
{
    public function __invoke(User $actor, Lead $record): void
    {
        Gate::forUser($actor)->authorize('delete', $record);
        DB::transaction(function () use ($actor, $record): void {
            app(RecordActivity::class)($actor, $record, 'lead_deleted', 'Lead deleted.');
            $record->delete();
        }, 3);
    }
}
