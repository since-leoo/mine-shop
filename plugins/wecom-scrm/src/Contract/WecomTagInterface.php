<?php
declare(strict_types=1);
namespace Plugin\WecomScrm\Contract;
interface WecomTagInterface extends WecomCapabilityInterface
{
    public function list(): array;
    public function create(string $groupName, string $tagName, ?string $color = null): array;
}
