<?php

namespace App\Enums;

/**
 * Status of a tree order, computed from its dates and the order deadline.
 */
enum TreeOrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case PickedUp = 'picked_up';
    case Cancelled = 'cancelled';
    // Still unpaid after the deadline: not taken into account.
    case Expired = 'expired';

    public function label(): string
    {
        return __('trees.status.'.$this->value);
    }

    /**
     * Flux badge color.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'green',
            self::PickedUp => 'sky',
            self::Cancelled, self::Expired => 'zinc',
        };
    }
}
