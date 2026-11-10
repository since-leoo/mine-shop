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

namespace Plugin\Distribution\Interface\Api;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponse;

/** Images contain only a public invitation. An unguessable filename allows ordinary Image loading without JWT headers. */
#[Controller(prefix: '/api/v1/distribution/share/images')]
final class ShareImageController
{
    public function __construct(private readonly ResponseInterface $response) {}

    #[GetMapping(path: '{name:[a-f0-9]{64}\.png}')]
    public function image(string $name): PsrResponse
    {
        if (! preg_match('/^[a-f0-9]{64}\.png$/', $name)) {
            return $this->response->raw('Not found')->withStatus(404);
        }
        $file = BASE_PATH . '/public/uploadfile/wechat/mini/qrcode/' . $name;
        if (! is_file($file)) {
            return $this->response->raw('Not found')->withStatus(404);
        }
        return $this->response->raw(file_get_contents($file))->withHeader('Content-Type', 'image/png')->withHeader('Cache-Control', 'public, max-age=3600')->withHeader('X-Content-Type-Options', 'nosniff');
    }
}
