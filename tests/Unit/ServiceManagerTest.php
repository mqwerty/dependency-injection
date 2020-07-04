<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Unit;

use PHPUnit\Framework\TestCase;
use Mqwerty\DI\Container;
use Mqwerty\DI\NotFoundException;
use Psr\Container\ContainerInterface;

final class ServiceManagerTest extends TestCase
{
    public function testConstructor(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new Container([]));
    }

    public function testGetNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $c = new Container([]);
        $c->get('notFound');
    }

    public function testHasNotFound(): void
    {
        $c = new Container([]);
        $this->assertFalse($c->has('notFound'));
    }

    public function testHasFoundString(): void
    {
        $config = ['foo' => 'bar'];
        $c = new Container($config);
        $this->assertTrue($c->has('foo'));
    }

    public function testConfig(): void
    {
        $config = ['foo' => 'bar'];
        $c = new Container($config);
        $this->assertSame($config['foo'], $c->get('foo'));
    }

    public function testShared(): void
    {
        $config = [
            'shared' => [Foo::class],
            Foo::class => fn() => new Foo(),
        ];
        $c = new Container($config);
        $this->assertSame($c->get(Foo::class), $c->get(Foo::class));
    }

    public function testNotShared(): void
    {
        $config = [
            Foo::class => fn() => new Foo(),
        ];
        $c = new Container($config);
        $this->assertNotSame($c->get(Foo::class), $c->get(Foo::class));
    }

    public function testDI(): void
    {
        $c = new Container(
            [
                SomeInterface::class => fn() => new class implements SomeInterface {
                },
            ]
        );
        $this->assertInstanceOf(Baz::class, $c->get(Baz::class));
    }

    public function testDIException(): void
    {
        $this->expectException(NotFoundException::class);
        $c = new Container([]);
        $c->get(Qux::class);
    }

    public function testConfigDI(): void
    {
        $c = new Container(
            [
                'foo' => 'bar',
                Qux::class => fn($c) => new Qux($c->get('foo')),
            ]
        );
        $this->assertInstanceOf(Qux::class, $c->get(Qux::class));
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
