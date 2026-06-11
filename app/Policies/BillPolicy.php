<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Bill;

class BillPolicy
{
    /**
     * Determine if user can view any bills (admin only)
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    /**
     * Determine if user can view a bill
     */
    public function view(User $user, Bill $bill)
    {
        return $user->isAdmin() || $user->id === $bill->user_id;
    }

    /**
     * Determine if user can create a bill
     */
    public function create(User $user)
    {
        return true; // Any authenticated user can create a bill
    }

    /**
     * Determine if user can update a bill
     */
    public function update(User $user, Bill $bill)
    {
        return $user->isAdmin() || ($user->id === $bill->user_id && !$bill->isPaid());
    }

    /**
     * Determine if user can delete a bill
     */
    public function delete(User $user, Bill $bill)
    {
        return $user->isAdmin() || ($user->id === $bill->user_id && $bill->status === 'pending');
    }
}
