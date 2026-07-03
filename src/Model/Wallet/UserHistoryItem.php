<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Model\Wallet;

final class UserHistoryItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly ?string $happenedAtUtc,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?string $description,
        public readonly bool $isInProgress,
        public readonly ?UserHistoryParticipant $sourceAccount,
        public readonly ?UserHistoryParticipant $targetAccount,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['Id'] ?? ''),
            (string) ($data['Type'] ?? ''),
            isset($data['HappenedAtUtc']) ? (string) $data['HappenedAtUtc'] : null,
            isset($data['Amount']) ? (float) $data['Amount'] : 0.0,
            (string) ($data['Currency'] ?? ''),
            isset($data['Description']) ? (string) $data['Description'] : null,
            (bool) ($data['IsInProgress'] ?? false),
            isset($data['SourceAccount']) && is_array($data['SourceAccount'])
                ? UserHistoryParticipant::fromArray($data['SourceAccount'])
                : null,
            isset($data['TargetAccount']) && is_array($data['TargetAccount'])
                ? UserHistoryParticipant::fromArray($data['TargetAccount'])
                : null,
        );
    }
}
