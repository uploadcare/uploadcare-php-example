<?php

declare(strict_types=1);

namespace App\Tests\Core;

use App\Controller\BatchDeleteController;
use App\Controller\BatchStoreController;
use App\Controller\DeleteFileController;
use App\Controller\DocumentConversionController;
use App\Controller\FileInfoController;
use App\Controller\GroupController;
use App\Controller\StoreFileController;
use App\Controller\UploadController;
use App\Controller\VideoConversionController;
use App\Controller\WebhooksController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Uploadcare\Api;

class ControllerServiceInjectionTest extends KernelTestCase
{
    public function provideControllerClasses(): array
    {
        return [
            [BatchDeleteController::class],
            [BatchStoreController::class],
            [DeleteFileController::class],
            [DocumentConversionController::class],
            [FileInfoController::class],
            [GroupController::class],
            [StoreFileController::class],
            [UploadController::class],
            [VideoConversionController::class],
            [WebhooksController::class],
        ];
    }

    /**
     * @dataProvider provideControllerClasses
     *
     * @throws \ReflectionException
     */
    public function testAnyController(string $controllerClass): void
    {
        $command = self::getContainer()->get($controllerClass);
        $reflection = (new \ReflectionObject($command));
        $api = $reflection->getProperty('api');

        self::assertInstanceOf(Api::class, $api->getValue($command));
    }
}
