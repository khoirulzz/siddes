<?php

namespace App\Services\Messaging;

use Illuminate\Pagination\LengthAwarePaginator;

final readonly class MessagingPage
{
    public function __construct(public array $items, public array $pagination) {}

    public static function fromArray(array $response): self
    {
        if (! isset($response['items'], $response['pagination']['total'], $response['pagination']['page'], $response['pagination']['perPage']) || ! is_array($response['items']) || ! is_numeric($response['pagination']['total']) || (int) $response['pagination']['perPage'] < 1 || (int) $response['pagination']['page'] < 1) {
            throw new MessagingException('Respons layanan pesan tidak valid.');
        }
        return new self($response['items'], $response['pagination']);
    }

    public function paginator(): LengthAwarePaginator
    {
        return new LengthAwarePaginator($this->items, $this->pagination['total'], $this->pagination['perPage'], $this->pagination['page'], ['path' => url()->current(), 'query' => request()->query()]);
    }
}
