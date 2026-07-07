<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Model\Wallet;

class GetUserHistoryResponse
{
    /**
     * @param list<UserHistoryItem> $items
     * @param list<mixed> $errors
     */
    public function __construct(
        public readonly bool $requestSuccessful,
        public readonly array $items,
        public readonly array $errors = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ($data['UserHistory'] ?? [] as $item) {
            if (is_array($item)) {
                $items[] = UserHistoryItem::fromArray($item);
            }
        }

        return new self(
            (bool) ($data['RequestSuccessful'] ?? false),
            $items,
            is_array($data['Errors'] ?? null) ? $data['Errors'] : [],
        );
    }
}
