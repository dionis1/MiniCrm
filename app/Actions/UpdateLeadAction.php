<?php

namespace App\Actions;

use App\Models\Lead;
use App\Models\User;
use App\Support\CrmRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class UpdateLeadAction
{
    public function __invoke(User $actor, Lead $record, array $input): Lead
    {
        Gate::forUser($actor)->authorize('update', $record);
        $data = Validator::make($input, CrmRules::lead(true))->validate();

        return DB::transaction(function () use ($actor, $data, $record): Lead {
            $version = $data['version'];
            unset($data['version']);
            $changed = Lead::whereKey($record->id)->where('user_id', $actor->id)
                ->where('version', $version)->whereNull('converted_at')->whereNull('converted_company_id')
                ->update(array_merge($data, ['version' => $version + 1]));
            if ($changed !== 1) {
                throw new ConflictHttpException('Lead was updated or converted. Refresh and retry.');
            }
            $record->refresh();
            app(RecordActivity::class)($actor, $record, 'lead_updated', 'Lead updated.');

            return $record->refresh();
        }, 3);
    }
}
