Bank-ID
=======

Library for connect Swedish BankID to your application.

[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/puggan/bank-id.svg?branch=dev)](https://travis-ci.org/puggan/bank-id)

## Requirements

* PHP 5.5+ (tested from 5.5 to 7.3-dev)
    * <PHP 8.0 due to composer dependensies
* [curl](http://php.net/manual/en/book.curl.php)

## Install

Via Composer

Not an offical package, so add repo:
https://github.com/SpiroAB/composer-repo

``` bash
$ composer require puggan/bank-id
```

## Usage

```php
<?php
use Puggan\BankID\Service\BankIDService;
use Puggan\BankID\Model\CollectResponse;

// Create BankIDService (for test server)
$bankIDService = new BankIDService(
    'https://appapi2.test.bankid.com/rp/v5',
    $_SERVER['REMOTE_ADDR'],
    [
        'cafile' => 'PATH_TO_CAFILE.pem',
        'local_cert' => 'PATH_TO_TEST_CERT.pem',
        // 'local_pk' => '', // if key not included in cert file
    ]
);

// Signing. Step 1 - Get orderRef
$response = $bankIDService->getSignResponse('PERSONAL_NUMBER', 'Test user data');

// Signing. Step 2 - Collect orderRef. 
// Repeat until $collectResponse->status == CollectResponse::STATUS_V5_COMPLETED
$collectResponse = $bankIDService->collectResponse($response->orderRef);
if($collectResponse->status === CollectResponse::STATUS_V5_COMPLETED) {
    return true; //Signed successfully
}

// Authorize. Step 1 - Get orderRef
$response = $bankIDService->getAuthResponse('PERSONAL_NUMBER');

// Authorize. Step 2 - Collect orderRef. 
// Repeat until $authResponse->progressStatus == CollectResponse::PROGRESS_STATUS_COMPLETE
$authResponse = $bankIDService->collectResponse($response->orderRef);
if($collectResponse->status === CollectResponse::STATUS_V5_COMPLETED) {
    return true; //Signed successfully
}
```

## Testing

1. Execute

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
