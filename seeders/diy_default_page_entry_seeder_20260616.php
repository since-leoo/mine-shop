<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;

require_once BASE_PATH . '/databases/seeders/diy_template_category_seeder_20260606.php';
require_once BASE_PATH . '/databases/seeders/diy_template_seeder_20260606.php';
require_once BASE_PATH . '/databases/seeders/diy_default_page_seeder_20260616.php';

class DiyDefaultPageEntrySeeder20260616 extends Seeder
{
    public function run(): void
    {
        (new DiyTemplateCategorySeeder20260606())->run();
        (new DiyTemplateSeeder20260606())->run();
        (new DiyDefaultPageSeeder20260616())->run();
    }
}
