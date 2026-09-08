<?php

namespace App\Actions\Profile;

use App\Models\User;

class RevokeSessionAction
{
    public function execute(User $user, string $tokenId): bool|string
    {
        $currentTokenId = $user->currentAccessToken()?->id;

        if ((string) $tokenId === (string) $currentTokenId) {
            return 'current';
        }

        $token = $user->tokens()->find($tokenId);

        if (!$token) {
            return false;
        }

        $token->delete();

        return true;
    }
}
