<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Application\Admin\Trade\Dto\OrderExportDto;
use App\Domain\Trade\Order\Mapper\OrderExportMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plugin\ExportCenter\Service\DtoHydrator;
use Plugin\ExportCenter\Service\ExportDtoResolver;

final class OrderExportSpecificationsTest extends TestCase
{
    public static function specificationSnapshots(): iterable
    {
        yield 'named values' => [[['name' => '颜色', 'value' => '红色'], ['name' => '尺码', 'value' => 'XL']], '红色/XL'];
        yield 'legacy values' => [['红色', 'XL'], '红色/XL'];
        yield 'value name objects' => [[['value_name' => '红色'], ['valueName' => 'XL']], '红色/XL'];
        yield 'mapped values' => [['颜色' => '红色', '尺码' => 'XL'], '红色/XL'];
        yield 'single string' => ['红色/XL', '红色/XL'];
        yield 'numeric zero' => [[['name' => '编号', 'value' => 0]], '0'];
        yield 'invalid nested values' => [[['name' => '颜色', 'value' => ['unexpected' => '红色']], ['name' => '空值'], null, false], '-'];
        yield 'empty values' => [[], '-'];
        yield 'null values' => [null, '-'];
    }

    #[DataProvider('specificationSnapshots')]
    public function testExportHydratesHistoricalAndNamedSpecificationsWithoutArrayConversion(mixed $specifications, string $expected): void
    {
        $item = ['product_name' => '测试商品', 'sku_name' => 'SKU-1', 'spec_values' => $specifications, 'unit_price' => 1299, 'quantity' => 2];
        $row = OrderExportMapper::toRow(['order_no' => 'ORDER-1', 'member' => ['nickname' => '用户']], $item);
        $columns = (new ExportDtoResolver())->resolve(OrderExportDto::class)['columns'];
        $columns = array_values(array_filter($columns, static fn (array $column): bool => $column['field'] === 'spec_values'));

        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            self::assertSame([$expected], (new DtoHydrator())->hydrate($columns, $row));
        } finally {
            restore_error_handler();
        }

        self::assertSame('ORDER-1', $row['order_no']);
        self::assertSame(['nickname' => '用户'], $row['member']);
        self::assertSame(1299, $row['unit_price']);
        self::assertSame(2, $row['quantity']);
        self::assertSame($specifications, $item['spec_values']);
    }
}
