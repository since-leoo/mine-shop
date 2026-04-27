<?php

declare(strict_types=1);

namespace Tests\Unit\Plugin\Express\Provider;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use Hyperf\Guzzle\ClientFactory;
use PHPUnit\Framework\TestCase;
use Plugin\Express\Provider\Kuaidi100Provider;

/**
 * @internal
 * @coversNothing
 */
final class Kuaidi100ProviderTest extends TestCase
{
    public function testTrackMapsInTransitPayloadToNormalizedResult(): void
    {
        $provider = $this->buildProvider([
            'state' => '0',
            'com' => 'shunfeng',
            'nu' => 'SF123456789',
            'data' => [
                [
                    'ftime' => '2026-04-10 10:00:00',
                    'context' => '包裹已揽收',
                    'location' => '上海',
                    'status' => '在途',
                ],
            ],
        ]);

        $result = $provider->track('shunfeng', 'SF123456789');

        self::assertSame('in_transit', $result->toArray()['status']);
        self::assertSame('shunfeng', $result->toArray()['companyCode']);
        self::assertSame('SF123456789', $result->toArray()['trackingNo']);
    }

    public function testTrackMapsSignedPayloadToSignedStatus(): void
    {
        $provider = $this->buildProvider([
            'state' => '3',
            'com' => 'yuantong',
            'nu' => 'YT123456789',
            'data' => [],
        ]);

        $result = $provider->track('yuantong', 'YT123456789');

        self::assertSame('signed', $result->toArray()['status']);
    }

    public function testTrackMapsUnknownPayloadToUnknownStatus(): void
    {
        $provider = $this->buildProvider([
            'state' => '999',
            'com' => 'ems',
            'nu' => 'EMS123456789',
            'data' => [],
        ]);

        $result = $provider->track('ems', 'EMS123456789');

        self::assertSame('unknown', $result->toArray()['status']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildProvider(array $payload): Kuaidi100Provider
    {
        $client = $this->createMock(Client::class);
        $client->method('post')->willReturn(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        ));

        $factory = $this->createMock(ClientFactory::class);
        $factory->method('create')->willReturn($client);

        return new Kuaidi100Provider($factory, [
            'customer' => 'customer-demo',
            'key' => 'key-demo',
            'endpoint' => 'https://poll.kuaidi100.com/poll/query.do',
            'timeout' => 5,
        ]);
    }
}
