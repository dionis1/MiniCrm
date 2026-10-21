<?php

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DeleteContactAction
{
    public function __invoke(User $actor, Contact $record): void
    {
        Gate::forUser($actor)->authorize('delete', $record);
        DB::transaction(function () use ($actor, $record): void {
            app(RecordActivity::class)($actor, $record, 'contact_deleted', 'Contact deleted.');
            $record->delete();
        }, 3);
    }
}
