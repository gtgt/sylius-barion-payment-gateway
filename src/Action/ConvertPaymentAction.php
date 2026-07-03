<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface as PayumPaymentInterface;
use Payum\Core\Request\Convert;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use SyliusBarionPaymentGateway\Model\BarionPaymentStatus;

final class ConvertPaymentAction implements ActionInterface
{
    /**
     * @param Convert $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($this->resolveDetails($request->getSource()));
        $details->defaults([
            'status' => BarionPaymentStatus::NEW,
        ]);

        $request->setResult($details->getArrayCopy());
    }

    public function supports($request): bool
    {
        return
            $request instanceof Convert &&
            (
                $request->getSource() instanceof SyliusPaymentInterface
                || $request->getSource() instanceof PayumPaymentInterface
            ) &&
            'array' === $request->getTo()
        ;
    }

    private function resolveDetails(mixed $source): array
    {
        if ($source instanceof SyliusPaymentInterface) {
            $details = $source->getDetails();

            return is_array($details) ? $details : [];
        }

        if ($source instanceof PayumPaymentInterface) {
            $details = $source->getDetails();

            return is_array($details) ? $details : [];
        }

        return [];
    }
}
