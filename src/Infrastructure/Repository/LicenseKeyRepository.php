<?php

namespace App\Infrastructure\Repository;

use App\Domain\LicenseKey;

/**
 * Repository interface for LicenseKey persistence
 */
interface LicenseKeyRepository
{
    /**
     * Save a license key
     */
    public function save(LicenseKey $licenseKey): void;

    /**
     * Find license key by ID
     */
    public function findById(string $id): ?LicenseKey;

    /**
     * Find license key by key string
     */
    public function findByKey(string $key): ?LicenseKey;

    /**
     * Find license keys by brand and customer email
     */
    public function findByBrandAndCustomerEmail(string $brandId, string $customerEmail): array;

    /**
     * Get all license keys for a brand
     */
    public function findByBrandId(string $brandId): array;

    /**
     * Check if a license key exists
     */
    public function exists(string $id): bool;
}
