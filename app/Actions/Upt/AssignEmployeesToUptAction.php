<?php

namespace App\Actions\Upt;

use App\Models\Upt;
use App\Models\User;

class AssignEmployeesToUptAction
{
    /**
     * @param  array<int>  $userIds
     */
    public function execute(Upt $upt, array $userIds): void
    {
        User::query()
            ->where('upt_id', $upt->id)
            ->whereNotIn('id', $userIds)
            ->update(['upt_id' => null]);

        if (! empty($userIds)) {
            User::query()
                ->whereIn('id', $userIds)
                ->update(['upt_id' => $upt->id]);
        }
    }
}
