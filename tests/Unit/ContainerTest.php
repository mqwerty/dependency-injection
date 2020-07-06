<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Unit;

use PHPUnit\Framework\TestCase;
use Mqwerty\DI\Container;
use Mqwerty\DI\NotFoundException;
use Psr\Container\ContainerInterface;

final class ContainerTest extends TestCase
{
    public function testConstructor(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function testGetNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $c = new Container();
        $c->get('notFound');
    }

    public function testHasNotFound(): void
    {
        $c = new Container();
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

    public function testFactoryObject(): void
    {
        $c = new Container(
            [
                SomeInterface::class => new class implements SomeInterface {
                },
            ]
        );
        $this->assertInstanceOf(SomeInterface::class, $c->get(SomeInterface::class));
    }

    public function testFactoryFn(): void
    {
        $c = new Container(
            [
                SomeInterface::class => fn() => new class implements SomeInterface {
                },
            ]
        );
        $this->assertInstanceOf(SomeInterface::class, $c->get(SomeInterface::class));
    }

    public function testFactoryStaticFunction(): void
    {
        $c = new Container(
            [
                SomeInterface::class => static function () {
                    return new class implements SomeInterface {
                    };
                },
            ]
        );
        $this->assertInstanceOf(SomeInterface::class, $c->get(SomeInterface::class));
    }

    public function testFactoryStaticMethod(): void
    {
        $c = new Container(
            [
                Bar::class => [Bar::class, 'getInstance'],
            ]
        );
        $this->assertInstanceOf(Bar::class, $c->get(Bar::class));
    }

    public function testAlias(): void
    {
        $c = new Container(
            [
                'Some' => fn() => new class implements SomeInterface {
                },
            ]
        );
        $this->assertInstanceOf(SomeInterface::class, $c->get('Some'));
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
        $c = new Container();
        $c->get(Qux::class);
    }

    public function testDISelfInject(): void
    {
        $c = new Container(
            [
                'foo' => 'bar',
                Qux::class => fn($c) => new Qux($c->get('foo')),
            ]
        );
        $this->assertInstanceOf(Qux::class, $c->get(Qux::class));
    }

    public function testBuild(): void
    {
        $c = new Container();
        $this->assertInstanceOf(Qux::class, $c->build(Qux::class, ['s' => 'string']));
    }

    public function testBuildException(): void
    {
        $this->expectException(NotFoundException::class);
        $c = new Container();
        $this->assertInstanceOf(Qux::class, $c->build(Qux::class));
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
    public static function getInstance(): self
    {
        return new static();
    }
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
