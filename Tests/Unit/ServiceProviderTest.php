<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Container;
use Core\Model\ServiceProvider;
use PHPUnit\Framework\TestCase;

class RecordingServiceProvider extends ServiceProvider
{
    public bool $registered = false;
    public bool $booted = false;

    public function register(): void
    {
        $this->registered = true;
        $this->container->singleton('recorded', fn () => new \stdClass());
    }

    public function boot(): void
    {
        $this->booted = true;
    }
}

class RegisterOnlyProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->bind('value', fn () => 42);
    }
}

final class ServiceProviderTest extends TestCase
{
    public function testRegisterBindsIntoContainer(): void
    {
        $container = new Container();
        $provider = new RecordingServiceProvider($container);

        $provider->register();

        $this->assertTrue($provider->registered);
        $this->assertInstanceOf(\stdClass::class, $container->get('recorded'));
    }

    public function testBootRunsAfterRegistration(): void
    {
        $container = new Container();
        $provider = new RecordingServiceProvider($container);

        $this->assertFalse($provider->booted);
        $provider->boot();
        $this->assertTrue($provider->booted);
    }

    public function testBootIsOptionalAndDefaultsToNoop(): void
    {
        $container = new Container();
        $provider = new RegisterOnlyProvider($container);

        $provider->register();
        $provider->boot(); // inherited no-op should not throw

        $this->assertSame(42, $container->get('value'));
    }
}
