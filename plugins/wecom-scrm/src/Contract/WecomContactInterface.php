<?php
declare(strict_types=1);
namespace Plugin\WecomScrm\Contract;
interface WecomContactInterface extends WecomCapabilityInterface
{
    public function getUser(string $userid): array;
    public function listUsers(int $departmentId = 0): array;
}
