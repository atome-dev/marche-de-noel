<?php

namespace App\Enums;

/**
 * Each stand type unlocks its own features (recipes, raffle prizes…).
 */
enum StandType: string
{
    case Generic = 'generic';
    case Cookies = 'cookies';
    case Raffle = 'raffle';
    case TreeOrder = 'tree_order';
    case Concert = 'concert';

    public function label(): string
    {
        return __('stands.types.'.$this->value);
    }
}
