<?php

declare(strict_types=1);

namespace Plugin\ProductReview;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => ['scan' => ['paths' => [__DIR__]]],
            'plugin_capabilities' => [
                \App\Domain\Infrastructure\Plugin\Contract\ProductReviewQueryInterface::class => \Plugin\ProductReview\Interface\Adapter\ProductReviewGateway::class,
                \App\Domain\Infrastructure\Plugin\Contract\ProductReviewCommandInterface::class => \Plugin\ProductReview\Interface\Adapter\ProductReviewGateway::class,
                \App\Domain\Infrastructure\Plugin\Contract\ReviewStatisticsInterface::class => \Plugin\ProductReview\Interface\Adapter\ProductReviewGateway::class,
            ],
        ];
    }
}
