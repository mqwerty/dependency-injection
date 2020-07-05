[![Test](https://github.com/mqwerty/service-manager/workflows/Test/badge.svg)](https://github.com/mqwerty/service-manager/actions?query=workflow%3ATest)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=mqwerty_dependency-injection&metric=alert_status)](https://sonarcloud.io/dashboard?id=mqwerty_dependency-injection)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=mqwerty_dependency-injection&metric=coverage)](https://sonarcloud.io/dashboard?id=mqwerty_dependency-injection)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/mqwerty/dependency-injection)](https://php.net/)
[![Latest Stable Version](https://img.shields.io/packagist/v/mqwerty/dependency-injection)](https://packagist.org/packages/mqwerty/dependency-injection)

Simple PSR-11 DI container with autowiring

```php
<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Foo {
    public function __construct(LoggerInterface $logger)
    {
    }
}

$config = [
    'logLevel' => 'info',
    'shared' => [LoggerInterface::class],
    LoggerInterface::class => static function ($c) {
        $handler = new StreamHandler(STDERR, $c->get('logLevel'));
        return (new Logger('log'))->pushHandler($handler);
    },
];

$container = new Mqwerty\DI\Container($config);
$container->get(Foo::class);
```
