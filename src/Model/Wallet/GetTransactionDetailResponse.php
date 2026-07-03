<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Model\Wallet;

final class GetTransactionDetailResponse
{
    /**
     * @param array<string, mixed> $detailInformation
     * @param list<mixed> $errors
     */
    public function __construct(
        public readonly bool $requestSuccessful,
        public readonly array $detailInformation,
        public readonly array $errors = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $detail = $data['DetailInformation'] ?? [];

        return new self(
            (bool) ($data['RequestSuccessful'] ?? false),
            is_array($detail) ? $detail : [],
            is_array($data['Errors'] ?? null) ? $data['Errors'] : [],
        );
    }

    public function resolveBarionPaymentId(): ?string
    {
        $paymentId = $this->detailInformation['PaymentId'] ?? null;

        return is_string($paymentId) && '' !== $paymentId ? $paymentId : null;
    }
}
