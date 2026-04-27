<?php

declare(strict_types=1);

namespace Plugin\Express\Provider;

use Hyperf\Guzzle\ClientFactory;
use Plugin\Express\Contract\LogisticsTrackingInterface;
use Plugin\Express\Exception\TrackingException;
use Plugin\Express\ValueObject\TrackingResult;
use Plugin\Express\ValueObject\TrackingTrace;
use Psr\Http\Message\ResponseInterface;

final class Kuaidi100Provider implements LogisticsTrackingInterface
{
    /**
     * @param array{customer:string,key:string,endpoint:string,timeout?:int,company_name_map?:array<string,string>} $config
     */
    public function __construct(
        private readonly ClientFactory $clientFactory,
        private readonly array $config,
    ) {}

    public function track(string $companyCode, string $trackingNo): TrackingResult
    {
        if (trim((string) ($this->config['customer'] ?? '')) === '' || trim((string) ($this->config['key'] ?? '')) === '') {
            throw new TrackingException('快递100配置不完整');
        }

        $payload = [
            'com' => strtolower(trim($companyCode)),
            'num' => trim($trackingNo),
            'show' => '0',
            'order' => 'desc',
            'lang' => 'zh',
            'resultv2' => '4',
        ];

        $param = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $response = $this->clientFactory->create()->post($this->config['endpoint'], [
            'form_params' => [
                'customer' => $this->config['customer'],
                'sign' => strtoupper(md5($param . $this->config['key'] . $this->config['customer'])),
                'param' => $param,
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            'timeout' => (int) ($this->config['timeout'] ?? 5),
        ]);

        return $this->mapResponse($response, $companyCode, $trackingNo);
    }

    private function mapResponse(ResponseInterface $response, string $companyCode, string $trackingNo): TrackingResult
    {
        $decoded = json_decode((string) $response->getBody(), true);
        if (! is_array($decoded)) {
            throw new TrackingException('快递100返回了无效响应');
        }

        if (isset($decoded['returnCode']) && (string) $decoded['returnCode'] !== '200') {
            throw new TrackingException((string) ($decoded['message'] ?? '物流查询失败'));
        }

        $traces = [];
        foreach (($decoded['data'] ?? []) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $traces[] = new TrackingTrace(
                time: (string) ($item['ftime'] ?? $item['time'] ?? ''),
                context: (string) ($item['context'] ?? ''),
                location: (string) ($item['location'] ?? $item['areaName'] ?? ''),
                status: (string) ($item['statusCode'] ?? $item['status'] ?? 'unknown'),
            );
        }

        $normalizedCompanyCode = (string) ($decoded['com'] ?? strtolower($companyCode));
        $companyMap = is_array($this->config['company_name_map'] ?? null) ? $this->config['company_name_map'] : [];
        $companyName = $companyMap[$normalizedCompanyCode] ?? $normalizedCompanyCode;

        return new TrackingResult(
            status: $this->normalizeState((string) ($decoded['state'] ?? '')),
            companyCode: $normalizedCompanyCode,
            companyName: $companyName,
            trackingNo: (string) ($decoded['nu'] ?? $trackingNo),
            traces: $traces,
            raw: $decoded,
        );
    }

    private function normalizeState(string $state): string
    {
        return match ($state) {
            '0', '1', '5' => 'in_transit',
            '3' => 'signed',
            '2', '4', '6', '14' => 'problem',
            default => 'unknown',
        };
    }
}
