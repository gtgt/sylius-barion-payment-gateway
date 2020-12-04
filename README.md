<h1 align="center">Sylius Barion Payment Gateway Plugin</h1>

## Quickstart Installation

1. Add to composer.json file:

```json
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "barion/barion-web-php",
                "version": "1.4.2",
                "dist": {
                    "url": "https://github.com/barion/barion-web-php/archive/v1.4.2.zip",
                    "type": "zip"
                },
                "source": {
                    "url": "https://github.com/barion/barion-web-php.git",
                    "type": "git",
                    "reference": "v1.4.2"
                },
                "autoload": {
                    "classmap": [ "library/" ],
                    "files": [
                        "library/common/Constants.php",
                        "library/BarionClient.php"
                    ]
                }
            }
        }
    ],
```

2. Run `composer req goncziakos/sylius-barion-payment-gateway`

3. Add new Barion payment on admin
