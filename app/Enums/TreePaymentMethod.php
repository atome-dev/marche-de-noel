<?php

namespace App\Enums;

enum TreePaymentMethod: string
{
    case HelloAsso = 'helloasso';
    case Cheque = 'cheque';

    public function label(): string
    {
        return __('trees.payment_methods.'.$this->value);
    }
}
