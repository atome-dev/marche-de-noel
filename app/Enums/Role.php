<?php

namespace App\Enums;

enum Role: string
{
    case Volunteer = 'volunteer';
    case Admin = 'admin';

    public function label(): string
    {
        return __('roles.'.$this->value);
    }
}
