<?php

namespace Core\Model;

/**
 * Enterprise Pagination System
 *
 * Database-agnostic pagination with customizable page size, navigation, and rendering.
 *
 * @package Core\Model
 * @version 1.0.0
 * @since 2025-12-24
 */
class Pagination
{
    private int $currentPage;
    private int $perPage;
    private int $total;
    private array $items;
    private string $path;

    public function __construct(array $items, int $total, int $perPage = 15, ?int $currentPage = null, string $path = '')
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage ?? $this->resolveCurrentPage();
        $this->path = $path ?: $_SERVER['REQUEST_URI'] ?? '';
    }

    private function resolveCurrentPage(): int
    {
        return max(1, (int) ($_GET['page'] ?? 1));
    }

    public function items(): array { return $this->items; }
    public function total(): int { return $this->total; }
    public function perPage(): int { return $this->perPage; }
    public function currentPage(): int { return $this->currentPage; }
    public function lastPage(): int { return (int) ceil($this->total / $this->perPage); }
    public function hasMorePages(): bool { return $this->currentPage() < $this->lastPage(); }
    public function hasPages(): bool { return $this->lastPage() > 1; }
    public function onFirstPage(): bool { return $this->currentPage() <= 1; }

    public function nextPageUrl(): ?string
    {
        return $this->hasMorePages() ? $this->url($this->currentPage() + 1) : null;
    }

    public function previousPageUrl(): ?string
    {
        return $this->currentPage() > 1 ? $this->url($this->currentPage() - 1) : null;
    }

    public function url(int $page): string
    {
        $url = parse_url($this->path);
        parse_str($url['query'] ?? '', $query);
        $query['page'] = $page;
        return ($url['path'] ?? '') . '?' . http_build_query($query);
    }

    public function links(int $onEachSide = 3): string
    {
        if (!$this->hasPages()) {
            return '';
        }

        $html = '<nav><ul class="pagination">';

        // Previous
        if ($this->onFirstPage()) {
            $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->previousPageUrl() . '">Previous</a></li>';
        }

        // Pages
        for ($page = 1; $page <= $this->lastPage(); $page++) {
            if ($page == $this->currentPage()) {
                $html .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
            } elseif ($page == 1 || $page == $this->lastPage() || abs($page - $this->currentPage()) <= $onEachSide) {
                $html .= '<li class="page-item"><a class="page-link" href="' . $this->url($page) . '">' . $page . '</a></li>';
            } elseif ($page == 2 || $page == $this->lastPage() - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next
        if ($this->hasMorePages()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->nextPageUrl() . '">Next</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }

    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage(),
            'data' => $this->items(),
            'first_page_url' => $this->url(1),
            'from' => ($this->currentPage() - 1) * $this->perPage() + 1,
            'last_page' => $this->lastPage(),
            'last_page_url' => $this->url($this->lastPage()),
            'next_page_url' => $this->nextPageUrl(),
            'path' => $this->path,
            'per_page' => $this->perPage(),
            'prev_page_url' => $this->previousPageUrl(),
            'to' => min($this->currentPage() * $this->perPage(), $this->total()),
            'total' => $this->total(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}

