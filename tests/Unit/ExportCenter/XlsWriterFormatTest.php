<?php

declare(strict_types=1);

namespace HyperfTests\Unit\ExportCenter;

use PHPUnit\Framework\TestCase;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Service\ExportDtoResolver;
use Plugin\ExportCenter\Writer\XlsWriterWriter;

final class XlsWriterFormatTest extends TestCase
{
    public function testInstalledExtensionAcceptsHeaderFormatAndWritesBoldCenteredCells(): void
    {
        if (! extension_loaded('xlswriter')) { self::markTestSkipped('xlswriter extension is not installed'); }
        $path = sys_get_temp_dir() . '/export-format-' . bin2hex(random_bytes(8)) . '.xlsx';
        try {
            $meta = (new ExportDtoResolver())->resolve(XlsFormatRow::class);
            $files = (new XlsWriterWriter())->writeExcel($path, $meta, [['value']], static function (int $progress): void {});
            self::assertSame([$path], $files);
            $zip = new \ZipArchive();
            self::assertTrue($zip->open($path));
            $sheet = new \SimpleXMLElement($zip->getFromName('xl/worksheets/sheet1.xml'));
            $styles = new \SimpleXMLElement($zip->getFromName('xl/styles.xml'));
            $zip->close();
            $styleId = (int) $sheet->sheetData->row[0]->c[0]['s'];
            $style = $styles->cellXfs->xf[$styleId];
            self::assertSame('center', (string) $style->alignment['horizontal']);
            self::assertSame('center', (string) $style->alignment['vertical']);
            self::assertTrue(isset($styles->fonts->font[(int) $style['fontId']]->b));
        } finally {
            if (is_file($path)) { unlink($path); }
        }
    }
}

final class XlsFormatRow
{
    #[ExportColumn(title: 'Name')]
    public string $name;
}
