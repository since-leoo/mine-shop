<?php

declare(strict_types=1);

namespace Plugin\SystemMessage\Infrastructure\Adapter;

use App\Infrastructure\Error\SystemMessageError;
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
            throw SystemMessageError::EMAIL_ADDRESS_INVALID->internal();
        }
        if (strpbrk($title, "\r\n") !== false) {
            throw SystemMessageError::EMAIL_SUBJECT_INVALID->invalid();
        }
        $headers = ['MIME-Version' => '1.0', 'Content-Type' => 'text/html; charset=UTF-8', 'From' => $from];
        if (! $this->mail($recipient, mb_encode_mimeheader($title, 'UTF-8'), $html, $headers)) {
            throw SystemMessageError::EMAIL_TRANSPORT_REJECTED->internal();
        }
    }

    public function gateway(string $channel, int $userId, array $payload): void
    {
        if (! in_array($channel, ['socketio', 'websocket', 'push'], true)) {
            throw SystemMessageError::GATEWAY_CHANNEL_UNKNOWN->invalid();
        }
        $url = (string) $this->config->get('system_message.gateways.' . $channel . '.url', '');
        $token = (string) $this->config->get('system_message.gateways.' . $channel . '.token', '');
        if (! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' || $token === '' || strpbrk($token, "\r\n") !== false) {
            throw SystemMessageError::GATEWAY_CONFIGURATION_INVALID->internal([$channel]);
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
            throw SystemMessageError::GATEWAY_RECEIPT_FAILED->internal([$channel, $status]);
        }
    }

    protected function mail(string $recipient, string $subject, string $html, array $headers): bool
    {
        return mail($recipient, $subject, $html, $headers);
    }
}
