<?php

namespace App\Application;

use App\Domain\{
    Brand,
    BrandNotFoundException,
    InvalidBrandException,
    License,
    LicenseKey,
    LicenseKeyNotFoundException,
    LicenseNotFoundException,
    Product,
    ProductNotFoundException,
    UnauthorizedException,
    DuplicateLicenseException
};
use App\Infrastructure\IdGenerator;
use App\Infrastructure\Repository\{
    BrandRepository,
    LicenseKeyRepository,
    LicenseRepository,
    ProductRepository
};

/**
 * Service for managing licenses
 * 
 * Orchestrates license provisioning, validation, and lifecycle management.
 */
class LicenseService
{
    private LicenseRepository $licenseRepo;
    private LicenseKeyRepository $licenseKeyRepo;
    private ProductRepository $productRepo;
    private BrandRepository $brandRepo;

    public function __construct(
        LicenseRepository $licenseRepo,
        LicenseKeyRepository $licenseKeyRepo,
        ProductRepository $productRepo,
        BrandRepository $brandRepo
    ) {
        $this->licenseRepo = $licenseRepo;
        $this->licenseKeyRepo = $licenseKeyRepo;
        $this->productRepo = $productRepo;
        $this->brandRepo = $brandRepo;
    }

    /**
     * Create a new license and associate it with a license key
     * 
     * @throws BrandNotFoundException
     * @throws ProductNotFoundException
     * @throws LicenseKeyNotFoundException
     * @throws DuplicateLicenseException
     */
    public function createLicense(
        string $licenseKeyId,
        string $productId,
        \DateTime $startsAt,
        \DateTime $expiresAt
    ): License {
        // Validate license key exists
        $licenseKey = $this->licenseKeyRepo->findById($licenseKeyId);
        if ($licenseKey === null) {
            throw new LicenseKeyNotFoundException("License key not found: $licenseKeyId");
        }

        // Validate product exists
        $product = $this->productRepo->findById($productId);
        if ($product === null) {
            throw new ProductNotFoundException("Product not found: $productId");
        }

        // Ensure product belongs to the same brand as license key
        if ($product->getBrandId() !== $licenseKey->getBrandId()) {
            throw new InvalidBrandException("Product and license key must belong to same brand");
        }

        // Check for duplicate license (same key + product)
        $existing = $this->licenseRepo->findByKeyAndProduct($licenseKeyId, $productId);
        if ($existing !== null) {
            throw new DuplicateLicenseException("License already exists for this key and product");
        }

        // Create the license
        $license = new License(
            IdGenerator::generateUuid(),
            $licenseKeyId,
            $productId,
            $startsAt,
            $expiresAt
        );

        // Persist
        $this->licenseRepo->save($license);

        return $license;
    }

    /**
     * Get license by license key id and product id
     */
    public function getLicenseByKeyAndProduct(string $licenseKeyId, string $productId): ?License
    {
        return $this->licenseRepo->findByKeyAndProduct($licenseKeyId, $productId);
    }

    /**
     * Get all licenses for a license key (with product details)
     * 
     * @return array Array of licenses with associated product information
     */
    public function getLicensesByKey(string $licenseKeyId): array
    {
        $licenses = $this->licenseRepo->findByLicenseKeyId($licenseKeyId);
        
        return array_map(function(License $license) {
            $product = $this->productRepo->findById($license->getProductId());
            return [
                'id' => $license->getId(),
                'product_id' => $license->getProductId(),
                'product_name' => $product?->getName() ?? 'Unknown',
                'status' => $license->getStatus(),
                'starts_at' => $license->getStartsAt()->format(\DateTime::ISO8601),
                'expires_at' => $license->getExpiresAt()->format(\DateTime::ISO8601),
                'activated_at' => $license->getActivatedAt()?->format(\DateTime::ISO8601),
            ];
        }, $licenses);
    }

    /**
     * Validate a license by license key and product ID
     * 
     * Returns license details if valid, null if not found or invalid
     */
    public function validateLicense(string $licenseKey, string $productId): ?array
    {
        // Find license key
        $key = $this->licenseKeyRepo->findByKey($licenseKey);
        if ($key === null) {
            return null;
        }

        // Find license for this product
        $license = $this->licenseRepo->findByKeyAndProduct($key->getId(), $productId);
        if ($license === null) {
            return null;
        }

        // Check if valid
        if (!$license->isValid()) {
            return null;
        }

        return [
            'valid' => true,
            'license_id' => $license->getId(),
            'product_id' => $license->getProductId(),
            'status' => $license->getStatus(),
            'expires_at' => $license->getExpiresAt()->format(\DateTime::ISO8601),
            'activated_at' => $license->getActivatedAt()?->format(\DateTime::ISO8601),
        ];
    }

    /**
     * Activate a license
     * 
     * Called when a product validates and activates a license for the first time.
     * 
     * @throws LicenseNotFoundException
     */
    public function activateLicense(string $licenseId): void
    {
        $license = $this->licenseRepo->findById($licenseId);
        if ($license === null) {
            throw new LicenseNotFoundException("License not found: $licenseId");
        }

        if (!$license->canActivate()) {
            throw new \LogicException("License cannot be activated in its current state");
        }

        $license->activate();
        $this->licenseRepo->save($license);
    }

    /**
     * Suspend a license
     */
    public function suspendLicense(string $licenseId): void
    {
        $license = $this->licenseRepo->findById($licenseId);
        if ($license === null) {
            throw new LicenseNotFoundException("License not found: $licenseId");
        }

        $license->suspend();
        $this->licenseRepo->save($license);
    }

    /**
     * Reactivate a suspended license
     */
    public function reactivateLicense(string $licenseId): void
    {
        $license = $this->licenseRepo->findById($licenseId);
        if ($license === null) {
            throw new LicenseNotFoundException("License not found: $licenseId");
        }

        $license->reactivate();
        $this->licenseRepo->save($license);
    }

    /**
     * Cancel a license
     */
    public function cancelLicense(string $licenseId): void
    {
        $license = $this->licenseRepo->findById($licenseId);
        if ($license === null) {
            throw new LicenseNotFoundException("License not found: $licenseId");
        }

        $license->cancel();
        $this->licenseRepo->save($license);
    }

    /**
     * Mark expired licenses as expired (cleanup task)
     */
    public function markExpiredLicenses(): int
    {
        $expired = $this->licenseRepo->findExpired();
        
        foreach ($expired as $license) {
            $license->markExpired();
            $this->licenseRepo->save($license);
        }

        return count($expired);
    }
}
