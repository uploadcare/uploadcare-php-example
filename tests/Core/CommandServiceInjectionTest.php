<?php declare(strict_types=1);

namespace App\Tests\Core;

use App\Command\{FileInfoCommand, ProjectInfoCommand};
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Uploadcare\Api;

class CommandServiceInjectionTest extends KernelTestCase
{
    public function provideCommandClasses(): array
    {
        return [
            [FileInfoCommand::class],
            [ProjectInfoCommand::class],
        ];
    }

    /**
     * @dataProvider provideCommandClasses
     *
     * @throws \ReflectionException
     */
    public function testAnyCommand(string $commandClass): void
    {
        self::bootKernel();
        $command = self::$container->get($commandClass);
        $reflection = (new \ReflectionObject($command));
        $api = $reflection->getProperty('api');
        $api->setAccessible(true);

        self::assertInstanceOf(Api::class, $api->getValue($command));
    }
}
