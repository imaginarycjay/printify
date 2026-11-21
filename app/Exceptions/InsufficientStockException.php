<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    /**
     * @param  array<int, array{id: int, name: string, available_qty: float, unit: string, category: string}>  $fallbackSuggestions
     */
    public function __construct(
        public readonly int $inventoryItemId,
        public readonly string $itemName,
        public readonly float $requestedQty,
        public readonly float $availableQty,
        public readonly string $unit,
        public readonly array $fallbackSuggestions = [],
        string $message = '',
    ) {
        $resolvedMessage = $message !== ''
            ? $message
            : sprintf(
                'Insufficient available stock for %s: requested %s %s, only %s %s available.',
                $this->itemName,
                number_format($this->requestedQty, 2),
                $this->unit,
                number_format($this->availableQty, 2),
                $this->unit,
            );

        parent::__construct($resolvedMessage);
    }
}
