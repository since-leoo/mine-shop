<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Plugin\WecomScrm\Service\WecomCallbackService;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

#[Controller(prefix: '/wecom-scrm/callback')]
final class WecomCallbackController
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response,
        private readonly WecomCallbackService $service,
    ) {}

    #[GetMapping(path: '')]
    public function verify(): PsrResponseInterface
    {
        try {
            $echo = $this->service->verify(
                (string) $this->request->input('msg_signature', ''),
                (string) $this->request->input('timestamp', ''),
                (string) $this->request->input('nonce', ''),
                (string) $this->request->input('echostr', ''),
            );
            return $this->response->raw($echo);
        } catch (\Throwable $e) {
            return $this->response->raw('invalid')->withStatus(403);
        }
    }

    #[PostMapping(path: '')]
    public function receive(): PsrResponseInterface
    {
        try {
            $this->service->handle(
                (string) $this->request->input('msg_signature', ''),
                (string) $this->request->input('timestamp', ''),
                (string) $this->request->input('nonce', ''),
                (string) $this->request->getBody(),
            );
            return $this->response->raw('success');
        } catch (\Throwable $e) {
            return $this->response->raw('invalid')->withStatus(403);
        }
    }
}
