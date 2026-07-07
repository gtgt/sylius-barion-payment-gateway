<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Model\Wallet;

class UserHistoryParticipant
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
            isset($data['Name']) ? $data['Name'] : null,
            isset($data['Email']) ? $data['Email'] : null,
        );
    }
}
