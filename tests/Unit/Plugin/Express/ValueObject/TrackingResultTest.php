<?php

declare(strict_types=1);

namespace Tests\Unit\Plugin\Express\ValueObject;

use PHPUnit\Framework\TestCase;
use Plugin\Express\ValueObject\TrackingResult;
use Plugin\Express\ValueObject\TrackingTrace;

/**
 * @internal
 * @coversNothing
 */
final class TrackingResultTest extends TestCase
{
    public function testToArrayReturnsStableNormalizedStructure(): void
    {
        $result = new TrackingResult(
            status: 'in_transit',
            companyCode: 'shunfeng',
            companyName: '顺丰速运',
            trackingNo: 'SF123456789',
            traces: [
                new TrackingTrace(
                    time: '2026-04-10 10:00:00',
                    context: '包裹已揽收',
                    location: '上海',
                    status: 'collected'
                ),
            ],
            raw: ['state' => '1']
        );

        self::assertSame([
            'status' => 'in_transit',
            'companyCode' => 'shunfeng',
            'companyName' => '顺丰速运',
            'trackingNo' => 'SF123456789',
            'traces' => [
                [
                    'time' => '2026-04-10 10:00:00',
                    'context' => '包裹已揽收',
                    'location' => '上海',
                    'status' => 'collected',
                ],
            ],
            'raw' => ['state' => '1'],
        ], $result->toArray());
    }
}
