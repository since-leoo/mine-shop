<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

interface CustomerServiceKnowledgeInterface
{
    /** Every evidence item has a stable id and a public, bounded text. */
    public function search(string $question, array $agent, ?int $categoryId, int $memberId): array;
}
