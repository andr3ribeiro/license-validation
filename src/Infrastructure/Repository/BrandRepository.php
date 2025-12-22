<?php

namespace App\Infrastructure\Repository;

use App\Domain\Brand;

/**
 * Repository interface for Brand persistence
 */
interface BrandRepository
{
    /**
     * Save a brand
     */
    public function save(Brand $brand): void;

    /**
     * Find brand by ID
     */
    public function findById(string $id): ?Brand;

    /**
     * Find brand by slug
     */
    public function findBySlug(string $slug): ?Brand;

    /**
     * Find brand by API key (Brand provisioning)
     */
    public function findByApiKeyBrand(string $apiKey): ?Brand;

    /**
     * Find brand by API key (Product validation)
     */
    public function findByApiKeyProduct(string $apiKey): ?Brand;

    /**
     * Get all active brands
     */
    public function findAllActive(): array;
}
