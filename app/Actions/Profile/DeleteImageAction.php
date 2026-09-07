<?php 
namespace App\Actions\Profile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
class DeleteImageAction
{
    public function execute(User $user)
    {
        if (!$user->profile_image) {
            return false;
        }

        // Delete image from storage
        if (Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        // Update user
        $user->update(['profile_image' => null]);
        return true;
    }
}