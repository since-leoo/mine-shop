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

namespace HyperfTests\Unit\Database\Seeders;

use App\Domain\Content\DiyPage\ValueObject\DiyPageSchemaVo;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 * @coversNothing
 */
final class DiyTemplateSeederTest extends TestCase
{
    public function testDefaultTemplateSchemasPassDomainValidation(): void
    {
        require_once BASE_PATH . '/databases/seeders/diy_template_seeder_20260606.php';

        $seeder = new \DiyTemplateSeeder20260606();
        $method = (new ReflectionClass($seeder))->getMethod('templates');
        $method->setAccessible(true);

        foreach ($method->invoke($seeder) as $template) {
            $vo = DiyPageSchemaVo::fromArray($template['schema'], $template['page_key']);

            self::assertSame($template['page_key'], $vo->toArray()['page']['key']);
        }
    }
}
