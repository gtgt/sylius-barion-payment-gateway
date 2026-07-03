<h1 align="center">Sylius Barion Payment Gateway Plugin</h1>

Sylius 2.x payment gateway for [Barion](https://www.barion.com) using `barion/barion-web-php` 2.x.

## Features

- Sylius 2 Payum actions (`Capture`, `Status`, `Notify`, `Refund`, `Cancel`)
- Barion API v2 prepare + PaymentState v4 polling
- 3DS address enrichment from Sylius order addresses
- Configurable funding sources and payment types (immediate, delayed capture, reservation)
- Optional recurring payment initiation
- Sylius 2 PaymentRequest notify provider (`/payment-methods/{code}`)
- Admin Barion transaction history (shop payments grid + wallet ledger)

## Installation

### 1. Require the package

Add the VCS repository (until the package is on Packagist) and install:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/gtgt/sylius-barion-payment-gateway.git"
        }
    ]
}
```

```bash
composer require gtgt/sylius-barion-payment-gateway
```

### 2. Symfony Flex recipe (routes and bundle)

The plugin ships a Flex recipe that:

- registers `SyliusBarionPaymentGatewayPlugin` in `config/bundles.php`
- copies `config/routes/sylius_barion.yaml` into the application

**Official recipe (recommended once merged):** after the recipe is accepted into
[symfony/recipes-contrib](https://github.com/symfony/recipes-contrib), Flex applies it
automatically on `composer require` with no extra steps.

**Until the contrib recipe is merged**, point Flex at the plugin `flex-recipe` branch by
adding this to your **application** `composer.json` (merge into existing `extra.symfony` if present):

```json
{
    "extra": {
        "symfony": {
            "endpoint": [
                "https://raw.githubusercontent.com/gtgt/sylius-barion-payment-gateway/flex-recipe/index.json",
                "flex://defaults"
            ]
        }
    }
}
```

Then reinstall the package so Flex can apply the recipe:

```bash
composer recipes:install gtgt/sylius-barion-payment-gateway --force
```

Or:

```bash
composer update gtgt/sylius-barion-payment-gateway
```

### 3. Manual fallback (without Flex)

If you cannot use Flex recipes, copy the route import file manually:

```bash
cp vendor/gtgt/sylius-barion-payment-gateway/config/install/routes/sylius_barion.yaml config/routes/sylius_barion.yaml
```

Ensure the bundle is enabled in `config/bundles.php`:

```php
SyliusBarionPaymentGateway\SyliusBarionPaymentGatewayPlugin::class => ['all' => true],
```

### 4. Configure a Barion payment method

In the Sylius admin, create a payment method using the **Barion** gateway and fill in your POS key and environment.

## Flex recipe maintenance

| Path | Purpose |
|------|---------|
| `gtgt/sylius-barion-payment-gateway/2.0/manifest.json` | Source recipe (submit to `symfony/recipes-contrib`) |
| `config/install/routes/sylius_barion.yaml` | Route file copied into consuming apps |
| `flex-recipe/` | Compiled Flex endpoint files for the `flex-recipe` git branch |

To refresh compiled Flex files after changing the manifest:

```bash
git ls-tree HEAD gtgt/sylius-barion-payment-gateway/2.0 \
  | php /path/to/recipes-checker/run generate:flex-endpoint \
      gtgt/sylius-barion-payment-gateway main flex-recipe flex-recipe/compiled
cp flex-recipe/compiled/index.json flex-recipe/index.json
cp flex-recipe/compiled/gtgt.sylius-barion-payment-gateway.2.0.json flex-recipe/
```

Publish the contents of `flex-recipe/` to the `flex-recipe` branch of the plugin repository.
