<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Model\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testBasicMeta(): void
    {
        $pagination = new Pagination(['a', 'b', 'c'], 25, 10, 2, '/items');

        $this->assertSame(['a', 'b', 'c'], $pagination->items());
        $this->assertSame(25, $pagination->total());
        $this->assertSame(10, $pagination->perPage());
        $this->assertSame(2, $pagination->currentPage());
        $this->assertSame(3, $pagination->lastPage());
    }

    public function testHasMorePagesAndHasPages(): void
    {
        $pagination = new Pagination([], 25, 10, 2, '/items');
        $this->assertTrue($pagination->hasMorePages());
        $this->assertTrue($pagination->hasPages());
        $this->assertFalse($pagination->onFirstPage());

        $last = new Pagination([], 25, 10, 3, '/items');
        $this->assertFalse($last->hasMorePages());

        $single = new Pagination([], 5, 10, 1, '/items');
        $this->assertFalse($single->hasPages());
        $this->assertTrue($single->onFirstPage());
    }

    public function testNextAndPreviousPageUrls(): void
    {
        $pagination = new Pagination([], 30, 10, 2, '/items');
        $this->assertSame('/items?page=3', $pagination->nextPageUrl());
        $this->assertSame('/items?page=1', $pagination->previousPageUrl());

        $first = new Pagination([], 30, 10, 1, '/items');
        $this->assertNull($first->previousPageUrl());

        $last = new Pagination([], 30, 10, 3, '/items');
        $this->assertNull($last->nextPageUrl());
    }

    public function testUrlPreservesExistingQueryParams(): void
    {
        $pagination = new Pagination([], 30, 10, 1, '/items?sort=name');
        $this->assertSame('/items?sort=name&page=5', $pagination->url(5));
    }

    public function testToArray(): void
    {
        $pagination = new Pagination(['x'], 21, 10, 2, '/items');
        $array = $pagination->toArray();

        $this->assertSame(2, $array['current_page']);
        $this->assertSame(['x'], $array['data']);
        $this->assertSame(3, $array['last_page']);
        $this->assertSame(11, $array['from']);
        $this->assertSame(20, $array['to']);
        $this->assertSame(21, $array['total']);
    }

    public function testToJson(): void
    {
        $pagination = new Pagination([], 10, 10, 1, '/items');
        $decoded = json_decode($pagination->toJson(), true);
        $this->assertSame(1, $decoded['current_page']);
        $this->assertSame(10, $decoded['total']);
    }

    public function testLinksIsEmptyWhenSinglePage(): void
    {
        $pagination = new Pagination([], 5, 10, 1, '/items');
        $this->assertSame('', $pagination->links());
    }

    public function testLinksContainsCurrentPageMarkup(): void
    {
        $pagination = new Pagination([], 30, 10, 2, '/items');
        $html = $pagination->links();

        $this->assertStringContainsString('page-item active', $html);
        $this->assertStringContainsString('>2<', $html);
        $this->assertStringContainsString('Previous', $html);
        $this->assertStringContainsString('Next', $html);
    }
}
