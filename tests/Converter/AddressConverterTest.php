<?php

declare(strict_types=1);

namespace Tests\SyliusBarionPaymentGateway\Converter;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AddressInterface;
use SyliusBarionPaymentGateway\Converter\AddressConverter;

final class AddressConverterTest extends TestCase
{
    public function test_it_builds_shipping_address_from_sylius_address(): void
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getCountryCode')->willReturn('hu');
        $address->method('getCity')->willReturn('Budapest');
        $address->method('getPostcode')->willReturn('1011');
        $address->method('getStreet')->willReturn('Example street 1');
        $address->method('getFirstName')->willReturn('John');
        $address->method('getLastName')->willReturn('Doe');

        $model = AddressConverter::toShippingAddress($address);

        self::assertNotNull($model);
        self::assertSame('HU', $model->Country);
        self::assertSame('Budapest', $model->City);
        self::assertSame('1011', $model->Zip);
        self::assertSame('Example street 1', $model->Street);
        self::assertSame('John Doe', $model->FullName);
    }
}
