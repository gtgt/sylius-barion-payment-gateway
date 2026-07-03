<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Api;

use SyliusBarionPaymentGateway\Model\Wallet\GetTransactionDetailResponse;
use SyliusBarionPaymentGateway\Model\Wallet\GetUserHistoryResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BarionWalletApi
{
    private const API_URL_PROD = 'https://api.barion.com';

    private const API_URL_TEST = 'https://api.test.barion.com';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $posKey,
        private readonly string $environment,
    ) {
    }

    public function getUserHistory(
        int $limit = 20,
        ?string $currency = null,
        ?string $lastVisibleItemId = null,
        ?\DateTimeInterface $lastRequestTime = null,
    ): GetUserHistoryResponse {
        $query = array_filter([
            'POSKey' => $this->posKey,
            'Limit' => $limit,
            'Currency' => $currency,
            'LastVisibleItemId' => $lastVisibleItemId,
            'LastRequestTime' => $lastRequestTime?->format(\DateTimeInterface::ATOM),
        ], static fn (mixed $value): bool => null !== $value && '' !== $value);

        $response = $this->get('/v3/UserHistory/GetHistory', $query);

        return GetUserHistoryResponse::fromArray($response);
    }

    public function getTransactionDetail(string $historyItemId): GetTransactionDetailResponse
    {
        $query = [
            'POSKey' => $this->posKey,
            'ItemId' => $historyItemId,
        ];

        $response = $this->get('/v3/TransactionHistory/GetDetail', $query);

        return GetTransactionDetailResponse::fromArray($response);
    }

    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>
     */
    private function get(string $path, array $query): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->resolveBaseUrl() . $path, [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                    'x-pos-key' => $this->posKey,
                ],
            ]);
        } catch (TransportExceptionInterface $exception) {
            throw new \RuntimeException('Barion wallet API request failed.', 0, $exception);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf('Barion wallet API returned HTTP %d.', $statusCode));
        }

        try {
            return $response->toArray(false);
        } catch (\Throwable) {
            throw new \RuntimeException('Barion wallet API returned invalid JSON.');
        }
    }

    private function resolveBaseUrl(): string
    {
        return match ($this->environment) {
            'prod', 'production' => self::API_URL_PROD,
            default => self::API_URL_TEST,
        };
    }
}
