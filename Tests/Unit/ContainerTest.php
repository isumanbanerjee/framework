<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

// --- Test fixtures ---------------------------------------------------------

class NoDependencies
{
    public string $marker = 'created';
}

class DependsOnNoDependencies
{
    public function __construct(public NoDependencies $dep)
    {
    }
}

class DependsOnTwo
{
    public function __construct(
        public NoDependencies $a,
        public DependsOnNoDependencies $b
    ) {
    }
}

class HasDefaultScalar
{
    public function __construct(
        public NoDependencies $dep,
        public int $count = 5
    ) {
    }
}

class NeedsUnresolvableScalar
{
    public function __construct(public string $name)
    {
    }
}

// --- Tests -----------------------------------------------------------------

final class ContainerTest extends TestCase
{
    public function testImplementsPsr11(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function testBindResolvesViaFactory(): void
    {
        $container = new Container();
        $container->bind('greeting', fn () => 'hello');

        $this->assertSame('hello', $container->get('greeting'));
    }

    public function testBindReturnsFreshInstanceEachTime(): void
    {
        $container = new Container();
        $container->bind(NoDependencies::class, fn () => new NoDependencies());

        $this->assertNotSame(
            $container->get(NoDependencies::class),
            $container->get(NoDependencies::class)
        );
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton(NoDependencies::class, fn () => new NoDependencies());

        $this->assertSame(
            $container->get(NoDependencies::class),
            $container->get(NoDependencies::class)
        );
    }

    public function testFactoryReceivesContainer(): void
    {
        $container = new Container();
        $container->bind('inner', fn () => 'value');
        $container->bind('outer', fn (ContainerInterface $c) => $c->get('inner') . '!');

        $this->assertSame('value!', $container->get('outer'));
    }

    public function testInstanceRegistersExistingObject(): void
    {
        $container = new Container();
        $object = new NoDependencies();
        $container->instance('shared', $object);

        $this->assertSame($object, $container->get('shared'));
    }

    public function testAutowiresClassWithNoConstructor(): void
    {
        $resolved = (new Container())->get(NoDependencies::class);

        $this->assertInstanceOf(NoDependencies::class, $resolved);
        $this->assertSame('created', $resolved->marker);
    }

    public function testAutowiresNestedDependencies(): void
    {
        $resolved = (new Container())->get(DependsOnTwo::class);

        $this->assertInstanceOf(DependsOnTwo::class, $resolved);
        $this->assertInstanceOf(NoDependencies::class, $resolved->a);
        $this->assertInstanceOf(DependsOnNoDependencies::class, $resolved->b);
        $this->assertInstanceOf(NoDependencies::class, $resolved->b->dep);
    }

    public function testAutowiringUsesDefaultForScalarWithDefault(): void
    {
        $resolved = (new Container())->get(HasDefaultScalar::class);

        $this->assertSame(5, $resolved->count);
        $this->assertInstanceOf(NoDependencies::class, $resolved->dep);
    }

    public function testHasReturnsTrueForBoundId(): void
    {
        $container = new Container();
        $container->bind('thing', fn () => 1);

        $this->assertTrue($container->has('thing'));
    }

    public function testHasReturnsTrueForResolvableClass(): void
    {
        $this->assertTrue((new Container())->has(NoDependencies::class));
    }

    public function testHasReturnsFalseForUnknownId(): void
    {
        $this->assertFalse((new Container())->has('nonexistent-service'));
    }

    public function testGetUnknownIdThrowsNotFound(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);
        (new Container())->get('nonexistent-service');
    }

    public function testGetUnresolvableScalarThrows(): void
    {
        $this->expectException(\Psr\Container\ContainerExceptionInterface::class);
        (new Container())->get(NeedsUnresolvableScalar::class);
    }

    public function testSingletonBoundByInterfaceResolves(): void
    {
        $container = new Container();
        $container->singleton(NoDependencies::class, fn () => new NoDependencies());

        $resolved = $container->get(DependsOnNoDependencies::class);
        // The autowired NoDependencies should come from the singleton binding.
        $this->assertSame($container->get(NoDependencies::class), $resolved->dep);
    }
}
