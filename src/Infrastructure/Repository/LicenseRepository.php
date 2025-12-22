<?php

namespace App\Infrastructure\Repository;

use App\Domain\License;

/**
 * Repository interface for License persistence
 */
interface LicenseRepository
{
    /**
     * Save a license
     */
    public function save(License $license): void;

    /**
     * Find license by ID
     */
    public function findById(string $id): ?License;

    /**
     * Find licenses by license key ID
     */
    public function findByLicenseKeyId(string $licenseKeyId): array;

    /**
     * Find license by license key ID and product ID
     */
    public function findByKeyAndProduct(string $licenseKeyId, string $productId): ?License;

    /**
     * Find licenses by product ID
     */
    public function findByProductId(string $productId): array;

    /**
     * Get all valid licenses for a brand (for reporting)
     */
    public function findValidByBrandId(string $brandId): array;

    /**
     * Get all expired licenses (for cleanup/reporting)
     */
    public function findExpired(): array;
}
