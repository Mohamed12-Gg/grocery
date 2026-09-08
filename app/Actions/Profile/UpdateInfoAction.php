<?php

namespace App\Actions\Profile;
use App\Models\User;

class UpdateInfoAction
{
   public function execute(User $user, array $data): User
    {
        if (array_key_exists('preferred_languages', $data)) {
            $data['preferred_languages'] ??= [];
        }

        $data = array_filter(
            $data,
            fn ($value, $key) =>
                $key === 'preferred_languages'
                || ($value !== null && $value !== ''),
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($data)) {
            throw new \InvalidArgumentException(
                'No data provided for update'
            );
        }

        $user->update($data);

        return $user->refresh();
    }
}