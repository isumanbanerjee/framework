<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function testMakeAndAll(): void
    {
        $collection = Collection::make([1, 2, 3]);
        $this->assertSame([1, 2, 3], $collection->all());
    }

    public function testCountAndEmptiness(): void
    {
        $this->assertSame(0, (new Collection())->count());
        $this->assertTrue((new Collection())->isEmpty());
        $this->assertFalse((new Collection())->isNotEmpty());

        $collection = new Collection([1, 2]);
        $this->assertSame(2, $collection->count());
        $this->assertFalse($collection->isEmpty());
    }

    public function testFirstAndLast(): void
    {
        $collection = new Collection(['a', 'b', 'c']);
        $this->assertSame('a', $collection->first());
        $this->assertSame('c', $collection->last());
        $this->assertNull((new Collection())->first());
        $this->assertNull((new Collection())->last());
    }

    public function testMap(): void
    {
        $collection = new Collection([1, 2, 3]);
        $result = $collection->map(fn ($n) => $n * 2);
        $this->assertSame([2, 4, 6], $result->all());
    }

    public function testFilterWithAndWithoutCallback(): void
    {
        $collection = new Collection([0, 1, 2, false, 3]);
        $this->assertSame([1 => 1, 2 => 2, 4 => 3], $collection->filter()->all());

        $collection = new Collection([1, 2, 3, 4]);
        $result = $collection->filter(fn ($n) => $n % 2 === 0);
        $this->assertSame([1 => 2, 3 => 4], $result->all());
    }

    public function testWhere(): void
    {
        $collection = new Collection([
            ['name' => 'Alice', 'role' => 'admin'],
            ['name' => 'Bob', 'role' => 'user'],
        ]);

        $result = $collection->where('role', 'admin');
        $this->assertSame([['name' => 'Alice', 'role' => 'admin']], $result->all());
    }

    public function testPluck(): void
    {
        $collection = new Collection([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $this->assertSame(['Alice', 'Bob'], $collection->pluck('name')->all());
    }

    public function testUnique(): void
    {
        $collection = new Collection([1, 2, 2, 3, 3, 3]);
        $this->assertSame([0 => 1, 1 => 2, 3 => 3], $collection->unique()->all());
    }

    public function testSortWithoutCallback(): void
    {
        $collection = new Collection([3, 1, 2]);
        $this->assertSame([1, 2, 3], $collection->sort()->all());
    }

    public function testSortWithCallback(): void
    {
        $collection = new Collection([3, 1, 2]);
        $result = $collection->sort(fn ($a, $b) => $b <=> $a);
        $this->assertSame([3, 2, 1], $result->all());
    }

    public function testReverse(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertSame([3, 2, 1], $collection->reverse()->all());
    }

    public function testChunk(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertSame([[1, 2], [3, 4], [5]], $collection->chunk(2)->all());
    }

    public function testTakeAndSkip(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertSame([1, 2], $collection->take(2)->all());
        $this->assertSame([4, 5], $collection->skip(3)->all());
    }

    public function testEachStopsWhenCallbackReturnsFalse(): void
    {
        $seen = [];
        $collection = new Collection([1, 2, 3, 4]);
        $collection->each(function ($item) use (&$seen) {
            $seen[] = $item;
            return $item < 3;
        });

        $this->assertSame([1, 2, 3], $seen);
    }

    public function testSumAvgMinMaxWithoutKey(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        $this->assertSame(10, $collection->sum());
        $this->assertSame(2.5, $collection->avg());
        $this->assertSame(1, $collection->min());
        $this->assertSame(4, $collection->max());
    }

    public function testSumAvgMinMaxWithKey(): void
    {
        $collection = new Collection([
            ['score' => 10],
            ['score' => 20],
            ['score' => 30],
        ]);

        $this->assertSame(60, $collection->sum('score'));
        $this->assertSame(20, $collection->avg('score'));
        $this->assertSame(10, $collection->min('score'));
        $this->assertSame(30, $collection->max('score'));
    }

    public function testAvgOfEmptyCollectionIsZero(): void
    {
        $this->assertSame(0, (new Collection())->avg());
    }

    public function testGroupBy(): void
    {
        $collection = new Collection([
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'banana'],
            ['type' => 'veg', 'name' => 'carrot'],
        ]);

        $groups = $collection->groupBy('type')->all();
        $this->assertSame(['apple', 'banana'], array_column($groups['fruit'], 'name'));
        $this->assertSame(['carrot'], array_column($groups['veg'], 'name'));
    }

    public function testToJson(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2]);
        $this->assertJsonStringEqualsJsonString('{"a":1,"b":2}', $collection->toJson());
    }

    public function testArrayAccess(): void
    {
        $collection = new Collection(['a' => 1]);
        $this->assertTrue(isset($collection['a']));
        $this->assertSame(1, $collection['a']);

        $collection['b'] = 2;
        $this->assertSame(2, $collection['b']);

        unset($collection['a']);
        $this->assertFalse(isset($collection['a']));
    }

    public function testIterator(): void
    {
        $collection = new Collection(['x', 'y', 'z']);
        $result = [];
        foreach ($collection as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame(['x', 'y', 'z'], $result);
    }

    public function testCollectHelperFunction(): void
    {
        require_once __DIR__ . '/../../Core/Model/Collection.php';
        $collection = \Core\Model\collect([1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame([1, 2, 3], $collection->all());
    }
}
