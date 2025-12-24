<?php

namespace Tests\Unit;

use Core\Model\Pagination;
use PHPUnit\Framework\TestCase;

/**
 * Pagination Tests
 */
class PaginationTest extends TestCase
{
    public function testPaginationCanBeCreated(): void
    {
        $items = [1, 2, 3];
        $paginator = new Pagination($items, 100, 15, 1);
        
        $this->assertInstanceOf(Pagination::class, $paginator);
    }

    public function testPaginationItems(): void
    {
        $items = [1, 2, 3];
        $paginator = new Pagination($items, 100, 15, 1);
        
        $this->assertEquals($items, $paginator->items());
    }

    public function testPaginationTotal(): void
    {
        $paginator = new Pagination([], 100, 15, 1);
        
        $this->assertEquals(100, $paginator->total());
    }

    public function testPaginationPerPage(): void
    {
        $paginator = new Pagination([], 100, 15, 1);
        
        $this->assertEquals(15, $paginator->perPage());
    }

    public function testPaginationCurrentPage(): void
    {
        $paginator = new Pagination([], 100, 15, 3);
        
        $this->assertEquals(3, $paginator->currentPage());
    }

    public function testPaginationLastPage(): void
    {
        $paginator = new Pagination([], 100, 15, 1);
        
        $this->assertEquals(7, $paginator->lastPage()); // ceil(100/15) = 7
    }

    public function testPaginationHasMorePages(): void
    {
        $firstPage = new Pagination([], 100, 15, 1);
        $lastPage = new Pagination([], 100, 15, 7);
        
        $this->assertTrue($firstPage->hasMorePages());
        $this->assertFalse($lastPage->hasMorePages());
    }

    public function testPaginationHasPages(): void
    {
        $single = new Pagination([], 10, 15, 1);
        $multiple = new Pagination([], 100, 15, 1);
        
        $this->assertFalse($single->hasPages());
        $this->assertTrue($multiple->hasPages());
    }

    public function testPaginationOnFirstPage(): void
    {
        $first = new Pagination([], 100, 15, 1);
        $second = new Pagination([], 100, 15, 2);
        
        $this->assertTrue($first->onFirstPage());
        $this->assertFalse($second->onFirstPage());
    }

    public function testPaginationNextPageUrl(): void
    {
        $paginator = new Pagination([], 100, 15, 2, '/users');
        
        $url = $paginator->nextPageUrl();
        
        $this->assertStringContainsString('page=3', $url);
    }

    public function testPaginationPreviousPageUrl(): void
    {
        $paginator = new Pagination([], 100, 15, 2, '/users');
        
        $url = $paginator->previousPageUrl();
        
        $this->assertStringContainsString('page=1', $url);
    }

    public function testPaginationUrl(): void
    {
        $paginator = new Pagination([], 100, 15, 1, '/users');
        
        $url = $paginator->url(5);
        
        $this->assertStringContainsString('page=5', $url);
    }

    public function testPaginationToArray(): void
    {
        $paginator = new Pagination([1, 2, 3], 100, 15, 2);
        
        $array = $paginator->toArray();
        
        $this->assertArrayHasKey('current_page', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('per_page', $array);
        $this->assertEquals(2, $array['current_page']);
        $this->assertEquals([1, 2, 3], $array['data']);
    }

    public function testPaginationToJson(): void
    {
        $paginator = new Pagination([1, 2, 3], 100, 15, 1);
        
        $json = $paginator->toJson();
        
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertEquals(1, $decoded['current_page']);
        $this->assertEquals(100, $decoded['total']);
    }

    public function testPaginationLinks(): void
    {
        $paginator = new Pagination([], 100, 15, 3);
        
        $links = $paginator->links();
        
        $this->assertStringContainsString('pagination', $links);
        $this->assertStringContainsString('Previous', $links);
        $this->assertStringContainsString('Next', $links);
    }

    public function testPaginationLinksOnFirstPage(): void
    {
        $paginator = new Pagination([], 100, 15, 1);
        
        $links = $paginator->links();
        
        $this->assertStringContainsString('disabled', $links);
    }

    public function testPaginationLinksOnLastPage(): void
    {
        $paginator = new Pagination([], 100, 15, 7);
        
        $links = $paginator->links();
        
        $this->assertStringContainsString('disabled', $links);
    }
}

