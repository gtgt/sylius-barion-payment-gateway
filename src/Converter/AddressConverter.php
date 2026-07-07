<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Converter;

use Barion\Models\ThreeDSecure\BillingAddressModel;
use Barion\Models\ThreeDSecure\ShippingAddressModel;
use Sylius\Component\Core\Model\AddressInterface;

class AddressConverter
{
    public static function toShippingAddress(?AddressInterface $address): ?ShippingAddressModel
    {
        if (null === $address) {
            return null;
        }

        $model = new ShippingAddressModel();
        $model->Country = self::countryCode($address);
        $model->City = $address->getCity();
        $model->Zip = $address->getPostcode();
        $model->Street = $address->getStreet();
        $model->FullName = trim(sprintf('%s %s', $address->getFirstName() ?? '', $address->getLastName() ?? ''));

        return $model;
    }

    public static function toBillingAddress(?AddressInterface $address): ?BillingAddressModel
    {
        if (null === $address) {
            return null;
        }

        $model = new BillingAddressModel();
        $model->Country = self::countryCode($address);
        $model->City = $address->getCity();
        $model->Zip = $address->getPostcode();
        $model->Street = $address->getStreet();

        return $model;
    }

    private static function countryCode(AddressInterface $address): ?string
    {
        $country = $address->getCountryCode();

        return $country ? strtoupper($country) : null;
    }
}
