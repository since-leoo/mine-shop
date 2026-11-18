<?php

declare(strict_types=1);

namespace App\Infrastructure\Error;

trait ErrorCodeTrait
{
    public function message(array $parameters = []): string
    {
        return $parameters === [] ? $this->template() : vsprintf($this->template(), $parameters);
    }

    public function exception(array $parameters = [], ?\Throwable $previous = null): \DomainException
    {
        return new \DomainException($this->message($parameters), (int) $this->value, $previous);
    }

    public function invalid(array $parameters = [], ?\Throwable $previous = null): \InvalidArgumentException
    {
        return new \InvalidArgumentException($this->message($parameters), (int) $this->value, $previous);
    }

    public function internal(array $parameters = [], ?\Throwable $previous = null): \RuntimeException
    {
        return new \RuntimeException($this->message($parameters), (int) $this->value, $previous);
    }
}
