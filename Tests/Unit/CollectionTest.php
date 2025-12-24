<?php

namespace Tests\Unit;

use Core\Model\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Collection Class Tests
 */
class CollectionTest extends TestCase
{
    public function testCollectionCanBeCreated(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $collection);
    }

    public function testCollectionMakeMethod(): void
    {
        $collection = Collection::make([1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $collection);
    }

    public function testCollectionAll(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $collection->all());
    }

    public function testCollectionCount(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(5, $collection->count());
    }

    public function testCollectionIsEmpty(): void
    {
        $empty = new Collection([]);
        $notEmpty = new Collection([1]);
        
        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function testCollectionIsNotEmpty(): void
    {
        $empty = new Collection([]);
        $notEmpty = new Collection([1]);
        
        $this->assertFalse($empty->isNotEmpty());
        $this->assertTrue($notEmpty->isNotEmpty());
    }

    public function testCollectionFirst(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(1, $collection->first());
    }

    public function testCollectionLast(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(3, $collection->last());
    }

    public function testCollectionMap(): void
    {
        $collection = new Collection([1, 2, 3]);
        $mapped = $collection->map(fn($n) => $n * 2);
        
        $this->assertEquals([2, 4, 6], $mapped->all());
    }

    public function testCollectionFilter(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $filtered = $collection->filter(fn($n) => $n > 2);
        
        $this->assertCount(3, $filtered);
    }

    public function testCollectionWhere(): void
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30],
            ['name' => 'Bob', 'age' => 25]
        ]);
        
        $filtered = $collection->where('age', 25);
        
        $this->assertCount(2, $filtered);
    }

    public function testCollectionPluck(): void
    {
        $collection = new Collection([
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30]
        ]);
        
        $names = $collection->pluck('name');
        
        $this->assertEquals(['John', 'Jane'], $names->all());
    }

    public function testCollectionUnique(): void
    {
        $collection = new Collection([1, 2, 2, 3, 3, 3]);
        $unique = $collection->unique();
        
        $this->assertCount(3, $unique);
    }

    public function testCollectionSort(): void
    {
        $collection = new Collection([3, 1, 2]);
        $sorted = $collection->sort();
        
        $this->assertEquals([1, 2, 3], array_values($sorted->all()));
    }

    public function testCollectionReverse(): void
    {
        $collection = new Collection([1, 2, 3]);
        $reversed = $collection->reverse();
        
        $this->assertEquals([3, 2, 1], array_values($reversed->all()));
    }

    public function testCollectionChunk(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $chunked = $collection->chunk(2);
        
        $this->assertCount(3, $chunked);
    }

    public function testCollectionTake(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $taken = $collection->take(3);
        
        $this->assertEquals([1, 2, 3], $taken->all());
    }

    public function testCollectionSkip(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $skipped = $collection->skip(2);
        
        $this->assertEquals([3, 4, 5], array_values($skipped->all()));
    }

    public function testCollectionSum(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        $this->assertEquals(15, $collection->sum());
    }

    public function testCollectionSumWithKey(): void
    {
        $collection = new Collection([
            ['price' => 10],
            ['price' => 20],
            ['price' => 30]
        ]);
        
        $this->assertEquals(60, $collection->sum('price'));
    }

    public function testCollectionAvg(): void
    {
        $collection = new Collection([10, 20, 30]);
        $this->assertEquals(20, $collection->avg());
    }

    public function testCollectionMin(): void
    {
        $collection = new Collection([5, 2, 8, 1, 9]);
        $this->assertEquals(1, $collection->min());
    }

    public function testCollectionMax(): void
    {
        $collection = new Collection([5, 2, 8, 1, 9]);
        $this->assertEquals(9, $collection->max());
    }

    public function testCollectionGroupBy(): void
    {
        $collection = new Collection([
            ['type' => 'A', 'value' => 1],
            ['type' => 'B', 'value' => 2],
            ['type' => 'A', 'value' => 3]
        ]);
        
        $grouped = $collection->groupBy('type');
        
        $this->assertCount(2, $grouped);
    }

    public function testCollectionToJson(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2]);
        $json = $collection->toJson();
        
        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals(['a' => 1, 'b' => 2], $decoded);
    }

    public function testCollectionChaining(): void
    {
        $result = Collection::make([1, 2, 3, 4, 5, 6])
            ->filter(fn($n) => $n > 2)
            ->map(fn($n) => $n * 2)
            ->take(3)
            ->sum();
        
        $this->assertEquals(30, $result); // (3*2) + (4*2) + (5*2) = 6 + 8 + 10 = 24
    }

    public function testCollectionArrayAccess(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2]);
        
        $this->assertTrue(isset($collection['a']));
        $this->assertEquals(1, $collection['a']);
        
        $collection['c'] = 3;
        $this->assertEquals(3, $collection['c']);
        
        unset($collection['c']);
        $this->assertFalse(isset($collection['c']));
    }

    public function testCollectionIsIterable(): void
    {
        $collection = new Collection([1, 2, 3]);
        $result = [];
        
        foreach ($collection as $item) {
            $result[] = $item;
        }
        
        $this->assertEquals([1, 2, 3], $result);
    }

    public function testCollectHelperFunction(): void
    {
        $collection = collect([1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $collection);
    }
}

