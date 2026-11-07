<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->customer_id || $this->update($user, $order);
    }

    public function update(User $user, Order $order): bool
    {
        return $user->operationalShop()?->id === $order->print_shop_id;
    }
}
