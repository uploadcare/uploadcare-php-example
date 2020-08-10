Example project for Uploadcare-php client
=========================================

This is example project for demonstration of [uploadcare-php](https://github.com/uploadcare/uploadcare-php) possibilities.

An example project based on Symfony Framework, but the library itself can be used in any PHP environment. 

## Table of contents

- [Demo-project installation](#install-demo-project)
- [Container initialization](#container-initialization)
- [Direct initialization](#direct-initialization)
- [Console usage](#console-usage)
    - [Upload file from console](#upload-file-from-console)
    - [Get files list and file info](#files-and-file-info)

## Install demo-project

Requirements:

- php7.4
- ext-ctype
- ext-iconv
- ext-curl

### Installation

- Clone this repository;
- Run `composer install` from project root;
- See examples

## Container initialization

### Symfony-container example:

```yaml
parameters:
    uploadcare_public_key: '%env(UPLOADCARE_PUBLIC_KEY)%'
    uploadcare_private_key: '%env(UPLOADCARE_PRIVATE_KEY)%'

services:
    Uploadcare\Interfaces\ConfigurationInterface:
        class: Uploadcare\Configuration
        factory: ['Uploadcare\Configuration', 'create']
        arguments: ['%uploadcare_public_key%', '%uploadcare_private_key%']

    uploadcare.configuration:
        alias: 'Uploadcare\Interfaces\ConfigurationInterface'

    Uploadcare\Api:
        arguments:
            - '@uploadcare.configuration'
```

See working example in [`config/services.yaml`](config/services.yaml)

### Laravel-container example

```php
// config/uploadcare.php
return [
    'uploadcare_public_key' => env('UPLOADCARE_PUBLIC_KEY'),
    'uploadcare_private_key' => env('UPLOADCARE_PRIVATE_KEY'),
];
```

```php
// app/providers/UploadcareServiceProvider.php
namespace App\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Uploadcare\Api;
use Uploadcare\Configuration;
use Uploadcare\Interfaces\ConfigurationInterface;

class UploadcareProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ConfigurationInterface::class, function () {
            return Configuration::create(config('uploadcare.public_key'), config('uploadcare.private_key'));
        });

        $this->app->bind(Api::class, function (Application $app) {
            return new Api($app->get(ConfigurationInterface::class));
        });
    }
}
```

## Direct initialization

Define variables or constants with Uploadcare public and private keys:

```php
(new \Symfony\Component\Dotenv\Dotenv())->bootEnv(__DIR__ . '/.env');

// Or something like that
defined('UPLOADCARE_PUBLIC_KEY') or define('UPLOADCARE_PUBLIC_KEY', '<Your public key>');
defined('UPLOADCARE_PRIVATE_KEY') or define('UPLOADCARE_PRIVATE_KEY', '<Your private key>');
```

Make configuration object and API instance:

```php
$configuration = \Uploadcare\Configuration::create($_ENV['UPLOADCARE_PUBLIC_KEY'], $_ENV['UPLOADCARE_PRIVATE_KEY']);
$api = new \Uploadcare\Api($configuration);
```

Or make API instance with factory:

```php
$api = \Uploadcare\Api::create($_ENV['UPLOADCARE_PUBLIC_KEY'], $_ENV['UPLOADCARE_PRIVATE_KEY']);
```

## Console usage

### Upload file from console

See `App\Command\UploadFileCommand` (`src/Command/UploadFileCommand.php`);

There is a different ways for upload files — from file path, from resource, created by `\fopen()` function of `SplFileObject` implementation, from remote url or from string contents.

All those ways implemented in example command.

Run command as `bin/console app:upload-file /path/to/file`.

See `app:upload-file --help` for information and run examples.
