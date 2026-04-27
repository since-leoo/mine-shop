<?php

declare(strict_types=1);

namespace Plugin\Express\ValueObject;

final class TrackingTrace
{
    public function __construct(
        private readonly string $time,
        private readonly string $context,
        private readonly string $location = '',
        private readonly string $status = 'unknown',
    ) {}

    /**
     * @return array{time:string,context:string,location:string,status:string}
     */
    public function toArray(): array
    {
        return [
            'time' => $this->time,
            'context' => $this->context,
            'location' => $this->location,
            'status' => $this->status,
        ];
    }
}
