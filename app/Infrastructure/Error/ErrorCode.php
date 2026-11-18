<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

interface ErrorCode extends \BackedEnum
{
    public function template(): string;

    public function message(array $parameters = []): string;
}
