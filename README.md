# PSR-15 ab testing middleware

This PSR-15 middleware allows you to do A/B testing on your application.

Cookies are used to keep track of a randomly generated user-id. Based on this cookie, a variant is selected for an experiment.

The Twig helper can be used to conditionally render the experiment template variants.

Although more than one experiment can be defined, only one experiment is supposed to be active at a time.

## Installation

```bash
composer require dmt-software/ab-middleware
```

## Usage

```php
use DMT\AbMiddleware\AbService;
use DMT\AbMiddleware\AbPsrMiddleware;

// define your experiments
$experiments = [
    'active-experiment' => [
        'variant-1' => 0.3,
        'variant-2' => 0.3,
        'control' => 0.4,
    ],
    'old-experiment' => [
        'variant-1' => 0.3,
        'variant-2' => 0.3,
        'control' => 0.4,
    ],
];

// instantiate the service
$service = new AbService($experiments);

// instantiate the middleware
$abMiddleware = new AbPsrMiddleware($service, 'ab-uid-cookie-name');

// add the middleware to your middleware stack
```
