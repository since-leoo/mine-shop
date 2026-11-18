<?php

declare(strict_types=1);

namespace Plugin\SystemMessage\Infrastructure\Adapter;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Contract\NotificationTransportInterface;

class NotificationTransport implements NotificationTransportInterface
{
    public function __construct(private readonly ConfigInterface $config, private readonly ClientFactory $clients) {}

    public function email(string $recipient, string $title, string $html): void
    {
        $from = (string) $this->config->get('system_message.email.from', '');
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL) || ! filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('邮件收件人或发件人配置无效');
        }
        if (strpbrk($title, "\r\n") !== false) {
            throw new \InvalidArgumentException('邮件标题不能包含换行');
        }
        $headers = ['MIME-Version' => '1.0', 'Content-Type' => 'text/html; charset=UTF-8', 'From' => $from];
        if (! $this->mail($recipient, mb_encode_mimeheader($title, 'UTF-8'), $html, $headers)) {
            throw new \RuntimeException('邮件传输服务拒绝发送');
        }
    }

    public function gateway(string $channel, int $userId, array $payload): void
    {
        if (! in_array($channel, ['socketio', 'websocket', 'push'], true)) {
            throw new \InvalidArgumentException('未知通知网关渠道');
        }
        $url = (string) $this->config->get('system_message.gateways.' . $channel . '.url', '');
        $token = (string) $this->config->get('system_message.gateways.' . $channel . '.token', '');
        if (! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' || $token === '' || strpbrk($token, "\r\n") !== false) {
            throw new \RuntimeException('通知网关未配置有效 HTTPS 地址和认证令牌: ' . $channel);
        }
        $response = $this->clients->create()->post($url, [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Idempotency-Key' => $channel . ':' . $payload['message_id'] . ':' . $userId],
            'json' => ['channel' => $channel, 'user_id' => $userId, 'message' => $payload],
            'connect_timeout' => 3,
            'timeout' => 5,
            'http_errors' => false,
            'allow_redirects' => false,
        ]);
        $status = $response->getStatusCode();
        $receipt = json_decode((string) $response->getBody(), true);
        if ($status < 200 || $status >= 300 || ! is_array($receipt) || ($receipt['success'] ?? null) !== true) {
            throw new \RuntimeException('通知网关未确认接收: ' . $channel . ' HTTP ' . $status);
        }
    }

    protected function mail(string $recipient, string $subject, string $html, array $headers): bool
    {
        return mail($recipient, $subject, $html, $headers);
    }
}
