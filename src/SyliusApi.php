<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway;

use Barion\BarionClient;
use Barion\Enumerations\BarionEnvironment;
use Barion\Enumerations\Currency;
use Barion\Enumerations\FundingSourceType;
use Barion\Enumerations\PaymentStatus;
use Barion\Enumerations\PaymentType;
use Barion\Enumerations\UILocale;
use Barion\Models\Common\ItemModel;
use Barion\Models\Error\ApiErrorModel;
use Barion\Models\Payment\CancelAuthorizationRequestModel;
use Barion\Models\Payment\CancelAuthorizationResponseModel;
use Barion\Models\Payment\CaptureRequestModel;
use Barion\Models\Payment\CaptureResponseModel;
use Barion\Models\Payment\FinishReservationRequestModel;
use Barion\Models\Payment\FinishReservationResponseModel;
use Barion\Models\Payment\PaymentStateResponseModel;
use Barion\Models\Payment\PaymentTransactionModel;
use Barion\Models\Payment\PreparePaymentRequestModel;
use Barion\Models\Payment\PreparePaymentResponseModel;
use Barion\Models\Payment\TransactionToCaptureModel;
use Barion\Models\Payment\TransactionToFinishModel;
use Barion\Models\Payment\TransactionToRefundModel;
use Barion\Models\Refund\RefundRequestModel;
use Barion\Models\Refund\RefundResponseModel;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Shipping\Model\ShippingMethodInterface;
use SyliusBarionPaymentGateway\Converter\AddressConverter;
use Symfony\Contracts\Translation\TranslatorInterface;

class SyliusApi
{
    private const DEFAULT_PAYMENT_WINDOW = '00:30:00';

    private const DEFAULT_DELAYED_CAPTURE_PERIOD = 'P7D';

    private const DEFAULT_RESERVATION_PERIOD = 'P7D';

    public function __construct(
        private readonly array $config,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getPosKey(): string
    {
        return $this->config['pos_key'] ?? '';
    }

    public function getPayee(): string
    {
        return $this->config['payee'] ?? '';
    }

    public function getEnvironment(): string
    {
        return $this->config['env'] ?? 'test';
    }

    public function getPaymentType(): PaymentType
    {
        return match ($this->config['payment_type'] ?? 'immediate') {
            'delayed_capture' => PaymentType::DelayedCapture,
            'reservation' => PaymentType::Reservation,
            default => PaymentType::Immediate,
        };
    }

    public function preparePayment(
        PaymentInterface $payment,
        float $total,
        string $redirectUrl,
        string $callbackUrl,
    ): PreparePaymentResponseModel {
        $order = $payment->getOrder();
        if (!$order instanceof OrderInterface) {
            throw new \InvalidArgumentException('Payment must belong to an order.');
        }

        $paymentType = $this->getPaymentType();
        $request = new PreparePaymentRequestModel(
            requestId: $this->buildPaymentRequestId($payment),
            paymentType: $paymentType,
            guestCheckoutAllowed: true,
            allowedFundingSources: $this->resolveFundingSources(),
            paymentWindow: $this->config['payment_window'] ?? self::DEFAULT_PAYMENT_WINDOW,
            locale: $this->resolveLocale($order->getLocaleCode()),
            initiateRecurrence: (bool) ($this->config['initiate_recurrence'] ?? false),
            recurrenceId: $this->config['recurrence_id'] ?? null,
            redirectUrl: $redirectUrl,
            callbackUrl: $callbackUrl,
            currency: $this->resolveCurrency($payment->getCurrencyCode()),
        );

        $request->OrderNumber = (string)$payment->getId();
        $request->PayerHint = $this->resolvePayerHint($order);
        $request->ShippingAddress = AddressConverter::toShippingAddress($order->getShippingAddress());
        $request->BillingAddress = AddressConverter::toBillingAddress($order->getBillingAddress() ?? $order->getShippingAddress());

        if (PaymentType::DelayedCapture === $paymentType) {
            $request->DelayedCapturePeriod = $this->config['delayed_capture_period'] ?? self::DEFAULT_DELAYED_CAPTURE_PERIOD;
        }

        if (PaymentType::Reservation === $paymentType) {
            $request->ReservationPeriod = $this->config['reservation_period'] ?? self::DEFAULT_RESERVATION_PERIOD;
        }

        $transaction = new PaymentTransactionModel();
        $transaction->POSTransactionId = (string)$payment->getId();
        $transaction->Payee = $this->getPayee();
        $transaction->Total = $total;
        $transaction->AddItems($this->buildOrderItems($order, $payment->getCurrencyCode()));

        $request->AddTransaction($transaction);

        $client = $this->createClient();
        $response = $client->PreparePayment($request);

        if (!$response->RequestSuccessful || PaymentStatus::Prepared !== $response->Status || empty($response->PaymentId)) {
            throw new \RuntimeException($this->formatErrors($response->Errors ?? []));
        }

        return $response;
    }

    public function getPaymentState(string $paymentId): PaymentStateResponseModel
    {
        $client = $this->createClient();
        $client->SetVersion(4);

        return $client->PaymentState($paymentId);
    }

    public function captureAuthorizedPayment(string $paymentId, array $transactions): CaptureResponseModel
    {
        $request = new CaptureRequestModel($paymentId);

        foreach ($transactions as $transaction) {
            $captureTransaction = new TransactionToCaptureModel();
            $captureTransaction->TransactionId = $transaction['transactionId'];
            $captureTransaction->Total = (float) $transaction['total'];
            $request->AddTransaction($captureTransaction);
        }

        return $this->createClient()->Capture($request);
    }

    public function cancelAuthorization(string $paymentId): CancelAuthorizationResponseModel
    {
        $request = new CancelAuthorizationRequestModel($paymentId);

        return $this->createClient()->CancelAuthorization($request);
    }

    public function finishReservation(string $paymentId, array $transactions): FinishReservationResponseModel
    {
        $request = new FinishReservationRequestModel($paymentId);

        foreach ($transactions as $transaction) {
            $finishTransaction = new TransactionToFinishModel();
            $finishTransaction->TransactionId = $transaction['transactionId'];
            $finishTransaction->Total = (float) $transaction['total'];
            $request->AddTransaction($finishTransaction);
        }

        return $this->createClient()->FinishReservation($request);
    }

    public function refundPayment(string $paymentId, string $transactionId, string $posTransactionId, float $amount, ?string $comment = null): RefundResponseModel
    {
        $request = new RefundRequestModel($paymentId);
        $request->AddTransaction(new TransactionToRefundModel($transactionId, $posTransactionId, $amount, $comment));

        return $this->createClient()->RefundPayment($request);
    }

    private function createClient(): BarionClient
    {
        return new BarionClient($this->getPosKey(), 2, $this->resolveEnvironment());
    }

    private function resolveEnvironment(): BarionEnvironment
    {
        return match ($this->getEnvironment()) {
            'prod', 'production' => BarionEnvironment::Prod,
            default => BarionEnvironment::Test,
        };
    }

    /**
     * @return array<FundingSourceType>
     */
    private function resolveFundingSources(): array
    {
        $configured = $this->config['funding_sources'] ?? ['All'];

        if (!is_array($configured) || [] === $configured) {
            return [FundingSourceType::All];
        }

        $sources = [];
        foreach ($configured as $source) {
            $sources[] = FundingSourceType::from($source);
        }

        return $sources;
    }

    private function resolveCurrency(?string $currencyCode): Currency
    {
        if (null === $currencyCode) {
            return Currency::HUF;
        }

        return Currency::tryFrom(strtoupper($currencyCode)) ?? Currency::HUF;
    }

    private function resolveLocale(?string $localeCode): UILocale
    {
        $normalized = str_replace('_', '-', strtolower($localeCode ?? ''));

        return match (true) {
            str_starts_with($normalized, 'hu') => UILocale::HU,
            str_starts_with($normalized, 'de') => UILocale::DE,
            str_starts_with($normalized, 'sk') => UILocale::SK,
            str_starts_with($normalized, 'sl') => UILocale::SL,
            str_starts_with($normalized, 'fr') => UILocale::FR,
            str_starts_with($normalized, 'cs'), str_starts_with($normalized, 'cz') => UILocale::CZ,
            str_starts_with($normalized, 'el'), str_starts_with($normalized, 'gr') => UILocale::GR,
            str_starts_with($normalized, 'es') => UILocale::ES,
            default => UILocale::EN,
        };
    }

    private function resolvePayerHint(OrderInterface $order): ?string
    {
        $customer = $order->getCustomer();
        if (null !== $customer && null !== $customer->getEmail()) {
            return $customer->getEmail();
        }

        $billingAddress = $order->getBillingAddress();
        if (null !== $billingAddress && method_exists($billingAddress, 'getEmail')) {
            $email = $billingAddress->getEmail();
            if (null !== $email) {
                return $email;
            }
        }

        return null;
    }

    /**
     * @return array<ItemModel>
     */
    private function buildOrderItems(OrderInterface $order, ?string $currencyCode): array
    {
        $locale = $order->getLocaleCode();
        $divisor = $this->currencyDivisor($currencyCode);
        $unitName = $this->translator->trans('sylius_barion.payment.unit.piece', [], 'messages', $locale);
        $items = [];

        foreach ($order->getItems() as $orderItem) {
            $item = new ItemModel();
            $item->Name = $orderItem->getProductName();
            $item->Description = $orderItem->getVariantName();
            $item->Quantity = (float) $orderItem->getQuantity();
            $item->Unit = $unitName;
            $item->UnitPrice = $orderItem->getUnitPrice() / $divisor;
            $item->ItemTotal = $orderItem->getTotal() / $divisor;
            $item->SKU = $orderItem->getVariant()?->getCode();

            $items[] = $item;
        }

        $shippingTotal = $order->getShippingTotal();
        if ($shippingTotal > 0) {
            $shippingItem = new ItemModel();
            $shippingItem->Name = $this->translator->trans('sylius.ui.shipping', [], 'messages', $locale);
            $shippingItem->Description = $this->resolveShippingMethodName($order);
            $shippingItem->Quantity = 1.0;
            $shippingItem->Unit = $unitName;
            $shippingItem->UnitPrice = $shippingTotal / $divisor;
            $shippingItem->ItemTotal = $shippingTotal / $divisor;
            $items[] = $shippingItem;
        }

        return $items;
    }

    private function resolveShippingMethodName(OrderInterface $order): ?string
    {
        $shipment = $order->getShipments()->first();
        if (!$shipment instanceof ShipmentInterface) {
            return null;
        }

        $shippingMethod = $shipment->getMethod();
        if (!$shippingMethod instanceof ShippingMethodInterface) {
            return null;
        }

        $locale = $order->getLocaleCode();
        if (null !== $locale) {
            return $shippingMethod->getTranslation($locale)->getName();
        }

        return $shippingMethod->getName();
    }

    private function buildPaymentRequestId(PaymentInterface $payment): string
    {
        return sprintf('%s-%s', $payment->getId(), date('YmdHis'));
    }

    private function currencyDivisor(?string $currencyCode): float
    {
        return match (strtoupper($currencyCode ?? '')) {
            'HUF', 'JPY' => 1.0,
            default => 100.0,
        };
    }

    /**
     * @param array<mixed> $errors
     */
    private function formatErrors(array $errors): string
    {
        if ([] === $errors) {
            return 'Barion payment preparation failed.';
        }

        $messages = [];
        foreach ($errors as $error) {
            if ($error instanceof ApiErrorModel) {
                $messages[] = sprintf('[%s] %s: %s', $error->ErrorCode, $error->Title ?? '', $error->Description ?? '');
            }
        }

        return [] === $messages ? 'Barion payment preparation failed.' : implode('; ', $messages);
    }
}
