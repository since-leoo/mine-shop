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

namespace Plugin\WecomScrm\Model;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Model\Events\Created;
use Hyperf\DbConnection\Model\Model;
use Plugin\WecomScrm\Job\SyncWecomTagsJob;

final class WecomTag extends Model
{
    protected ?string $table = 'wecom_tags';

    protected array $fillable = ['tag_id', 'group_id', 'name', 'color'];

    public function created(Created $event): void
    {
        if (ApplicationContext::hasContainer()) {
            $this->getContainer()->get(DriverFactory::class)->get('default')->push(new SyncWecomTagsJob());
        }
    }
}
