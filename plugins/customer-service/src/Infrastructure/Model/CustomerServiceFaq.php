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

namespace Plugin\CustomerService\Infrastructure\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $question
 * @property string $answer
 * @property null|string $category_name
 * @property null|int $category_id
 * @property null|int $published_revision_id
 * @property null|array $alternate_questions
 * @property int $sort
 * @property bool $enabled
 * @property null|\Carbon\Carbon $created_at
 * @property null|\Carbon\Carbon $updated_at
 */
final class CustomerServiceFaq extends Model
{
    protected ?string $table = 'customer_service_faqs';

    protected array $fillable = ['question', 'answer', 'category_name', 'sort', 'enabled', 'category_id', 'published_revision_id', 'alternate_questions'];

    protected array $casts = ['alternate_questions' => 'array', 'category_id' => 'integer', 'published_revision_id' => 'integer', 'sort' => 'integer', 'enabled' => 'boolean'];
}
