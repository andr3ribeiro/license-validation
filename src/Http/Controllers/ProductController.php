<?php

namespace App\Http\Controllers;

use App\Application\{BrandService, LicenseKeyService, LicenseService};
use App\Domain\{
    LicenseKeyNotFoundException,
    UnauthorizedException
};
use App\Http\Controller;

/**
 * Product API Controller
 * 
 * Handles license validation and activation for products.
 * Requires Product API Key authentication.
 */
class ProductController extends Controller
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
            $brand = $this->brandService->authenticateBrandByValidationKey($apiKey);
            return $brand->getId();
        } catch (UnauthorizedException $e) {
            $this->errorResponse('UNAUTHORIZED', 'Invalid API key', 401);
            exit;
        }
    }

    /**
     * POST /api/v1/products/validate
     * Validate a license key for a specific product
     * 
     * Returns 200 if valid, 404 if not found/invalid
     */
    public function validateLicense(): void
    {
        $this->authenticateBrand();

        $body = $this->getJsonBody();
        $licenseKey = $body['license_key'] ?? null;
        $productId = $body['product_id'] ?? null;

        if (!$licenseKey || !$productId) {
            $this->errorResponse('INVALID_REQUEST', 'Missing license_key or product_id');
            return;
        }

        $result = $this->licenseService->validateLicense($licenseKey, $productId);

        if ($result === null) {
            $this->jsonResponse([
                'valid' => false,
                'message' => 'License not found or invalid'
            ], 404);
            return;
        }

        $this->jsonResponse($result);
    }

    /**
     * POST /api/v1/products/activate
     * Activate a license (mark as activated by product)
     */
    public function activateLicense(): void
    {
        $this->authenticateBrand();

        $body = $this->getJsonBody();
        $licenseKey = $body['license_key'] ?? null;
        $productId = $body['product_id'] ?? null;
        $activationSource = $body['activation_source'] ?? 'unknown';
        $metadata = $body['metadata'] ?? [];

        if (!$licenseKey || !$productId) {
            $this->errorResponse('INVALID_REQUEST', 'Missing license_key or product_id');
            return;
        }

        try {
            // Find license key
            $key = $this->licenseKeyService->getLicenseKeyByString($licenseKey);
            
            // Validate the license exists and is valid
            $result = $this->licenseService->validateLicense($licenseKey, $productId);
            
            if ($result === null) {
                $this->jsonResponse([
                    'activated' => false,
                    'message' => 'License not found or invalid'
                ], 404);
                return;
            }

            // Activate the license
            $this->licenseService->activateLicense($result['license_id']);

            $this->jsonResponse([
                'activated' => true,
                'license_id' => $result['license_id'],
                'activated_at' => (new \DateTime())->format(\DateTime::ISO8601)
            ]);
        } catch (LicenseKeyNotFoundException $e) {
            $this->jsonResponse([
                'activated' => false,
                'message' => 'License key not found'
            ], 404);
        } catch (\Exception $e) {
            $this->errorResponse('ERROR', $e->getMessage(), 400);
        }
    }

    /**
     * GET /api/v1/products/licenses/{licenseKey}
     * Get all licenses for a license key
     */
    public function getLicensesByKey(): void
    {
        $this->authenticateBrand();

        $licenseKey = $this->getRouteParam('licenseKey');

        if (!$licenseKey) {
            $this->errorResponse('INVALID_REQUEST', 'Missing license key');
            return;
        }

        try {
            $key = $this->licenseKeyService->getLicenseKeyByString($licenseKey);
            $licenses = $this->licenseService->getLicensesByKey($key->getId());

            $this->jsonResponse([
                'license_key' => $licenseKey,
                'licenses' => $licenses
            ]);
        } catch (LicenseKeyNotFoundException $e) {
            $this->errorResponse('NOT_FOUND', 'License key not found', 404);
        }
    }
}
