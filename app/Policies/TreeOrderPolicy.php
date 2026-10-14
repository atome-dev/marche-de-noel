<?php

namespace App\Policies;

use App\Models\TreeOrder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Families reach their own tree orders; tree stand managers see them all.
 * Administrators are granted everything by Gate::before.
 */
class TreeOrderPolicy
{
    public function view(User $user, TreeOrder $order): bool
    {
        return $order->user_id === $user->id || Gate::forUser($user)->allows('manage-tree-orders');
    }

    /**
     * Pay or cancel: the family who placed the order.
     */
    public function update(User $user, TreeOrder $order): bool
    {
        return $order->user_id === $user->id;
    }
}
