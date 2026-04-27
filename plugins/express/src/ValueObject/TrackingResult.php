<?php

declare(strict_types=1);

namespace Plugin\Express\ValueObject;

final class TrackingResult
{
    /**
     * @param TrackingTrace[] $traces
     * @param array<string,mixed> $raw
     */
    public function __construct(
        private readonly string $status,
        private readonly string $companyCode,
        private readonly string $companyName,
        private readonly string $trackingNo,
        private readonly array $traces = [],
        private readonly array $raw = [],
    ) {}

    /**
     * @return array{
     *   status:string,
     *   companyCode:string,
     *   companyName:string,
     *   trackingNo:string,
     *   traces:array<int,array{time:string,context:string,location:string,status:string}>,
     *   raw:array<string,mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'companyCode' => $this->companyCode,
            'companyName' => $this->companyName,
            'trackingNo' => $this->trackingNo,
            'traces' => array_map(
                static fn (TrackingTrace $trace): array => $trace->toArray(),
                $this->traces
            ),
            'raw' => $this->raw,
        ];
    }
}
