<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class UniqueEmail extends Constraint
{
    public string $message = 'This email is already registered. Please use a different email address or login to your existing account.';

    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
