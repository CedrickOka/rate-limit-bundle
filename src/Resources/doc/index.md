# Getting Started With OkaRateLimitBundle

This bundle provides request rate limit.

## Prerequisites

The OkaRateLimitBundle has the following requirements:

 - PHP 7.2+
 - Symfony 3.4+

## Installation

Installation is a quick (I promise!) 3 step process:

1. Download OkaRateLimitBundle
2. Register the Bundle
3. Configure the Bundle

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require coka/rate-limit-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Register the Bundle

**Symfony 3 Version**

Then, register the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Oka\RateLimitBundle\OkaRateLimitBundle(),
        ];

        // ...
    }

    // ...
}
```

**Symfony 4 Version**

Then, register the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project (Flex did it automatically):

```php
return [
    //...
    Oka\RateLimitBundle\OkaRateLimitBundle::class => ['all' => true],
]
```

### Step 3: Configure the Bundle

Add the following configuration to your `config/packages/oka_rate_limit.yaml`.

``` yaml
# config/packages/oka_rate_limit.yaml
oka_rate_limit:
    cache_pool_id: cache.app #Indicate the cache pool of your choice here
    configs:
        - { path: '.*' }
```
