<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Mapper;

use Plugin\CustomerService\Domain\Entity\CustomerServiceConversationEntity;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;

final class CustomerServiceConversationMapper
{
    public function fromModel(CustomerServiceConversation $model): CustomerServiceConversationEntity
    {
        return new CustomerServiceConversationEntity($model->toArray());
    }
}
