<?php

namespace Tests\Unit;

use Core\Model\Event;
use PHPUnit\Framework\TestCase;

/**
 * Event System Tests
 */
class EventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::flush(); // Clear all listeners
    }

    public function testEventCanRegisterListener(): void
    {
        $called = false;
        
        Event::listen('test.event', function() use (&$called) {
            $called = true;
        });
        
        Event::fire('test.event');
        
        $this->assertTrue($called);
    }

    public function testEventCanPassPayload(): void
    {
        $receivedPayload = null;
        
        Event::listen('user.created', function($payload) use (&$receivedPayload) {
            $receivedPayload = $payload;
        });
        
        Event::fire('user.created', ['name' => 'John']);
        
        $this->assertEquals(['name' => 'John'], $receivedPayload);
    }

    public function testEventCanHaveMultipleListeners(): void
    {
        $count = 0;
        
        Event::listen('test', function() use (&$count) { $count++; });
        Event::listen('test', function() use (&$count) { $count++; });
        Event::listen('test', function() use (&$count) { $count++; });
        
        Event::fire('test');
        
        $this->assertEquals(3, $count);
    }

    public function testEventDispatchAlias(): void
    {
        $called = false;
        
        Event::listen('test', function() use (&$called) {
            $called = true;
        });
        
        Event::dispatch('test');
        
        $this->assertTrue($called);
    }

    public function testEventUntilReturnsFirstNonNullResponse(): void
    {
        Event::listen('test', function() { return null; });
        Event::listen('test', function() { return 'first'; });
        Event::listen('test', function() { return 'second'; });
        
        $result = Event::until('test');
        
        $this->assertEquals('first', $result);
    }

    public function testEventHasListeners(): void
    {
        $this->assertFalse(Event::hasListeners('test'));
        
        Event::listen('test', function() {});
        
        $this->assertTrue(Event::hasListeners('test'));
    }

    public function testEventForgetRemovesListeners(): void
    {
        Event::listen('test', function() {});
        
        $this->assertTrue(Event::hasListeners('test'));
        
        Event::forget('test');
        
        $this->assertFalse(Event::hasListeners('test'));
    }

    public function testEventFlushRemovesAllListeners(): void
    {
        Event::listen('event1', function() {});
        Event::listen('event2', function() {});
        
        $this->assertTrue(Event::hasListeners('event1'));
        $this->assertTrue(Event::hasListeners('event2'));
        
        Event::flush();
        
        $this->assertFalse(Event::hasListeners('event1'));
        $this->assertFalse(Event::hasListeners('event2'));
    }

    public function testEventCanStopPropagation(): void
    {
        $count = 0;
        
        Event::listen('test', function() use (&$count) {
            $count++;
            return false; // Stop propagation
        });
        
        Event::listen('test', function() use (&$count) {
            $count++; // Should not be called
        });
        
        Event::fire('test');
        
        $this->assertEquals(1, $count);
    }

    public function testEventReturnsAllResponses(): void
    {
        Event::listen('test', function() { return 'a'; });
        Event::listen('test', function() { return 'b'; });
        Event::listen('test', function() { return 'c'; });
        
        $responses = Event::fire('test');
        
        $this->assertEquals(['a', 'b', 'c'], $responses);
    }

    public function testEventCanListenToMultipleEvents(): void
    {
        $count = 0;
        
        Event::listen(['event1', 'event2'], function() use (&$count) {
            $count++;
        });
        
        Event::fire('event1');
        Event::fire('event2');
        
        $this->assertEquals(2, $count);
    }

    public function testEventWildcardListener(): void
    {
        $events = [];
        
        Event::listen('user.*', function($payload, $event) use (&$events) {
            $events[] = $event;
        });
        
        Event::fire('user.created');
        Event::fire('user.updated');
        Event::fire('user.deleted');
        
        $this->assertCount(3, $events);
        $this->assertContains('user.created', $events);
        $this->assertContains('user.updated', $events);
        $this->assertContains('user.deleted', $events);
    }

    public function testEventFake(): void
    {
        $fake = Event::fake();
        
        $this->assertInstanceOf(\Core\Model\FakeEvent::class, $fake);
    }
}

