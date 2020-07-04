<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Unit;

use PHPUnit\Framework\TestCase;
use Mqwerty\ServiceManager\Manager;
use Mqwerty\ServiceManager\NotFoundException;
use Psr\Container\ContainerInterface;

final class ServiceManagerTest extends TestCase
{
    public function testConstructor(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new Manager());
    }

    public function testGetNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $manager = new Manager();
        $manager->get('notFound');
    }

    public function testHasNotFound(): void
    {
        $manager = new Manager();
        $this->assertFalse($manager->has('notFound'));
    }

    public function testHasFoundString(): void
    {
        $config = ['foo' => 'bar'];
        $manager = new Manager($config);
        $this->assertTrue($manager->has('foo'));
    }

    public function testConfig(): void
    {
        $config = ['foo' => 'bar'];
        $manager = new Manager($config);
        $this->assertSame($config['foo'], $manager->get('foo'));
    }

    public function testShared(): void
    {
        $config = [
            'shared' => [Foo::class],
            Foo::class => fn() => new Foo(),
        ];
        $manager = new Manager($config);
        $this->assertSame($manager->get(Foo::class), $manager->get(Foo::class));
    }

    public function testNotShared(): void
    {
        $config = [
            Foo::class => fn() => new Foo(),
        ];
        $manager = new Manager($config);
        $this->assertNotSame($manager->get(Foo::class), $manager->get(Foo::class));
    }

    public function testDI(): void
    {
        $manager = new Manager(
            [
                SomeInterface::class => fn() => new class implements SomeInterface {
                },
            ]
        );
        $this->assertInstanceOf(Baz::class, $manager->get(Baz::class));
    }

    public function testDIException(): void
    {
        $this->expectException(NotFoundException::class);
        $manager = new Manager();
        $manager->get(Qux::class);
    }
}

interface SomeInterface
{
}

class Foo
{
}

class Bar
{
}

class Baz
{
    public function __construct(SomeInterface $foo, Bar $bar, ?Qux $optional = null)
    {
    }
}

class Qux
{
    public function __construct(string $s)
    {
    }
}
