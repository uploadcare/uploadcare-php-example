Example project for Uploadcare-php client
=========================================

This is example project for demonstration of [uploadcare-php](https://github.com/uploadcare/uploadcare-php) possibilities.

An example project based on Symfony Framework, but the library itself can be used in any PHP environment. 

## Table of contents

- [Demo-project installation](#install-demo-project)
- [Container initialization](#container-initialization)
- [Direct initialization](#direct-initialization)
- [Project info](#project-info)
- [File operations](#file-operations)
    - [Upload file](#upload-file)
    - [Get files list and file info](#files-and-file-info)
    - [Example of store file](#store-file)
    - [Example of delete file](#delete-file)
    - [Batch store files](#batch-store-files)
    - [Batch delete files](#batch-delete-files)
- [Group operations](#group-operations)
    - [Create group of files](#create-group)
- [Conversion operations](#conversion-operations)
    - [Convert document](#convert-document)
    - [Get document conversion job status](#document-conversion-status)
    - [Convert video](#convert-video)
    - [Video conversion job status](#video-conversion-status)
- [Webhook operations](#webhook-operations)
    - [List of project webhooks](#list-of-webhooks)
    - [Create webhook](#create-webhook)
    - [Update webhook](#update-webhook)
    - [Delete webhook](#delete-webhook)

## Usage with Docker

You can use this project as demo with Docker. For do this, clone this repository, build Docker image like

```shell script
docker build -t uploadcare-example-project -f Dockerfile .
```

then, run the image like

```shell script
docker run -it --rm -p 8000:8000 -e UPLOADCARE_PUBLIC_KEY=<your public key> -e UPLOADCARE_PRIVATE_KEY=<your provate key> uploadcare-example-project sh
```

In container shell you shold install the composer packages (`composer install`) and run simple dev-server: `php -S 0.0.0.0:8000 public/index.php`

After that, you can see the web-interface of project in your browser on `http://localhost:8000`.

## Install demo-project

Requirements:

- php7.4
- ext-ctype
- ext-iconv
- ext-curl

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

## Common information

All web-examples accessible with development web-server by [Symfony CLI](https://symfony.com/download).

## Project info

You can retrieve your information of project by its public key.

[Console example](src/Command/ProjectInfoCommand.php), [Web example](src/Controller/ProjectInfoController.php). Web example is accessible by `/` (root) route.

![Project Info](references/project-info.png)

## File operations

### Upload file

### Console usage example

See `App\Command\UploadFileCommand` (`src/Command/UploadFileCommand.php`);

There is a different ways for upload files — from file path, from resource, created by `\fopen()` function of `SplFileObject` implementation, from remote url or from string contents.

All those ways implemented in example command.

Run command as `bin/console app:upload-file /path/to/file`.

See `app:upload-file --help` for information and run examples.

### Web usage example

See `App\Controller\UploadController` for controller example and `templates/upload/index.html.twig` for template markup.

In this example you can see the way to organize upload operations for user web-interface. Most of web-services restricts the upload size, and this is a simple example with one-piece uploading with size restricted by the server. 

Steps to run example:

- open `https://localhost:8000/upload` page in your preferred browser;
- upload file and see the result

### Files and file info

If you have any files in your project, you can see your files in `/file-list` route or with `app:file-info [file-id]` command. Examples in `src/Command/FileInfoCommand.php` (console) and `src/Controller/FileInfoController.php` (web).

![File list](references/file-list.png)

## Store file

This option has a sense when You upload file with `0` option in "Store" field. Anyway, you can call `store` method on any file. Example in `StoreFileController`.

## Delete file

You can delete any of your files from `file-info` screen. Example in `DeleteFileController`.

## Batch store files

You can apply the "Store" operation for all (or part) of your files. See `/batch-store` route and `BatchStoreController` for examples.

## Batch delete files

You can delete all or part of you files with "Batch delete" operation. See `/batch-delete` route and `BatchDeleteController` for examples.

## Group operations

You can see your groups of files in `/groups` route ("Group list" menu item). You can go deeper and see the group info by a click to group ID in list. See example in `GroupController::index` and `GroupController::info`.

## Create group

On `/group-create` route ("Create group" menu item) you can see your file list, select any number of files and create group from these files. Example in `GroupController::createGroup`.

![Create group](references/create-group.png)

After the group creation your browser will redirect to group-info page.

## Conversion operations

Uploadcare API provides possibilities to convert documents (images, PDF's and other) and video-files to various formats. See [Document Conversion](https://uploadcare.com/docs/transformations/document_conversion/) and [Video Encoding](https://uploadcare.com/docs/transformations/video_encoding/) documentation.

In this library, you should not create the conversion urls by hands — you can create the special object for each (document or video) conversion and use it through library API. 

### Convert document

For convert document from one format to another, you should create the `Uploadcare\Conversion\DocumentConversionRequest` and set target data to it. For example:

```php
$configuration = \Uploadcare\Configuration::create($_ENV['UPLOADCARE_PUBLIC_KEY'], $_ENV['UPLOADCARE_PRIVATE_KEY']);
$api = new \Uploadcare\Api($configuration);
$dcr = (new Uploadcare\Conversion\DocumentConversionRequest())
    ->setTargetFormat('png')
    ->setStore(true)
    ->setThrowError(false)
    ->setPageNumber(2);
$file = $api->file()->fileInfo('1822793d-5cdb-418e-8545-6bd6a5dd74bd');

$result = $api->conversion()->convertDocument($file, $dcr);
```

In this case, we take a PDF file and convert it's second page to PNG image. `$result` variable will contain `Uploadcare\Interfaces\Conversion\ConvertedItemInterface` or `Uploadcare\Interfaces\Response\ResponseProblemInterface` (if conversion is not possible, or you account cannot request conversions).

`ConvertedItemInterface` contains UUID of converted document and token, that can us in conversion status request. 

If you pass `true` to `setThrowError` method of `DocumentConversionRequest`, any conversion problem will throw `Uploadcare\Exception\ConversionException`, otherwise, the response will contain `ResponseProblemInterface`

Examples are accessible on `/convert-document` ("Convert Document" menu item), code examples in `DocumentConversionController`

### Document Conversion Status

After successfully document conversion, you will redirect to Document conversion status page

![Document conversion status](references/document-conversion-status.png)

See code example in `DocumentConversionController::conversionResult`.

### Convert video

For video conversion request you should make the `Uploadcare\Conversion\VideoEncodingRequest` and add request data to it. See [Video Encoding](https://uploadcare.com/docs/transformations/video_encoding/) documentation for common description and `Uploadcare\Interfaces\Conversion\VideoEncodingRequestInterface` for library API options.

For example, you can request convert a video file to `webm` format, resize it to horizontal 720px with preserve ratio, make it small and store it:

```php
$configuration = \Uploadcare\Configuration::create($_ENV['UPLOADCARE_PUBLIC_KEY'], $_ENV['UPLOADCARE_PRIVATE_KEY']);
$api = new \Uploadcare\Api($configuration);
$ver = (new \Uploadcare\Conversion\VideoEncodingRequest())
    ->setTargetFormat('webm')
    ->setHorizontalSize(720)
    ->setResizeMode('preserve_ratio')
    ->setQuality('lightest')
    ->setStore(true);
$file = $api->file()->fileInfo('f24a5fdd-8318-4b3f-a77e-00be2ec47576');

$result = $api->conversion()->convertVideo($file, $ver);
```

You can also set start and end time for video in `H:MM:SS.sss` or `MM:SS.sss` formats.

`$result` variable (as in document conversion case) will contain `ConvertedItemInterface` or `ResponseProblemInterface`.

You can see how it works by going to any video-file info in web-interface and click "Request video conversion" button.

![Video conversion request](references/video-info.png)

See code examples in `VideoConversionController::conversionRequest`.

### Video Conversion Status

After successfully conversion your browser will redirect to Video conversion status page. This page looks like Document conversion status, because of the conversion status object implements the same interface.

## Webhook operations

Uploadcare [provides a possibility](https://uploadcare.com/docs/rest_api/webhooks/) to call any URL by the file uploading (webhooks). The library API provides methods to create, update and delete webhooks.

### List Of Webhooks

You can see the list of project webhooks by the `/webhooks` route ("List of project webhooks" menu item). You can call method `$api->webhook()->listWebhooks()` to get this list (`CollectionInterface` will be returned). 

See code examples in `WebhooksController::index`.

### Create Webhook

Webhook creation runs from `/webhook-create` route ("Create new webhook" menu item). You must set the target url for webhook.

See code examples in `WebhooksController::createWebhook`

### Update Webhook

You can click on "Update" button in webhooks list and get the update form. You can change URL or activity of webhook in this form. See code examples in `WebhooksController::updateWebhook`.

![Webhook update form](references/webhook-info.png)
    
### Delete Webhook

You can delete webhook from webhooks list. Code examples in `WebhooksController::deleteWebhook`.
