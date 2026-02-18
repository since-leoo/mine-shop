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

namespace Tests\Unit\Plugin\ExportCenter\Service;

use PHPUnit\Framework\TestCase;
use Plugin\ExportCenter\Service\ExportFileService;

/**
 * @internal
 * @coversNothing
 */
final class ExportFileServiceTest extends TestCase
{
    private ExportFileService $service;

    private string $tempDir;

    protected function setUp(): void
    {
        $this->service = new ExportFileService();
        $this->tempDir = sys_get_temp_dir() . '/export_file_service_test_' . uniqid();
        mkdir($this->tempDir, 0o777, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $files = glob($this->tempDir . '/*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testDeleteFileRemovesExistingFile(): void
    {
        $path = $this->createTempFile();
        self::assertFileExists($path);

        $result = $this->service->deleteFile($path);

        self::assertTrue($result);
        self::assertFileDoesNotExist($path);
    }

    public function testDeleteFileReturnsFalseForNonExistentFile(): void
    {
        $result = $this->service->deleteFile($this->tempDir . '/nonexistent.txt');

        self::assertFalse($result);
    }

    public function testFileExistsReturnsTrueForExistingFile(): void
    {
        $path = $this->createTempFile();

        self::assertTrue($this->service->fileExists($path));
    }

    public function testFileExistsReturnsFalseForNonExistentFile(): void
    {
        self::assertFalse($this->service->fileExists($this->tempDir . '/nonexistent.txt'));
    }

    public function testFileExistsReturnsFalseForDirectory(): void
    {
        self::assertFalse($this->service->fileExists($this->tempDir));
    }

    public function testGetFileSizeReturnsCorrectSize(): void
    {
        $content = 'hello world';
        $path = $this->createTempFile($content);

        self::assertSame(mb_strlen($content), $this->service->getFileSize($path));
    }

    public function testGetFileSizeReturnsZeroForNonExistentFile(): void
    {
        self::assertSame(0, $this->service->getFileSize($this->tempDir . '/nonexistent.txt'));
    }

    public function testGetFileSizeReturnsZeroForEmptyFile(): void
    {
        $path = $this->createTempFile('');

        self::assertSame(0, $this->service->getFileSize($path));
    }

    private function createTempFile(string $content = 'test content'): string
    {
        $path = $this->tempDir . '/test_' . uniqid() . '.txt';
        file_put_contents($path, $content);
        return $path;
    }
}
