<?php

namespace App\Application;

use App\Domain\{
    Brand,
    BrandNotFoundException,
    InvalidBrandException,
    LicenseKey,
    LicenseKeyNotFoundException
};
use App\Infrastructure\IdGenerator;
use App\Infrastructure\Repository\{
    BrandRepository,
    LicenseKeyRepository
};

/**
 * Service for managing license keys
 * 
 * Handles creation, retrieval, and lifecycle of license keys.
 * A license key is the primary identifier given to customers and can unlock
 * multiple licenses (one per product).
 */
class LicenseKeyService
{
    private LicenseKeyRepository $licenseKeyRepo;
    private BrandRepository $brandRepo;

    public function __construct(
        LicenseKeyRepository $licenseKeyRepo,
        BrandRepository $brandRepo
    ) {
        $this->licenseKeyRepo = $licenseKeyRepo;
        $this->brandRepo = $brandRepo;
    }

    /**
     * Create a new license key for a customer
     * 
     * @throws BrandNotFoundException
     * @throws InvalidBrandException
     */
    public function createLicenseKey(string $brandId, string $customerEmail): LicenseKey
    {
        // Validate brand exists and is active
        $brand = $this->brandRepo->findById($brandId);
        if ($brand === null) {
            throw new BrandNotFoundException("Brand not found: $brandId");
        }

        if (!$brand->isActive()) {
            throw new InvalidBrandException("Brand is not active");
        }

        // Generate unique key
        $keyString = $this->generateUniqueKey($brand);

        // Create license key
        $licenseKey = new LicenseKey(
            IdGenerator::generateUuid(),
            $brandId,
            $customerEmail,
            $keyString,
            $brandId // Created by this brand
        );

        // Persist
        $this->licenseKeyRepo->save($licenseKey);

        return $licenseKey;
    }

    /**
     * Get license key by ID
     * 
     * @throws LicenseKeyNotFoundException
     */
    public function getLicenseKey(string $licenseKeyId): LicenseKey
    {
        $licenseKey = $this->licenseKeyRepo->findById($licenseKeyId);
        if ($licenseKey === null) {
            throw new LicenseKeyNotFoundException("License key not found: $licenseKeyId");
        }

        return $licenseKey;
    }

    /**
     * Get license key by key string
     * 
     * @throws LicenseKeyNotFoundException
     */
    public function getLicenseKeyByString(string $keyString): LicenseKey
    {
        $licenseKey = $this->licenseKeyRepo->findByKey($keyString);
        if ($licenseKey === null) {
            throw new LicenseKeyNotFoundException("License key not found: $keyString");
        }

        return $licenseKey;
    }

    /**
     * Get all license keys for a brand and customer email
     */
    public function getLicenseKeysByCustomer(string $brandId, string $customerEmail): array
    {
        return $this->licenseKeyRepo->findByBrandAndCustomerEmail($brandId, $customerEmail);
    }

    /**
     * Suspend a license key (deactivate all associated licenses)
     * 
     * @throws LicenseKeyNotFoundException
     */
    public function suspendLicenseKey(string $licenseKeyId): void
    {
        $licenseKey = $this->licenseKeyRepo->findById($licenseKeyId);
        if ($licenseKey === null) {
            throw new LicenseKeyNotFoundException("License key not found: $licenseKeyId");
        }

        $licenseKey->suspend();
        $this->licenseKeyRepo->save($licenseKey);
    }

    /**
     * Reactivate a suspended license key
     * 
     * @throws LicenseKeyNotFoundException
     */
    public function reactivateLicenseKey(string $licenseKeyId): void
    {
        $licenseKey = $this->licenseKeyRepo->findById($licenseKeyId);
        if ($licenseKey === null) {
            throw new LicenseKeyNotFoundException("License key not found: $licenseKeyId");
        }

        $licenseKey->reactivate();
        $this->licenseKeyRepo->save($licenseKey);
    }

    /**
     * Cancel a license key (permanent)
     * 
     * @throws LicenseKeyNotFoundException
     */
    public function cancelLicenseKey(string $licenseKeyId): void
    {
        $licenseKey = $this->licenseKeyRepo->findById($licenseKeyId);
        if ($licenseKey === null) {
            throw new LicenseKeyNotFoundException("License key not found: $licenseKeyId");
        }

        $licenseKey->cancel();
        $this->licenseKeyRepo->save($licenseKey);
    }

    /**
     * Generate a unique license key string
     * 
     * Format: {BRAND_ACRONYM}-{YEAR}-{RANDOM}
     * Example: RANK-2025-A1B2C3D4E5F6
     */
    private function generateUniqueKey(Brand $brand): string
    {
        $acronym = $this->extractAcronym($brand->getSlug());
        
        // Generate until we get a unique key
        do {
            $key = IdGenerator::generateLicenseKey($acronym);
        } while ($this->licenseKeyRepo->findByKey($key) !== null);

        return $key;
    }

    /**
     * Extract acronym from brand slug
     * 
     * Examples:
     * - "rankmath" -> "RANK"
     * - "wp-rocket" -> "WPRK"
     * - "content-ai" -> "CONT"
     */
    private function extractAcronym(string $slug): string
    {
        $parts = explode('-', $slug);
        
        if (count($parts) === 1) {
            // Single word, take first 4 characters
            return strtoupper(substr($slug, 0, 4));
        }

        // Multiple words, take first letter of each
        $acronym = '';
        foreach ($parts as $part) {
            if (!empty($part)) {
                $acronym .= strtoupper($part[0]);
            }
        }

        // Ensure at least 4 characters
        if (strlen($acronym) < 4) {
            $acronym = strtoupper(str_replace('-', '', $slug));
            $acronym = substr($acronym, 0, 4);
        }

        return substr($acronym, 0, 4);
    }
}
