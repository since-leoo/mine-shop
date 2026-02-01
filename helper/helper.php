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

namespace {
    use Hyperf\Context\ApplicationContext;
    use Hyperf\Contract\ConfigInterface;
    use Hyperf\Contract\LengthAwarePaginatorInterface;
    use Hyperf\Paginator\AbstractPaginator;
    use Hyperf\Paginator\LengthAwarePaginator;
    use Hyperf\Resource\Json\JsonResource;
    use Psr\Container\ContainerExceptionInterface;
    use Psr\Container\ContainerInterface;
    use Psr\Container\NotFoundExceptionInterface;
    use Psr\EventDispatcher\EventDispatcherInterface;
    use Psr\Http\Message\RequestInterface;

    if (! function_exists('config_base')) {
        function config_base(): ConfigInterface
        {
            return ApplicationContext::getContainer()->get(ConfigInterface::class);
        }
    }

    if (! function_exists('ip')) {
        function ip(): string
        {
            // 从request获取真实ip
            $request = ApplicationContext::getContainer()->get(RequestInterface::class);
            return $request->getServerParams()['remote_addr'] ?? '127.0.0.1';
        }
    }

    if (! function_exists('event')) {
        function event(object $event): EventDispatcherInterface
        {
            return ApplicationContext::getContainer()->get(EventDispatcherInterface::class)->dispatch($event);
        }
    }

    if (! function_exists('di')) {
        /**
         * Finds an entry of the container by its identifier and returns it.
         * @return ContainerInterface|mixed
         * @throws ContainerExceptionInterface
         * @throws NotFoundExceptionInterface
         */
        function di(?string $id = null)
        {
            $container = ApplicationContext::getContainer();
            if ($id) {
                return $container->get($id);
            }

            return $container;
        }
    }

    if (! function_exists('is_paginator')) {
        function is_paginator(mixed $data): bool
        {
            return $data instanceof LengthAwarePaginator || $data instanceof LengthAwarePaginatorInterface || $data instanceof AbstractPaginator;
        }
    }

    if (! function_exists('resource_format_data')) {
        /**
         * 格式化结构体数据.
         * @return mixed
         *               Created by since at 2023/2/25 16:57
         */
        function resource_format_data(mixed $data): mixed
        {
            if (is_paginator($data) || ($data instanceof JsonResource && is_paginator($data->resource))) {
                return [
                    'data' => $data->items(),
                    'current_page' => $data->currentPage(),
                    'total' => $data->total(),
                ];
            }
            return $data;
        }
    }
}
