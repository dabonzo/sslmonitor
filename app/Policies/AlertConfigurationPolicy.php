<?php

namespace App\Policies;

use App\Models\AlertConfiguration;
use App\Models\User;

class AlertConfigurationPolicy
{
    public function update(User $user, AlertConfiguration $alertConfiguration): bool
    {
        return $user->id === $alertConfiguration->user_id;
    }

    public function delete(User $user, AlertConfiguration $alertConfiguration): bool
    {
        return $user->id === $alertConfiguration->user_id;
    }
}