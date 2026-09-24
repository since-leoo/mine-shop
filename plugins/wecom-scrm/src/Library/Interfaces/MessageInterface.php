<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Library\Interfaces;

interface MessageInterface
{
    public function sendText(array $touser, string $content, array $options = []): array;
    public function sendMarkdown(array $touser, string $content, array $options = []): array;
}
