<?php declare(strict_types=1);

namespace App\Tests\Core;

use App\Controller\{
    BatchDeleteController,
    BatchStoreController,
    DeleteFileController,
    DocumentConversionController,
    FileInfoController,
    GroupController,
    StoreFileController,
    UploadController,
    VideoConversionController,
    WebhooksController
};
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
     * @param string $controllerClass
     *
     * @throws \ReflectionException
     */
    public function testAnyController(string $controllerClass): void
    {
        self::bootKernel();
        $command = self::$container->get($controllerClass);
        $reflection = (new \ReflectionObject($command));
        $api = $reflection->getProperty('api');
        $api->setAccessible(true);

        self::assertInstanceOf(Api::class, $api->getValue($command));
    }
}
