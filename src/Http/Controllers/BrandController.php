<?php

namespace App\Http\Controllers;

use App\Application\{BrandService, LicenseKeyService, LicenseService};
use App\Domain\{
    DuplicateLicenseException,
    InvalidBrandException,
    LicenseKeyNotFoundException,
    ProductNotFoundException,
    UnauthorizedException
};
use App\Http\Controller;

/**
 * Brand Provisioning API Controller
 *
 * Handles license key and license creation for brands.
 * Requires Brand API Key authentication.
 */
class BrandController extends Controller
{
    private BrandService $brandService;
    private LicenseKeyService $licenseKeyService;
    private LicenseService $licenseService;

    public function __construct(
        BrandService $brandService,
        LicenseKeyService $licenseKeyService,
        LicenseService $licenseService
    ) {
        $this->brandService = $brandService;
        $this->licenseKeyService = $licenseKeyService;
        $this->licenseService = $licenseService;
    }

    /**
     * Authenticate the brand from API key
     */
    private function authenticateBrand(): string
    {
        $apiKey = $this->getAuthorizationHeader();
        if (!$apiKey) {
            $this->errorResponse('UNAUTHORIZED', 'Missing Authorization header', 401);
            exit;
        }

        try {
            $brand = $this->brandService->authenticateBrandByProvisioningKey($apiKey);
            return $brand->getId();
        } catch (UnauthorizedException $e) {
            $this->errorResponse('UNAUTHORIZED', 'Invalid API key', 401);
            exit;
        }
    }

    /**
     * POST /api/v1/brands/{brandId}/license-keys
     * Create a new license key for a customer
     */
    public function createLicenseKey(): void
    {
        $brandId = $this->getRouteParam('brandId');
        $this->validateBrandAccess($brandId);

        $body = $this->getJsonBody();
        $customerEmail = $body['customer_email'] ?? null;

        if (!$customerEmail || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->errorResponse('INVALID_EMAIL', 'Invalid or missing customer_email');
            return;
        }

        try {
            $licenseKey = $this->licenseKeyService->createLicenseKey($brandId, $customerEmail);

            $this->createdResponse([
                'id' => $licenseKey->getId(),
                'key' => $licenseKey->getKey(),
                'customer_email' => $licenseKey->getCustomerEmail(),
                'status' => $licenseKey->getStatus(),
                'created_at' => $licenseKey->getCreatedAt()->format(\DateTime::ISO8601),
            ]);
        } catch (InvalidBrandException $e) {
            $this->errorResponse('INVALID_BRAND', $e->getMessage(), 400);
        }
    }

    /**
     * GET /api/v1/brands/{brandId}/license-keys/{licenseKeyId}
     * Get license key with associated licenses
     */
    public function getLicenseKey(): void
    {
        $brandId = $this->getRouteParam('brandId');
        $licenseKeyId = $this->getRouteParam('licenseKeyId');

        $this->validateBrandAccess($brandId);

        try {
            $licenseKey = $this->licenseKeyService->getLicenseKey($licenseKeyId);

            // Verify license key belongs to the brand
            if ($licenseKey->getBrandId() !== $brandId) {
                $this->errorResponse('FORBIDDEN', 'License key does not belong to this brand', 403);
                return;
            }

            // Get associated licenses
            $licenses = $this->licenseService->getLicensesByKey($licenseKeyId);

            $this->jsonResponse([
                'id' => $licenseKey->getId(),
                'key' => $licenseKey->getKey(),
                'customer_email' => $licenseKey->getCustomerEmail(),
                'status' => $licenseKey->getStatus(),
                'licenses' => $licenses,
                'created_at' => $licenseKey->getCreatedAt()->format(\DateTime::ISO8601),
            ]);
        } catch (LicenseKeyNotFoundException $e) {
            $this->errorResponse('NOT_FOUND', $e->getMessage(), 404);
        }
    }

    /**
     * POST /api/v1/brands/{brandId}/licenses
     * Create a new license for a license key
     */
    public function createLicense(): void
    {
        $brandId = $this->getRouteParam('brandId');
        $this->validateBrandAccess($brandId);

        $body = $this->getJsonBody();
        $licenseKeyId = $body['license_key_id'] ?? null;
        $productId = $body['product_id'] ?? null;
        $startsAt = $body['starts_at'] ?? null;
        $expiresAt = $body['expires_at'] ?? null;
        $seatLimit = $body['seat_limit'] ?? null;

        // Validate required fields
        if (!$licenseKeyId || !$productId || !$startsAt || !$expiresAt) {
            $this->errorResponse('INVALID_REQUEST', 'Missing required fields');
            return;
        }

        // Parse dates
        try {
            $startsAtDt = new \DateTime($startsAt);
            $expiresAtDt = new \DateTime($expiresAt);
        } catch (\Exception $e) {
            $this->errorResponse('INVALID_DATE', 'Invalid date format');
            return;
        }

        if ($seatLimit !== null) {
            if (!is_int($seatLimit) && !ctype_digit((string)$seatLimit)) {
                $this->errorResponse('INVALID_SEAT_LIMIT', 'seat_limit must be an integer');
                return;
            }
            $seatLimit = (int)$seatLimit;
            if ($seatLimit <= 0) {
                $seatLimit = null; // treat non-positive as unlimited
            }
        }

        try {
            $license = $this->licenseService->createLicense(
                $licenseKeyId,
                $productId,
                $startsAtDt,
                $expiresAtDt,
                $seatLimit
            );

            $this->createdResponse($license->toArray());
        } catch (LicenseKeyNotFoundException | ProductNotFoundException $e) {
            $this->errorResponse('NOT_FOUND', $e->getMessage(), 404);
        } catch (InvalidBrandException $e) {
            $this->errorResponse('INVALID_BRAND', $e->getMessage(), 403);
        } catch (DuplicateLicenseException $e) {
            $this->errorResponse('DUPLICATE_LICENSE', $e->getMessage(), 409);
        }
    }

    /**
     * PATCH /api/v1/brands/{brandId}/licenses/{licenseId}
     * Update license status (suspend/reactivate)
     */
    public function updateLicense(): void
    {
        $brandId = $this->getRouteParam('brandId');
        $licenseId = $this->getRouteParam('licenseId');

        $this->validateBrandAccess($brandId);

        $body = $this->getJsonBody();
        $newStatus = $body['status'] ?? null;

        if (!in_array($newStatus, ['valid', 'suspended', 'cancelled'])) {
            $this->errorResponse('INVALID_STATUS', 'Invalid license status');
            return;
        }

        try {
            switch ($newStatus) {
                case 'suspended':
                    $this->licenseService->suspendLicense($licenseId);
                    break;
                case 'valid':
                    $this->licenseService->reactivateLicense($licenseId);
                    break;
                case 'cancelled':
                    $this->licenseService->cancelLicense($licenseId);
                    break;
            }

            $license = $this->licenseService->getLicensesByKey(
                // We need to get the license to return it
                // This is a bit inefficient, but for now we'll fetch by ID from repo
                // For production, inject LicenseRepository here
            );

            $this->jsonResponse([
                'status' => $newStatus,
                'message' => 'License status updated'
            ]);
        } catch (\Exception $e) {
            $this->errorResponse('ERROR', $e->getMessage(), 400);
        }
    }

    /**
     * Validate that the authenticated brand owns this brand ID
     */
    private function validateBrandAccess(string $brandId): void
    {
        $authenticatedBrandId = $this->authenticateBrand();

        if ($authenticatedBrandId !== $brandId) {
            $this->errorResponse('FORBIDDEN', 'No access to this brand', 403);
            exit;
        }
    }
}
