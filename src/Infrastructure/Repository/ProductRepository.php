<?php

namespace App\Infrastructure\Repository;

use App\Domain\Product;

/**
 * Repository interface for Product persistence
 */
interface ProductRepository
{
    /**
     * Save a product
     */
    public function save(Product $product): void;

    /**
     * Find product by ID
     */
    public function findById(string $id): ?Product;

    /**
     * Find product by brand and slug
     */
    public function findByBrandAndSlug(string $brandId, string $slug): ?Product;

    /**
     * Get all products for a brand
     */
    public function findByBrandId(string $brandId): array;

    /**
     * Get all active products for a brand
     */
    public function findActiveByBrandId(string $brandId): array;
}
