<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Model\Wallet;

final class UserHistoryParticipant
{
    public function __construct(
        public readonly ?string $Name,
        public readonly ?string $Email,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['Name']) ? (string) $data['Name'] : null,
            isset($data['Email']) ? (string) $data['Email'] : null,
        );
    }
}
