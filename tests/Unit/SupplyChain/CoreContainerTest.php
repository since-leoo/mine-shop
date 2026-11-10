<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace HyperfTests\Unit\SupplyChain;

use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use App\Infrastructure\Plugin\NullExternalFulfillment;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class CoreContainerTest extends TestCase
{
    public function testEnabledSupplyChainResolvesProductionContainerAndRegistersEveryPublicEvent(): void
    {
        $result = $this->worker('enabled');
        self::assertSame('Plugin\SupplyChain\Interface\Adapter\SupplyFulfillmentAdapter', $result['resolved'][ExternalFulfillmentInterface::class]);
        self::assertSame('Plugin\SupplyChain\Service\SupplyChainManager', $result['resolved']['Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface']);
        self::assertCount(19, $result['resolved']);
        self::assertCount(4, $result['queues']);
        self::assertCount(4, $result['processes']);
        self::assertSame([1, 1, 1, 1, 1], array_values($result['events']));
        self::assertTrue($result['plugin_autoloadable']);
        self::assertTrue($result['payment_capability_matches']);
        self::assertTrue($result['temporary_lock']);
        self::assertSame(0, $result['network_requests']);
    }

    public function testDisabledSupplyChainDoesNotRegisterWorkersOrListenersAndFailsClosed(): void
    {
        $result = $this->worker('disabled');
        self::assertSame(NullExternalFulfillment::class, $result['resolved'][ExternalFulfillmentInterface::class]);
        self::assertSame([], $result['queues']);
        self::assertSame([], $result['processes']);
        self::assertSame([0, 0, 0, 0, 0], array_values($result['events']));
        self::assertFalse($result['plugin_autoloadable']);
        self::assertTrue($result['external_rejected']);
        self::assertTrue($result['payment_capability_matches']);
        self::assertTrue($result['temporary_lock']);
        self::assertSame(0, $result['network_requests']);
    }

    private function worker(string $mode): array
    {
        $process = proc_open([\PHP_BINARY, BASE_PATH . '/tests/Support/supply-chain-container-smoke.php', $mode], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr . $stdout);
        return json_decode(trim($stdout), true, 512, \JSON_THROW_ON_ERROR);
    }
}
