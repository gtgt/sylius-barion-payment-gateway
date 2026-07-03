<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Action;

use ArrayObject;
use Barion\Enumerations\PaymentStatus;
use Barion\Models\Payment\PaymentStateResponseModel;
use Barion\Models\Payment\PreparePaymentResponseModel;
use Barion\Models\Payment\TransactionDetailModel;
use Barion\Models\Payment\TransactionResponseModel;
use SyliusBarionPaymentGateway\Model\BarionPaymentStatus;

final class BarionStatusMapper
{
    public static function mapPaymentStatus(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Prepared,
            PaymentStatus::Started,
            PaymentStatus::InProgress,
            PaymentStatus::Waiting => BarionPaymentStatus::PENDING,
            PaymentStatus::Succeeded => BarionPaymentStatus::CAPTURED,
            PaymentStatus::Authorized => BarionPaymentStatus::AUTHORIZED,
            PaymentStatus::Failed => BarionPaymentStatus::FAILED,
            PaymentStatus::Canceled => BarionPaymentStatus::CANCELED,
            PaymentStatus::Expired => BarionPaymentStatus::EXPIRED,
            PaymentStatus::Reserved => BarionPaymentStatus::RESERVED,
            PaymentStatus::PartiallySucceeded => BarionPaymentStatus::PARTIAL,
            default => BarionPaymentStatus::UNKNOWN,
        };
    }

    public static function applyPrepareResponse(ArrayObject $details, PreparePaymentResponseModel $response): void
    {
        $details['paymentId'] = $response->PaymentId;
        $details['paymentRequestId'] = $response->PaymentRequestId;
        $details['paymentUrl'] = $response->PaymentRedirectUrl;
        $details['barionStatus'] = $response->Status->value;
        $details['status'] = self::mapPaymentStatus($response->Status);
        $details['traceId'] = $response->TraceId;

        self::storeTransactionIds($details, $response->Transactions);
    }

    public static function applyPaymentState(ArrayObject $details, PaymentStateResponseModel $response): void
    {
        $details['paymentId'] = $response->PaymentId ?? $details['paymentId'] ?? null;
        $details['paymentRequestId'] = $response->PaymentRequestId ?? $details['paymentRequestId'] ?? null;
        $details['barionStatus'] = $response->Status->value;
        $details['status'] = self::mapPaymentStatus($response->Status);
        $details['fundingSource'] = $response->FundingSource;
        $details['paymentMethod'] = $response->PaymentMethod;
        $details['fraudRiskScore'] = $response->FraudRiskScore;
        $details['traceId'] = $response->TraceId;
        $details['completedAt'] = $response->CompletedAt;
        $details['paymentType'] = $response->PaymentType->value;

        self::storeTransactionIds($details, $response->Transactions);
    }

    /**
     * @param array<object> $transactions
     */
    private static function storeTransactionIds(ArrayObject $details, array $transactions): void
    {
        $transactionIds = [];

        foreach ($transactions as $transaction) {
            if ($transaction instanceof TransactionDetailModel || $transaction instanceof TransactionResponseModel) {
                if (!empty($transaction->TransactionId) && !empty($transaction->POSTransactionId)) {
                    $transactionIds[] = [
                        'transactionId' => $transaction->TransactionId,
                        'posTransactionId' => $transaction->POSTransactionId,
                    ];
                }
            }
        }

        if ([] !== $transactionIds) {
            $details['transactions'] = $transactionIds;
        }
    }
}
