<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

final class OrderAddressValue
{
    private string $receiverName = '';

    private string $receiverPhone = '';

    private string $province = '';

    private string $city = '';

    private string $district = '';

    private string $detail = '';

    private string $fullAddress = '';

    public static function fromArray(array $payload): self
    {
        $value = new self();
        $value->setReceiverName((string) ($payload['name'] ?? ''));
        $value->setReceiverPhone((string) ($payload['phone'] ?? ''));
        $value->setProvince((string) ($payload['province'] ?? ''));
        $value->setCity((string) ($payload['city'] ?? ''));
        $value->setDistrict((string) ($payload['district'] ?? ''));
        $value->setDetail((string) ($payload['detail'] ?? ''));
        $value->setFullAddress((string) ($payload['full_address'] ?? ''));
        return $value;
    }

    public function setReceiverName(string $receiverName): void
    {
        $this->receiverName = $receiverName;
    }

    public function getReceiverName(): string
    {
        return $this->receiverName;
    }

    public function setReceiverPhone(string $receiverPhone): void
    {
        $this->receiverPhone = $receiverPhone;
    }

    public function getReceiverPhone(): string
    {
        return $this->receiverPhone;
    }

    public function setProvince(string $province): void
    {
        $this->province = $province;
    }

    public function getProvince(): string
    {
        return $this->province;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setDistrict(string $district): void
    {
        $this->district = $district;
    }

    public function getDistrict(): string
    {
        return $this->district;
    }

    public function setDetail(string $detail): void
    {
        $this->detail = $detail;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function setFullAddress(string $fullAddress): void
    {
        $this->fullAddress = $fullAddress;
    }

    public function getFullAddress(): string
    {
        return $this->fullAddress ?: trim($this->province . $this->city . $this->district . $this->detail);
    }

    public function toArray(): array
    {
        return [
            'name' => $this->receiverName,
            'phone' => $this->receiverPhone,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'detail' => $this->detail,
            'full_address' => $this->fullAddress,
        ];
    }
}
