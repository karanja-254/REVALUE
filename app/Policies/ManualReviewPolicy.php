<?php

namespace App\Policies;

use App\Models\ManualReview;
use App\Models\User;

class ManualReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'super_admin';
    }

    public function update(User $user, ManualReview $review): bool
    {
        return $user->role === 'admin' || $user->role === 'super_admin';
    }
}
