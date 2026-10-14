<?php

namespace App\TreeOrders;

/**
 * One size of Christmas tree on sale this year (TREE_SIZES in .env).
 */
final readonly class TreeSize
{
    public function __construct(
        public string $key,
        public string $name,
        public string $height,
        public int $priceCents,
    ) {}
}
