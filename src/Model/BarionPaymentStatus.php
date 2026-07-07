<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Model;

class BarionPaymentStatus
{
    public const NEW = 'new';

    public const PENDING = 'pending';

    public const CAPTURED = 'captured';

    public const AUTHORIZED = 'authorized';

    public const RESERVED = 'reserved';

    public const FAILED = 'failed';

    public const CANCELED = 'canceled';

    public const EXPIRED = 'expired';

    public const PARTIAL = 'partial';

    public const UNKNOWN = 'unknown';

    private function __construct()
    {
    }
}
