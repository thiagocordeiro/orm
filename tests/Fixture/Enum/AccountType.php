<?php

declare(strict_types=1);

namespace Test\Orm\Fixture\Enum;

enum AccountType: string
{
    case checking = 'checking';
    case saving = 'saving';
}
