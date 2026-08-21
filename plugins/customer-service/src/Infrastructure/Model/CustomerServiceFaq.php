<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Model;

use Hyperf\DbConnection\Model\Model;

final class CustomerServiceFaq extends Model
{
    protected ?string $table = 'customer_service_faqs';

    protected array $fillable = ['question', 'answer', 'category_name', 'sort', 'enabled'];

    protected array $casts = ['sort' => 'integer', 'enabled' => 'boolean'];
}
